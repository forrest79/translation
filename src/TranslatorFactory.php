<?php declare(strict_types=1);

namespace Forrest79\Translation;

class TranslatorFactory
{
	private bool $debugMode;

	private string $tempDir;

	private CatalogueLoader $catalogueLoader;

	/** @var array<string, list<string>> */
	private array $fallbackLocales;

	private CatalogueUtils|null $catalogueUtils;

	private Logger|null $logger;

	private Catalogues|null $catalogues = null;

	/** @var array<string, Translator> */
	private array $translators = [];


	/**
	 * @param array<string, list<string>> $fallbackLocales
	 */
	public function __construct(
		bool $debugMode,
		string $tempDir,
		CatalogueLoader $catalogueLoader,
		array $fallbackLocales = [],
		CatalogueUtils|null $catalogueUtils = null,
		Logger|null $logger = null,
	)
	{
		$this->debugMode = $debugMode;
		$this->tempDir = $tempDir;
		$this->catalogueLoader = $catalogueLoader;
		$this->fallbackLocales = $fallbackLocales;
		$this->catalogueUtils = $catalogueUtils;
		$this->logger = $logger;
	}


	/**
	 * @param list<string>|null $fallbackLocales
	 * @throws Exceptions\BadLocaleNameException
	 * @throws Exceptions\FallbackLocaleIsTheSameAsMainLocaleException
	 */
	public function create(string $locale, array|null $fallbackLocales = null): Translator
	{
		$fallbackLocales ??= $this->fallbackLocales[$locale] ?? [];
		$cacheKey = serialize([$locale, $fallbackLocales]);

		if (!isset($this->translators[$cacheKey])) {
			$translator = new Translator($this->debugMode, $this->createCatalogues(), $locale, $fallbackLocales);

			if ($this->logger !== null) {
				$translator->setLogger($this->logger);
			}

			$this->translators[$cacheKey] = $translator;
		}

		return $this->translators[$cacheKey];
	}


	private function createCatalogues(): Catalogues
	{
		if ($this->catalogues === null) {
			$this->catalogues = new Catalogues(
				$this->debugMode,
				$this->tempDir,
				$this->catalogueLoader,
				$this->catalogueUtils ?? (function_exists('opcache_invalidate') ? new CatalogueUtils\Opcache() : null),
			);

			if ($this->logger !== null) {
				$this->catalogues->setLogger($this->logger);
			}
		}

		return $this->catalogues;
	}

}
