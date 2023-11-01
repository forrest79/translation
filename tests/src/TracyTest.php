<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoader;
use Forrest79\Translation\Catalogues;
use Forrest79\Translation\Logger;
use Forrest79\Translation\Loggers;
use Forrest79\Translation\Tests;
use Forrest79\Translation\Translator;
use Tester\Assert;
use Tracy\ILogger;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class TracyTest extends Tests\TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testLogger(): void
	{
		$tracyLogger = self::createTracyLogger();
		$translator = $this->createTranslator(self::createLogger($tracyLogger));

		Assert::same('non_existing_identifier', $translator->translate('non_existing_identifier'));
		Assert::same('existing_singular_message', $translator->translate('existing_singular_message', count: 2));
		Assert::same([
			[
				'No translation for "non_existing_identifier" in locale "en"',
				'translator',
			],
			[
				'Translation error [Message "existing_singular_message" in "en" is not plural] in locale "en"',
				'translator',
			],
		], $tracyLogger->getMessages());
	}


	public function testDebugPanel(): void
	{
		$logger = self::createBarPanel();
		$translator = $this->createTranslator($logger);

		Assert::same('non_existing_identifier', $translator->translate('non_existing_identifier'));
		Assert::same('non_existing_identifier', $translator->translate('non_existing_identifier')); // we want info about missing identifier just once
		Assert::same('another_non_existing_identifier', $translator->translate('another_non_existing_identifier'));

		Assert::same('existing_singular_message', $translator->translate('existing_singular_message', count: 2)); // on production, this will add error that is ignored in TracyBarPanel

		Assert::same('<span title="Translation"><img width="16" height="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB9ElEQVR42qWTv2pUQRTGfzP37t3EJTHZpDBIIIIgYhVQQdKJEXwECxsbG8FXsPcBxMaUEsQHSCmi2AVfQAvBGHYxf3Tv7r0zcz6Lm2QTE0FwimHm8M2P8505B/5zucPD3dXVh5+NR08KGz4uYvgSBDLMDMyQhJm1JJuU2YurbzdfAuSHAMF9xE0dXJqgaHuPAVVMR0rBPnASgAQ4ZAamI8j3UUWOmMo8SWpkGlvw4/dq4hKyRO7EMCW+lkO2hjVmasQCd4zgT5ZESIYsITN2qppunlM4x16IZAcGjL9loMaJA8qYKENkcaKgW2TshECUNYKzLIxBhpNRxkCdjH4dGIRIHROjmMj+0Ptjv9DsEpaM/TqQORjESGUJAb9Cwjt3NqApL2TAXh3ojQLzRcbSZMGlyYJO5vlW11TJyMftc+wbD/rKMCRjoZ3TcY46JryDbp7hgSjhnZ0GmMHIPFWCGQUWnaMcViRBBNrARWAUI6HVOisDw6UalycGrQ6D4hxFq0UVAkJEoJZDyXCjcucU4MI8PL3Xp7e1y3rxAHWXuXZlgfm5OYajITKR2hNYv4dfe77Bu82TgNpYW1meurN92fP62Zv1n+9ffdRSMXNj9jxtIMjIzHlnmlaKP05N4/bGrevTHVYmpn292y8/zN7e/PQv4/wbPRMfZ4n8tOQAAAAASUVORK5CYII="><span class="tracy-label">en, cs <strong>(2 errors)</strong></span></span>', $logger->getTab());
		Assert::same('<h1>Missing translations: 2</h1><div class="nette-inner tracy-inner translator-panel" style="min-width:500px"><table style="width:100%"><tr><th>Untranslated messages (en)</th></tr><tr><td>non_existing_identifier</td></tr><tr><td>another_non_existing_identifier</td></tr></table><br><br><h2>Loaded locale sources</h2><table style="width:100%"><tr><th>Locale</th><th>Data source</th></tr><tr><td>en</td><td>test_en</td></tr><tr><td>cs</td><td>test_cs</td></tr></table></div><style>#tracy-debug .translator-panel h2 {font-size: 23px;}</style>', $logger->getPanel());
	}


	private function createTranslator(Logger $logger): Translator
	{
		return (new Translator(FALSE, self::createCatalogues($logger), 'en', ['cs']))->setLogger($logger);
	}


	private function createCatalogues(Logger $logger): Catalogues
	{
		return (new Catalogues(FALSE, $this->tempDir, self::createCatalogueLoader()))->setLogger($logger);
	}


	private static function createCatalogueLoader(): CatalogueLoader
	{
		return new class() implements CatalogueLoader {

			public function isLocaleUpdated(string $locale, string $cacheFile): bool
			{
				return FALSE;
			}


			/**
			 * @return array<string, string|array<string, string|list<string>>|NULL>
			 */
			public function loadData(string $locale): array
			{
				return ['messages' => ['existing_singular_message' => 'with some translation']];
			}


			public function source(string $locale): string
			{
				return 'test_' . $locale;
			}

		};
	}


	private static function createTracyLogger(): ILogger
	{
		return new class implements ILogger {
			/** @var list<array{0: mixed, 1: string}> */
			private array $messages;


			public function log(mixed $value, string $level = self::INFO): void
			{
				$this->messages[] = [$value, $level];
			}


			/**
			 * @return list<array{0: mixed, 1: string}>
			 */
			public function getMessages(): array
			{
				return $this->messages;
			}

		};
	}


	private static function createLogger(ILogger $tracyLogger): Loggers\TracyLogger
	{
		return new Loggers\TracyLogger($tracyLogger);
	}


	private static function createBarPanel(): Loggers\TracyBarPanel
	{
		return Loggers\TracyBarPanel::register();
	}

}

(new TracyTest())->run();
