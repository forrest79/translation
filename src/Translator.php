<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Nette\Utils;
use Tracy;

class Translator implements ITranslator
{
	/** @var bool */
	private $debugMode;

	/** @var DataLoader */
	private $dataLoader;

	/** @var string */
	private $tempDir;

	/** @var Tracy\ILogger */
	private $logger;

	/** @var string */
	private $locale;

	/** @var string */
	private $fallbackLocale;

	/** @var TranslatorData[] */
	private $data = [];

	/** @var LocaleUtils */
	private $localeUtils;

	/** @var Diagnostics\Panel */
	private $panel;


	public function __construct(bool $debugMode, string $tempDir, Tracy\ILogger $logger)
	{
		$this->debugMode = $debugMode;
		$this->tempDir = $tempDir;
		$this->logger = $logger;
	}


	/**
	 * @throws Exceptions\BadLocaleNameExceptions
	 */
	public function setLocale(string $locale): self
	{
		$locale = strtolower($locale);
		$this->checkLocaleName($locale);

		$this->locale = $locale;
		return $this;
	}


	/**
	 * @throws Exceptions\NoLocaleSelectedExceptions
	 */
	public function getLocale(): string
	{
		if ($this->locale === NULL) {
			throw new Exceptions\NoLocaleSelectedExceptions;
		}
		return $this->locale;
	}


	/**
	 * @throws Exceptions\BadLocaleNameExceptions
	 */
	public function setFallbackLocale(string $locale): self
	{
		$locale = strtolower($locale);
		$this->checkLocaleName($locale);

		$this->fallbackLocale = $locale;
		return $this;
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\NoLocaleSelectedExceptions
	 * @throws Exceptions\TranslatorException
	 */
	public function translate($message, $parameters = NULL, $count = NULL): string
	{
		if (is_array($parameters) && isset($parameters[self::PARAM_LOCALE])) {
			$locale = strtolower($parameters[self::PARAM_LOCALE]);
		} else {
			$locale = $this->getLocale();
		}

		if (is_array($parameters) && isset($parameters[self::PARAM_COUNT])) {
			$count = (int) $parameters[self::PARAM_COUNT];
		} else if (is_numeric($parameters)) {
			$count = (int) $parameters;
			$parameters = NULL;
		}

		try {
			$translate = $this->loadData($locale)->getTranslate($message, $count);
		} catch (Exceptions\TranslatorException $e) {
			return $this->processTranslatorException($e, $message, $locale);
		}
		if ($translate === NULL) {
			if ($this->panel) {
				$this->panel->addUntranslated($locale, $message);
			} else {
				$this->logger->log('No translation for "' . $message . '" in locale "' . $locale . '"', 'translator');
			}

			if (($this->fallbackLocale !== NULL) && ($this->fallbackLocale !== $locale)) {
				try {
					$translate = $this->loadData($this->fallbackLocale)->getTranslate($message, $count);
				} catch (Exceptions\TranslatorException $e) {
					return $this->processTranslatorException($e, $message, $this->fallbackLocale);
				}
			}

			if ($translate === NULL) {
				return $message;
			}
		}

		if (is_array($parameters) && $parameters) {
			$tmp = [];
			foreach ($parameters as $key => $value) {
				$tmp['%' . trim($key, '%') . '%'] = $value;
			}
			$parameters = $tmp;

			return strtr($translate, $parameters);
		}

		return $translate;
	}


	public function createImmutableTranslator(string $locale): TranslatorImmutable
	{
		return new TranslatorImmutable($this, $locale);
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
		}
		return $this;
	}


	public function setLocaleUtils(LocaleUtils $localeUtils): self
	{
		$this->localeUtils = $localeUtils;
		return $this;
	}


	public function getLocaleUtils(): LocaleUtils
	{
		return $this->localeUtils;
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
	 * @throws Exceptions\PluralSectionMissingException
	 */
	private function loadData(string $locale): TranslatorData
	{
		if (!isset($this->data[$locale])) {
			$dataLoader = $this->getDataLoader();
			$source = $dataLoader->source($locale);
			$localeCache = $this->getCacheFile($locale);
			if (!file_exists($localeCache) || ($this->debugMode && $dataLoader->isLocaleUpdated($locale, $localeCache))) {
				$data = $dataLoader->loadData($locale);

				if (!isset($data['plural']) || !isset($data['messages'])) {
					throw new Exceptions\PluralSectionMissingException('You must have "plural" and "messages" section in data');
				}

				$pluralCondition = '';
				foreach ($data['plural'] as $i => $plural) {
					$pluralCondition .= (($i > 0) ? 'else ' : '') . 'if (' . str_replace('n', '$count', $plural) . ') return ' . $i . ';';
				}

				$localeData = '';
				foreach ($data['messages'] as $identificator => $translate) {
					if (is_array($translate)) {
						$translateData = '';
						foreach ($translate as $translateItem) {
							$translateData .= '\'' . addslashes($translateItem) . '\',';
						}
						$translateData = '[' . $translateData . ']';
					} else {
						$translateData = '\'' . addslashes($translate) . '\'';
					}
					$localeData .= '\'' . $identificator . '\'=>' . $translateData . ',';
				}

				Utils\FileSystem::createDir(dirname($localeCache), 0755);
				file_put_contents($localeCache . '.tmp', '<?php declare(strict_types=1); class TranslatorData' . ucfirst($locale) . ' extends Forrest79\SimpleTranslator\TranslatorData {protected function getPluralIndex(int $count): int {' . $pluralCondition . 'throw new Forrest79\SimpleTranslator\TranslateException(\'No definition for count \' . $count);}}; return new TranslatorData' . ucfirst($this->locale) . '(\'' . $this->locale . '\', [' . $localeData . ']);');
				rename($localeCache . '.tmp', $localeCache); // atomic replace (in Linux)
				if ($this->localeUtils !== NULL) {
					$this->localeUtils->afterCacheBuild($locale, $source, $localeCache);
				}
			}

			$this->data[$locale] = require $localeCache;

			if ($this->panel) {
				$this->panel->addLocaleFile($locale, $source);
			}
		}

		return $this->data[$locale];
	}


	private function getCacheFile(string $locale): string
	{
		return $this->tempDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . $locale . '.php';
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
	 * @throws Exceptions\BadLocaleNameExceptions
	 */
	private function checkLocaleName(string $locale): void
	{
		if (!preg_match('/^[a-z0-9_\-]+$/', $locale)) {
			throw new Exceptions\BadLocaleNameExceptions('Only "a-z", "0-9", "_" and "-" characters are allowed for locale name.');
		}
	}


	/**
	 * @throws Exceptions\TranslatorException
	 */
	private function processTranslatorException(Exceptions\TranslatorException $e, string $message, string $locale): string
	{
		if ($this->debugMode) {
			throw $e;
		} else {
			$this->logger->log('Translation error "' . $e->getMessage() . '" in locale "' . $locale . '"', 'translator');
			return $message;
		}
	}

}
