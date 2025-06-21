<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoader;
use Forrest79\Translation\CatalogueUtils;
use Forrest79\Translation\Catalogues;
use Forrest79\Translation\Logger;
use Forrest79\Translation\TranslatorFactory;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class TranslatorFactoryTest extends TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testBasic(): void
	{
		$translatorFactory = $this->createTranslatorFactory(fallbackLocales: ['cs' => ['en'], 'en' => ['cs']]);

		$translatorCs = $translatorFactory->create('cs');
		$translatorEn = $translatorFactory->create('en');
		$translatorSk = $translatorFactory->create('sk');

		Assert::same('cs', $translatorCs->getLocale());
		Assert::same(['en'], $translatorCs->getFallbackLocales());
		Assert::same('en', $translatorEn->getLocale());
		Assert::same(['cs'], $translatorEn->getFallbackLocales());
		Assert::same('sk', $translatorSk->getLocale());
		Assert::same([], $translatorSk->getFallbackLocales());

		$translatorPl = $translatorFactory->create('pl', ['sk', 'cs', 'en']);
		Assert::same('pl', $translatorPl->getLocale());
		Assert::same(['sk', 'cs', 'en'], $translatorPl->getFallbackLocales());

		$translatorCs2 = $translatorFactory->create('cs');
		Assert::true($translatorCs === $translatorCs2);

		$translatorCsWithTheSameFallback = $translatorFactory->create('cs', ['en']);
		Assert::true($translatorCs === $translatorCsWithTheSameFallback);

		$translatorCsWithTheDifferentFallback = $translatorFactory->create('cs', ['sk']);
		Assert::false($translatorCs === $translatorCsWithTheDifferentFallback);
		Assert::same('cs', $translatorCsWithTheDifferentFallback->getLocale());
		Assert::same(['sk'], $translatorCsWithTheDifferentFallback->getFallbackLocales());
	}


	public function testWithoutLogger(): void
	{
		$translatorFactory = $this->createTranslatorFactory();
		$translator = $translatorFactory->create('en');

		$logger = (fn (): Logger|null => $this->logger)->call($translator);
		Assert::null($logger);
	}


	public function testWithLogger(): void
	{
		$translatorFactory = $this->createTranslatorFactory(withLogger: true);
		$translator = $translatorFactory->create('en');

		$logger = (fn (): Logger|null => $this->logger)->call($translator);
		Assert::true($logger instanceof Logger);
	}


	public function testWithoutOpcacheCatalogueUtils(): void
	{
		$translatorFactory = $this->createTranslatorFactory();
		$translator = $translatorFactory->create('en');

		$catalogues = (fn (): Catalogues => $this->catalogues)->call($translator);
		$catalogueUtils = (fn (): CatalogueUtils|null => $this->catalogueUtils)->call($catalogues);
		Assert::null($catalogueUtils);
	}


	public function testWithtOpcacheCatalogueUtils(): void
	{
		eval('function opcache_invalidate() {};');

		$translatorFactory = $this->createTranslatorFactory();
		$translator = $translatorFactory->create('en');

		$catalogues = (fn (): Catalogues => $this->catalogues)->call($translator);
		$catalogueUtils = (fn (): CatalogueUtils|null => $this->catalogueUtils)->call($catalogues);
		Assert::true($catalogueUtils instanceof CatalogueUtils\Opcache);
	}


	/**
	 * @param array<string, list<string>> $fallbackLocales
	 */
	private function createTranslatorFactory(bool $withLogger = false, array $fallbackLocales = []): TranslatorFactory
	{
		return new TranslatorFactory(
			true,
			$this->tempDir,
			self::createCatalogueLoader(),
			$fallbackLocales,
			logger: $withLogger ? self::createLogger() : null,
		);
	}


	private static function createCatalogueLoader(): CatalogueLoader
	{
		return new class() implements CatalogueLoader {

			public function isLocaleUpdated(string $locale, string $cacheFile): bool
			{
				return false;
			}


			/**
			 * @return array<string, string|array<string, string|list<string>>|null>
			 */
			public function loadData(string $locale): array
			{
				return ['messages' => []];
			}


			public function source(string $locale): string
			{
				return 'test_' . $locale;
			}

		};
	}


	private static function createLogger(): Logger
	{
		return new class implements Logger {

			public function addUntranslated(string $locale, string $message): void
			{
				// nothing important in this test
			}


			public function addError(string $locale, string $error): void
			{
				// nothing important in this test
			}


			public function addLocaleFile(string $locale, string $source): void
			{
				// nothing important in this test
			}

		};
	}

}

(new TranslatorFactoryTest())->run();
