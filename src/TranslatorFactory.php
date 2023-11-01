<?php declare(strict_types=1);

namespace Forrest79\Translation;

class TranslatorFactory
{
	private bool $debugMode;

	private string $tempDir;

	private CatalogueLoader $catalogueLoader;

	/** @var array<string, list<string>> */
	private array $fallbackLocales;

	private CatalogueUtils|NULL $catalogueUtils;

	private Logger|NULL $logger;

	private Catalogues|NULL $catalogues = NULL;

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
		CatalogueUtils|NULL $catalogueUtils = NULL,
		Logger|NULL $logger = NULL,
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
	 * @param list<string>|NULL $fallbackLocales
	 * @throws Exceptions\BadLocaleNameException
	 * @throws Exceptions\FallbackLocaleIsTheSameAsMainLocaleException
	 */
	public function create(string $locale, array|NULL $fallbackLocales = NULL): Translator
	{
		$fallbackLocales ??= $this->fallbackLocales[$locale] ?? [];
		$cacheKey = serialize([$locale, $fallbackLocales]);

		if (!isset($this->translators[$cacheKey])) {
			$translator = new Translator($this->debugMode, $this->createCatalogues(), $locale, $fallbackLocales);

			if ($this->logger !== NULL) {
				$translator->setLogger($this->logger);
			}

			$this->translators[$cacheKey] = $translator;
		}

		return $this->translators[$cacheKey];
	}


	private function createCatalogues(): Catalogues
	{
		if ($this->catalogues === NULL) {
			$this->catalogues = new Catalogues(
				$this->debugMode,
				$this->tempDir,
				$this->catalogueLoader,
				$this->catalogueUtils ?? (function_exists('opcache_invalidate') ? new CatalogueUtils\Opcache() : NULL),
			);

			if ($this->logger !== NULL) {
				$this->catalogues->setLogger($this->logger);
			}
		}

		return $this->catalogues;
	}

}
