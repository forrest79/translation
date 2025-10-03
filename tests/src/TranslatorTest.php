<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoader;
use Forrest79\Translation\Catalogues;
use Forrest79\Translation\Exceptions;
use Forrest79\Translation\Logger;
use Forrest79\Translation\Translator;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class TranslatorTest extends TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testBasic(): void
	{
		$translator = self::createTranslator(
			'en',
			['cs'],
			localeMessages: [
				'en' => ['simple_identifier' => 'Simple Translation'],
				'cs' => ['simple_fallback_identifier' => 'Simple Fallback Translation'],
			],
		);

		Assert::same('en', $translator->getLocale());
		Assert::same(['cs'], $translator->getFallbackLocales());

		Assert::same('Simple Translation', $translator->translate('simple_identifier'));
		Assert::same('Simple Fallback Translation', $translator->translate('simple_fallback_identifier'));
		Assert::same('simple_non_existing_identifier', $translator->translate('simple_non_existing_identifier'));
	}


	public function testPlural(): void
	{
		$translator = self::createTranslator(
			'en',
			['cs'],
			localeMessages: [
				'en' => ['identifier_pl' => ['Translation', 'Plural translation']],
				'cs' => ['fallback_identifier_pl' => ['Fallback Translation 1', 'Fallback Translation 2', 'Fallback Translation 3']],
			],
		);

		Assert::same('Translation', $translator->translate('identifier_pl', count: 1));
		Assert::same('Plural translation', $translator->translate('identifier_pl', count: 2));
		Assert::same('Plural translation', $translator->translate('identifier_pl', count: 10));
		Assert::same('Fallback Translation 1', $translator->translate('fallback_identifier_pl', count: 1));
		Assert::same('Fallback Translation 2', $translator->translate('fallback_identifier_pl', count: 2));
		Assert::same('Fallback Translation 2', $translator->translate('fallback_identifier_pl', count: 3));
		Assert::same('Fallback Translation 2', $translator->translate('fallback_identifier_pl', count: 4));
		Assert::same('Fallback Translation 3', $translator->translate('fallback_identifier_pl', count: 5));
		Assert::same('non_existing_plural_identifier', $translator->translate('non_existing_plural_identifier', count: 100));
	}


	public function testParameters(): void
	{
		$translator = self::createTranslator(
			'en',
			['cs'],
			localeMessages: [
				'en' => ['param_identifier' => 'Translation With %param%'],
				'cs' => ['param_fallback_identifier' => 'Fallback Translation With %param%'],
			],
		);

		Assert::same('Translation With Parameter', $translator->translate('param_identifier', ['param' => 'Parameter']));
		Assert::same('Fallback Translation With Parameter', $translator->translate('param_fallback_identifier', ['param' => 'Parameter']));
		Assert::same('param_non_existing_identifier', $translator->translate('param_non_existing_identifier', ['param' => 'Parameter']));
	}


	public function testPluralWithParameters(): void
	{
		$translator = self::createTranslator(
			'en',
			['cs'],
			localeMessages: [
				'en' => ['param_identifier_pl' => ['Translation With %param%', 'Plural Translation With %param%']],
				'cs' => ['param_fallback_identifier_pl' => ['Plural Translation With %param% 1', 'Plural Translation With %param% 2', 'Plural Translation With %param% 3']],
			],
		);

		Assert::same('en', $translator->getLocale());
		Assert::same(['cs'], $translator->getFallbackLocales());

		Assert::same('Translation With Parameter', $translator->translate('param_identifier_pl', ['param' => 'Parameter'], 1));
		Assert::same('Plural Translation With Parameter', $translator->translate('param_identifier_pl', ['param' => 'Parameter'], 2));
		Assert::same('Plural Translation With Parameter', $translator->translate('param_identifier_pl', ['param' => 'Parameter'], 10));
		Assert::same('Plural Translation With Parameter 1', $translator->translate('param_fallback_identifier_pl', ['param' => 'Parameter'], 1));
		Assert::same('Plural Translation With Parameter 2', $translator->translate('param_fallback_identifier_pl', ['param' => 'Parameter'], 2));
		Assert::same('Plural Translation With Parameter 2', $translator->translate('param_fallback_identifier_pl', ['param' => 'Parameter'], 3));
		Assert::same('Plural Translation With Parameter 2', $translator->translate('param_fallback_identifier_pl', ['param' => 'Parameter'], 4));
		Assert::same('Plural Translation With Parameter 3', $translator->translate('param_fallback_identifier_pl', ['param' => 'Parameter'], 5));
		Assert::same('param_non_existing_plural_identifier', $translator->translate('param_non_existing_plural_identifier', ['param' => 'Parameter'], 100));
	}


	public function testProcessExceptionInDebugMode(): void
	{
		Assert::exception(function (): void {
			$this->createTranslator(debugMode: true, localeMessages: ['en' => ['test_identifier' => 'Test translation']])
				->translate('test_identifier', count: 1);
		}, Exceptions\NotPluralMessageException::class);
	}


	public function testProcessExceptionInProductionMode(): void
	{
		$translator = self::createTranslator(localeMessages: ['en' => ['test_identifier' => 'Test translation']]);
		Assert::same('test_identifier', $translator->translate('test_identifier', count: 1));
	}


	public function testLoggerInProductionMode(): void
	{
		$logger = self::createLogger();
		$translator = self::createTranslator(localeMessages: ['en' => ['test_identifier' => 'Test translation']]);
		$translator->setLogger($logger);

		Assert::same([], $logger->getUntranslated());
		Assert::same([], $logger->getErrors());

		Assert::same('Test translation', $translator->translate('test_identifier'));

		Assert::same([], $logger->getUntranslated());
		Assert::same([], $logger->getErrors());

		Assert::same('test_non_existing_identifier', $translator->translate('test_non_existing_identifier'));

		Assert::same([
			[
				'en',
				'test_non_existing_identifier',
			],
		], $logger->getUntranslated());
		Assert::same([], $logger->getErrors());

		Assert::same('test_identifier', $translator->translate('test_identifier', count: 1));

		Assert::same([
			[
				'en',
				'test_non_existing_identifier',
			],
		], $logger->getUntranslated());
		Assert::same([
			[
				'en',
				'Message "test_identifier" in "en" is not plural',
			],
		], $logger->getErrors());
	}


	public function testBadLocaleName(): void
	{
		Assert::exception(function (): void {
			$this->createTranslator('en?');
		}, Exceptions\BadLocaleNameException::class);

		Assert::exception(function (): void {
			$this->createTranslator('en', ['cs', 'en?']);
		}, Exceptions\BadLocaleNameException::class);
	}


	public function testLocaleNameIsTheSameAsFallbackLocale(): void
	{
		Assert::exception(function (): void {
			$this->createTranslator('en', ['cs', 'en']);
		}, Exceptions\FallbackLocaleIsTheSameAsMainLocaleException::class);
	}


	public function testClearCache(): void
	{
		$translator = self::createTranslator(
			localeMessages: [
				'en' => ['simple_identifier' => 'Simple Translation'],
			],
		);

		$cacheFile = $this->tempDir . '/cache/locales/en.php';

		Assert::false(file_exists($cacheFile));

		Assert::same('Simple Translation', $translator->translate('simple_identifier'));

		Assert::true(file_exists($cacheFile));

		$translator->clearCache();

		Assert::false(file_exists($cacheFile));
	}


	/**
	 * @param list<string> $fallbackLocales
	 * @param array<string, array<string, string|list<string>>> $localeMessages
	 */
	private function createTranslator(
		string $locale = 'en',
		array $fallbackLocales = [],
		bool $debugMode = false,
		array $localeMessages = [],
	): Translator
	{
		return new Translator($debugMode, self::createCatalogues($debugMode, $localeMessages), $locale, $fallbackLocales);
	}


	/**
	 * @param array<string, array<string, string|list<string>>> $localeMessages
	 */
	private function createCatalogues(bool $debugMode = false, array $localeMessages = []): Catalogues
	{
		return new Catalogues($debugMode, $this->tempDir, self::createCatalogueLoader($localeMessages));
	}


	/**
	 * @param array<string, array<string, string|list<string>>> $localeMessages
	 */
	private static function createCatalogueLoader(array $localeMessages = []): CatalogueLoader
	{
		return new class($localeMessages) implements CatalogueLoader {
			/** @var array<string, array<string, string|list<string>>> */
			private array $localeMessages;


			/**
			 * @param array<string, array<string, string|list<string>>> $localeMessages
			 */
			public function __construct(array $localeMessages)
			{
				$this->localeMessages = $localeMessages;
			}


			public function isLocaleUpdated(string $locale, string $cacheFile): bool
			{
				return false;
			}


			/**
			 * @return array<string, string|array<string, string|list<string>>|null>
			 */
			public function loadData(string $locale): array
			{
				return ['messages' => $this->localeMessages[$locale] ?? []];
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
			/** @var list<array{0: string, 1: string}> */
			private array $untranslated = [];

			/** @var list<array{0: string, 1: string}> */
			private array $errors = [];


			public function addUntranslated(string $locale, string $message): void
			{
				$this->untranslated[] = [$locale, $message];
			}


			/**
			 * @return list<array{0: string, 1: string}>
			 */
			public function getUntranslated(): array
			{
				return $this->untranslated;
			}


			public function addError(string $locale, string $error): void
			{
				$this->errors[] = [$locale, $error];
			}


			/**
			 * @return list<array{0: string, 1: string}>
			 */
			public function getErrors(): array
			{
				return $this->errors;
			}


			public function addLocaleFile(string $locale, string $source): void
			{
				// nothing important in this test
			}

		};
	}

}

(new TranslatorTest())->run();
