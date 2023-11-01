<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoader;
use Forrest79\Translation\CatalogueUtils;
use Forrest79\Translation\Catalogues;
use Forrest79\Translation\Exceptions;
use Forrest79\Translation\Logger;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class CataloguesTest extends TestCase
{
	private const SIMPLE_BLANK_CACHE_FILE = '<?php return new class(\'en\', []) extends Forrest79\Translation\Catalogue {protected function getPluralIndex(int $count): int {return -1;}};';

	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testBuildAndClearCache(): void
	{
		$cacheFile = $this->getCacheFile('en');
		$catalogueUtils = self::createCatalogueUtils();
		$logger = self::createLogger();
		$catalogues = $this->createCatalogues(debugMode: TRUE, catalogueUtils: $catalogueUtils);
		$catalogues->setLogger($logger);

		Assert::false(file_exists($cacheFile));

		Assert::same([], $catalogueUtils->getAfterCacheBuild());
		Assert::same([], $logger->getLocaleFiles());

		// this should generate cache file
		$catalogues->getTranslation('en', 'test_message', NULL);

		$cacheFileReal = realpath($cacheFile);

		Assert::same([['en', 'test_en', $cacheFileReal]], $catalogueUtils->getAfterCacheBuild());
		Assert::same([['en', 'test_en']], $logger->getLocaleFiles());

		Assert::true(file_exists($cacheFile));

		// clear cache

		Assert::same([], $catalogueUtils->getAfterCacheClear());

		$catalogues->clearCache('en');

		Assert::same([['en', $cacheFileReal]], $catalogueUtils->getAfterCacheClear());

		Assert::false(file_exists($cacheFile));

		// try another translation - no new after cache is build and locale file (it sould be in the new request)

		Assert::same([['en', 'test_en', $cacheFileReal]], $catalogueUtils->getAfterCacheBuild());
		Assert::same([['en', 'test_en']], $logger->getLocaleFiles());
	}


	public function testRebuildCacheInDebugMode(): void
	{
		$cacheFile = $this->getCacheFile('en');
		$catalogueLoader = self::createCatalogueLoader();
		$catalogues = $this->createCatalogues(debugMode: TRUE, catalogueLoader: $catalogueLoader);

		mkdir(dirname($cacheFile), 0777, TRUE);
		touch($cacheFile);

		Assert::true(filesize($cacheFile) === 0);

		// this should now re-generate cache file

		$catalogueLoader->setIsLocaleUpdated(TRUE);
		$catalogues->getTranslation('en', 'test_message', NULL);

		Assert::true(filesize($cacheFile) > 0);
	}


	public function testNotRebuildCacheInDebugMode(): void
	{
		$cacheFile = $this->getCacheFile('en');

		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => 'test-translation']]);

		$catalogues = $this->createCatalogues(debugMode: TRUE, catalogueLoader: $catalogueLoader);

		mkdir(dirname($cacheFile), 0777, TRUE);
		file_put_contents($cacheFile, self::SIMPLE_BLANK_CACHE_FILE);

		$size = filesize($cacheFile);

		// this should not re-generate cache file

		$catalogues->getTranslation('en', 'test_message', NULL);

		Assert::same($size, filesize($cacheFile));
	}


	public function testNotRebuildCacheInProductionMode(): void
	{
		$cacheFile = $this->getCacheFile('en');

		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => 'test-translation']]);

		$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

		mkdir(dirname($cacheFile), 0777, TRUE);
		file_put_contents($cacheFile, self::SIMPLE_BLANK_CACHE_FILE);

		$size = filesize($cacheFile);

		// this should not re-generate cache file

		$catalogueLoader->setIsLocaleUpdated(TRUE);
		$catalogues->getTranslation('en', 'test_message', NULL);

		Assert::same($size, filesize($cacheFile));
	}


	public function testMissingMessagesSection(): void
	{
		Assert::exception(function (): void {
			$catalogueLoader = self::createCatalogueLoader();
			$catalogueLoader->setData(['messagesX' => ['test_identifier' => 'test-translation']]);

			$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

			$catalogues->getTranslation('en', 'test_message', NULL);
		}, Exceptions\MessagesSectionIsMissingException::class);
	}


	public function testMissingCountVariableInPlural(): void
	{
		Assert::exception(function (): void {
			$catalogueLoader = self::createCatalogueLoader();
			$catalogueLoader->setData(['messages' => ['test_identifier' => 'test-translation'], 'plural' => '($i === 1) ? 0 : 1']);

			$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

			$catalogues->getTranslation('en', 'test_message', NULL);
		}, Exceptions\NoCountDefinitionException::class);
	}


	public function testSimpleTranslation(): void
	{
		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => 'test-translation']]);

		$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

		Assert::same('test-translation', $catalogues->getTranslation('en', 'test_identifier', NULL));
	}


	public function testTranslationWithSimpleQuotes(): void
	{
		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => '\'test\'-translation', 'test_identifier_pl' => ['\'test\'-translation1', '\'test\'-translation2']]]);

		$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

		Assert::same('\'test\'-translation', $catalogues->getTranslation('en', 'test_identifier', NULL));
		Assert::same('\'test\'-translation1', $catalogues->getTranslation('en', 'test_identifier_pl', 1));
		Assert::same('\'test\'-translation2', $catalogues->getTranslation('en', 'test_identifier_pl', 2));
	}


	public function testCatalogueWithCustomPlural(): void
	{
		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => ['0', '1']], 'plural' => '($count === 100) ? 1 : 0']);

		$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

		Assert::same('0', $catalogues->getTranslation('en', 'test_identifier', 0));
		Assert::same('0', $catalogues->getTranslation('en', 'test_identifier', 1));
		Assert::same('0', $catalogues->getTranslation('en', 'test_identifier', 10));
		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 100));
		Assert::same('0', $catalogues->getTranslation('en', 'test_identifier', 1000));
	}


	public function testCatalogueWithEnPlural(): void
	{
		$catalogueLoader = self::createCatalogueLoader();
		$catalogueLoader->setData(['messages' => ['test_identifier' => ['0', '1']]]);

		$catalogues = $this->createCatalogues(catalogueLoader: $catalogueLoader);

		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 0));
		Assert::same('0', $catalogues->getTranslation('en', 'test_identifier', 1));
		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 2));
		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 10));
		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 100));
		Assert::same('1', $catalogues->getTranslation('en', 'test_identifier', 1000));
	}


	private function createCatalogues(
		bool $debugMode = FALSE,
		CatalogueLoader|NULL $catalogueLoader = NULL,
		CatalogueUtils|NULL $catalogueUtils = NULL,
	): Catalogues
	{
		return new Catalogues(
			$debugMode,
			$this->tempDir,
			$catalogueLoader ?? self::createCatalogueLoader(),
			$catalogueUtils,
		);
	}


	private static function createCatalogueLoader(): CatalogueLoader
	{
		return new class implements CatalogueLoader {
			private bool $isLocaleUpdated = FALSE;

			/** @var array<string, string|array<string, string|list<string>>> */
			private array $data = ['messages' => []];


			public function isLocaleUpdated(string $locale, string $cacheFile): bool
			{
				return $this->isLocaleUpdated;
			}


			public function setIsLocaleUpdated(bool $isLocaleUpdated): void
			{
				$this->isLocaleUpdated = $isLocaleUpdated;
			}


			/**
			 * @return array<string, string|array<string, string|list<string>>|NULL>
			 */
			public function loadData(string $locale): array
			{
				return $this->data;
			}


			/**
			 * @param array<string, string|array<string, string|list<string>>> $data
			 */
			public function setData(array $data): void
			{
				$this->data = $data;
			}


			public function source(string $locale): string
			{
				return 'test_' . $locale;
			}

		};
	}


	private static function createCatalogueUtils(): CatalogueUtils
	{
		return new class implements CatalogueUtils {
			/** @var list<array{0: string, 1: string, 2: string}> */
			private array $afterCacheBuild = [];

			/** @var list<array{0: string, 1: string}> */
			private array $afterCacheClear = [];


			public function afterCacheBuild(string $locale, string $source, string $localeCache): void
			{
				$this->afterCacheBuild[] = [$locale, $source, $localeCache];
			}


			/**
			 * @return list<array{0: string, 1: string, 2: string}>
			 */
			public function getAfterCacheBuild(): array
			{
				return $this->afterCacheBuild;
			}


			public function afterCacheClear(string $locale, string $localeCache): void
			{
				$this->afterCacheClear[] = [$locale, $localeCache];
			}


			/**
			 * @return list<array{0: string, 1: string}>
			 */
			public function getAfterCacheClear(): array
			{
				return $this->afterCacheClear;
			}

		};
	}


	private static function createLogger(): Logger
	{
		return new class implements Logger {
			/** @var list<array{0: string, 1: string}> */
			private array $localeFiles = [];


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
				$this->localeFiles[] = [$locale, $source];
			}


			/**
			 * @return list<array{0: string, 1: string}>
			 */
			public function getLocaleFiles(): array
			{
				return $this->localeFiles;
			}

		};
	}


	private function getCacheFile(string $locale): string
	{
		return sprintf('%s/cache/locales/%s.php', $this->tempDir, $locale);
	}

}

(new CataloguesTest())->run();
