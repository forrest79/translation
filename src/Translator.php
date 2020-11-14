<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Nette\Utils;
use Tracy;

class Translator implements ITranslator
{
	private bool $debugMode;

	private string $tempDir;

	private Tracy\ILogger $logger;

	private ?DataLoader $dataLoader = NULL;

	private ?string $locale = NULL;

	private ?string $fallbackLocale = NULL;

	/** @var array<string, TranslatorImmutable> */
	private array $immutableTranslators = [];

	/** @var array<string, TranslatorData> */
	private array $data = [];

	private ?LocaleUtils $localeUtils = NULL;

	private ?Diagnostics\Panel $panel = NULL;


	public function __construct(bool $debugMode, string $tempDir, Tracy\ILogger $logger)
	{
		$this->debugMode = $debugMode;
		$this->tempDir = $tempDir;
		$this->logger = $logger;
	}


	/**
	 * @throws Exceptions\BadLocaleNameException
	 */
	public function setLocale(string $locale): self
	{
		$locale = strtolower($locale);
		$this->checkLocaleName($locale);

		$this->locale = $locale;
		return $this;
	}


	/**
	 * @throws Exceptions\NoLocaleSelectedException
	 */
	public function getLocale(): string
	{
		if ($this->locale === NULL) {
			throw new Exceptions\NoLocaleSelectedException();
		}
		return $this->locale;
	}


	/**
	 * @throws Exceptions\BadLocaleNameException
	 */
	public function setFallbackLocale(string $locale): self
	{
		$locale = strtolower($locale);
		$this->checkLocaleName($locale);

		$this->fallbackLocale = $locale;
		return $this;
	}


	/**
	 * translate(string $message, int|array|NULL $translateParameters = NULL, ?int $count = NULL): string
	 *   param string $message
	 *   param int|array|NULL $translateParameters (int = count; array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 *   param int|NULL $count
	 *
	 * @param mixed $message
	 * @param mixed ...$parameters
	 * @throws Exceptions\NoLocaleSelectedException
	 * @throws Exceptions\Exception
	 */
	public function translate($message, ...$parameters): string
	{
		$translationParams = $parameters[0] ?? NULL;

		if (is_array($translationParams) && isset($translationParams[self::PARAM_LOCALE])) {
			$locale = strtolower($translationParams[self::PARAM_LOCALE]);
		} else {
			$locale = $this->getLocale();
		}

		if (is_array($translationParams) && isset($translationParams[self::PARAM_COUNT])) {
			$count = intval($translationParams[self::PARAM_COUNT]);
		} else if (is_numeric($translationParams)) {
			$count = intval($translationParams);
			$translationParams = NULL;
		} else if (isset($parameters[1])) {
			$count = intval($parameters[1]);
		} else {
			$count = NULL;
		}

		try {
			$translate = $this->loadData($locale)->getTranslate($message, $count);
		} catch (Exceptions\Exception $e) {
			return $this->processTranslatorException($e, $message, $locale);
		}
		if ($translate === NULL) {
			if ($this->panel !== NULL) {
				$this->panel->addUntranslated($locale, $message);
			} else {
				$this->logger->log(sprintf('No translation for "%s" in locale "%s"', $message, $locale), 'translator');
			}

			if (($this->fallbackLocale !== NULL) && ($this->fallbackLocale !== $locale)) {
				try {
					$translate = $this->loadData($this->fallbackLocale)->getTranslate($message, $count);
				} catch (Exceptions\Exception $e) {
					return $this->processTranslatorException($e, $message, $this->fallbackLocale);
				}
			}

			if ($translate === NULL) {
				return $message;
			}
		}

		if (is_array($translationParams) && (count($translationParams) > 0)) {
			$tmp = [];
			foreach ($translationParams as $key => $value) {
				$tmp['%' . trim((string) $key, '%') . '%'] = $value;
			}
			$translationParams = $tmp;

			return strtr($translate, $translationParams);
		}

		return $translate;
	}


	public function createImmutableTranslator(string $locale): TranslatorImmutable
	{
		$locale = strtolower($locale);
		if (!isset($this->immutableTranslators[$locale])) {
			$this->immutableTranslators[$locale] = new TranslatorImmutable($this, $locale);
		}
		return $this->immutableTranslators[$locale];
	}


	/**
	 * @throws Exceptions\ClearCacheFailedException
	 */
	public function clearCache(string $locale): self
	{
		$localeCache = $this->getCacheFile($locale);
		if (file_exists($localeCache)) {
			if (!@unlink($localeCache)) {
				throw new Exceptions\ClearCacheFailedException();
			}
			if ($this->localeUtils !== NULL) {
				$this->localeUtils->afterCacheClear($locale, $localeCache);
			}
		}
		return $this;
	}


	public function setLocaleUtils(LocaleUtils $localeUtils): self
	{
		$this->localeUtils = $localeUtils;
		return $this;
	}


	public function setPanel(Diagnostics\Panel $panel): Diagnostics\Panel
	{
		$this->panel = $panel;
		return $panel;
	}


	/**
	 * @throws Exceptions\NoLocaleFileException
	 * @throws Exceptions\NoDataLoaderException
	 * @throws Exceptions\ParsingErrorException
	 * @throws Exceptions\SomeSectionMissingException
	 */
	private function loadData(string $locale): TranslatorData
	{
		if (!isset($this->data[$locale])) {
			$dataLoader = $this->getDataLoader();
			$source = $dataLoader->source($locale);
			$localeCache = $this->getCacheFile($locale);
			if ($this->cacheNeedsRebuild($localeCache, $locale)) {
				Utils\FileSystem::createDir(dirname($localeCache), 0755);

				$lockFile = $localeCache . '.lock';
				$lockHandle = fopen($lockFile, 'c+');
				if (($lockHandle === FALSE) || !flock($lockHandle, LOCK_EX)) {
					throw new Exceptions\CantAcquireLockException(sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $lockFile));
				}

				// cache still not exists
				if ($this->cacheNeedsRebuild($localeCache, $locale)) {
					$data = $dataLoader->loadData($locale);

					if (!array_key_exists('plural', $data) || !array_key_exists('messages', $data)) {
						throw new Exceptions\SomeSectionMissingException('You must have "plural" and "messages" section in data');
					}

					$pluralCondition = '';
					/** @var string $plural */
					foreach ($data['plural'] as $i => $plural) {
						$pluralCondition .= (($i > 0) ? 'else ' : '') . 'if (' . str_replace('n', '$count', $plural) . ') return ' . $i . ';';
					}

					$localeData = '';
					foreach ($data['messages'] as $identificator => $translate) {
						if (is_array($translate)) {
							$translateData = '';
							foreach ($translate as $translateItem) {
								$translateData .= '\'' . str_replace('\'', '\\\'', $translateItem) . '\',';
							}
							$translateData = '[' . $translateData . ']';
						} else {
							$translateData = '\'' . str_replace('\'', '\\\'', $translate) . '\'';
						}
						$localeData .= '\'' . $identificator . '\'=>' . $translateData . ',';
					}

					file_put_contents(
						$localeCache . '.tmp',
						sprintf(
							'<?php declare(strict_types=1); return new class(\'%s\', [%s]) extends Forrest79\SimpleTranslator\TranslatorData {protected function getPluralIndex(int $count): int {%sthrow new Forrest79\SimpleTranslator\Exceptions\TranslatorException(\'No definition for count \' . $count);}};',
							$locale,
							$localeData,
							$pluralCondition,
						),
					);
					rename($localeCache . '.tmp', $localeCache); // atomic replace (in Linux)
					if ($this->localeUtils !== NULL) {
						$this->localeUtils->afterCacheBuild($locale, $source, $localeCache);
					}
				}

				flock($lockHandle, LOCK_UN);
				fclose($lockHandle);
			}

			$this->data[$locale] = require $localeCache;

			if ($this->panel !== NULL) {
				$this->panel->addLocaleFile($locale, $source);
			}
		}

		return $this->data[$locale];
	}


	private function getCacheFile(string $locale): string
	{
		return $this->tempDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . $locale . '.php';
	}


	private function cacheNeedsRebuild(string $localeCache, string $locale): bool
	{
		return !file_exists($localeCache) || ($this->debugMode && $this->getDataLoader()->isLocaleUpdated($locale, $localeCache));
	}


	public function setDataLoader(DataLoader $dataLoader): self
	{
		$this->dataLoader = $dataLoader;
		return $this;
	}


	/**
	 * @throws Exceptions\NoDataLoaderException
	 */
	private function getDataLoader(): DataLoader
	{
		if ($this->dataLoader === NULL) {
			throw new Exceptions\NoDataLoaderException('You must set data loader via setDataLoader.');
		}

		return $this->dataLoader;
	}


	/**
	 * @throws Exceptions\BadLocaleNameException
	 */
	private function checkLocaleName(string $locale): void
	{
		if (preg_match('/^[a-z0-9_\-]+$/', $locale) === 0) {
			throw new Exceptions\BadLocaleNameException('Only "a-z", "0-9", "_" and "-" characters are allowed for locale name.');
		}
	}


	/**
	 * @throws Exceptions\Exception
	 */
	private function processTranslatorException(Exceptions\Exception $e, string $message, string $locale): string
	{
		if ($this->debugMode) {
			throw $e;
		} else {
			$this->logger->log(sprintf('Translation error "%s" in locale "%s"', $e->getMessage(), $locale), 'translator');
			return $message;
		}
	}

}
