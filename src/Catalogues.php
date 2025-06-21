<?php declare(strict_types=1);

namespace Forrest79\Translation;

class Catalogues
{
	private bool $debugMode;

	private string $tempDir;

	private CatalogueLoader $catalogueLoader;

	private CatalogueUtils|null $catalogueUtils;

	private Logger|null $logger = null;

	/** @var array<string, Catalogue> */
	private array $catalogues = [];


	public function __construct(
		bool $debugMode,
		string $tempDir,
		CatalogueLoader $catalogueLoader,
		CatalogueUtils|null $catalogueUtils = null,
	)
	{
		$this->debugMode = $debugMode;
		$this->tempDir = $tempDir;
		$this->catalogueLoader = $catalogueLoader;
		$this->catalogueUtils = $catalogueUtils;
	}


	public function setLogger(Logger $logger): static
	{
		$this->logger = $logger;

		return $this;
	}


	public function getTranslation(string $locale, string $message, int|null $count): string|null
	{
		return $this->loadCatalogue($locale)->getTranslation($message, $count);
	}


	/**
	 * @throws Exceptions\CantAcquireLockException
	 * @throws Exceptions\IOException
	 * @throws Exceptions\ParsingErrorException
	 * @throws Exceptions\MessagesSectionIsMissingException
	 */
	private function loadCatalogue(string $locale): Catalogue
	{
		if (!isset($this->catalogues[$locale])) {
			$source = $this->catalogueLoader->source($locale);
			$localeCache = $this->getCacheFile($locale);
			if (!file_exists($localeCache) || ($this->debugMode && $this->catalogueLoader->isLocaleUpdated($locale, $localeCache))) {
				$localeCacheDir = dirname($localeCache);
				if (!is_dir($localeCacheDir) && !@mkdir($localeCacheDir, 0755, true) && !is_dir($localeCacheDir)) { // intentionally @ - dir may already exist
					throw new Exceptions\IOException(sprintf('Unable to create directory \'%s\'.', $localeCacheDir));
				}

				$lockFile = $localeCache . '.lock';
				$lockHandle = fopen($lockFile, 'c+');
				if (($lockHandle === false) || !flock($lockHandle, LOCK_EX)) {
					throw new Exceptions\CantAcquireLockException(sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $lockFile));
				}

				// Another request can fill cache between before lock is reached and lock is released, so we need to check if cache still not exists to prevent double catalogue loading
				if (!file_exists($localeCache) || ($this->debugMode && $this->catalogueLoader->isLocaleUpdated($locale, $localeCache))) {
					$data = $this->catalogueLoader->loadData($locale);

					if (!array_key_exists('messages', $data)) {
						throw new Exceptions\MessagesSectionIsMissingException('You must have "messages" section in data');
					}

					if (!array_key_exists('plural', $data)) {
						$data['plural'] = PluralsHelper::getPluralizationRule($locale) ?? '';
					}

					$pluralCondition = $data['plural'];
					assert(is_string($pluralCondition));
					if (!str_contains($pluralCondition, '$count')) {
						throw new Exceptions\NoCountDefinitionException(sprintf('Plural condition \'%s\' must contain at least one \'$count\' variable.', $pluralCondition));
					}

					file_put_contents(
						$localeCache . '.tmp',
						sprintf(
							'<?php declare(strict_types=1); return new class(\'%s\', %s) extends Forrest79\Translation\Catalogue {protected function getPluralIndex(int $count): int {return %s;throw new Forrest79\Translation\Exceptions\NoCountDefinitionException(\'No definition for count \' . $count);}};',
							$locale,
							var_export($data['messages'] ?? [], true),
							$pluralCondition,
						),
					);
					rename($localeCache . '.tmp', $localeCache); // atomic replace (in Linux)

					$this->catalogueUtils?->afterCacheBuild($locale, $source, $localeCache);
				}

				flock($lockHandle, LOCK_UN);
				fclose($lockHandle);
			}

			$catalogue = require $localeCache;
			assert($catalogue instanceof Catalogue);

			$this->catalogues[$locale] = $catalogue;

			$this->logger?->addLocaleFile($locale, $source);
		}

		return $this->catalogues[$locale];
	}


	public function clearCache(string $locale): static
	{
		$localeCache = $this->getCacheFile($locale);
		if (file_exists($localeCache)) {
			!@unlink($localeCache); // intentionally @ - file may not exists

			$this->catalogueUtils?->afterCacheClear($locale, $localeCache);
		}
		return $this;
	}


	private function getCacheFile(string $locale): string
	{
		return $this->tempDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . $locale . '.php';
	}

}
