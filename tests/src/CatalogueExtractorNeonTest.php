<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueExtractors;
use Forrest79\Translation\MessageExtractors;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class CatalogueExtractorNeonTest extends TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testExtractPhpFile(): void
	{
		$neonLocaleFile = $this->tempDir . DIRECTORY_SEPARATOR . 'en.neon';

		file_put_contents($neonLocaleFile, 'messages:' . PHP_EOL);

		$phpFile = $this->tempDir . DIRECTORY_SEPARATOR . 'extract.php';

		$class = <<<'PHP'
			<?php
			class Translations extends Translateble {
			
				public function test(string $identifier): void
				{
					$this->translator->translate('sample_identifier');
					$this->translator->translate("sample_identifier_with_param", ['param' => 1]);
					$this->translator->translate('sample_identifier_plural', count: 3);
					$this->translator->translate($identifier, ['param' => 2], count: 3);
				}
			
			}		
			PHP;

		file_put_contents($phpFile, $class);

		$neonExtractor = $this->createNeonExtractor();
		$neonExtractor->extract();

		Assert::same([
			sprintf('Extracting: %s/extract.php', $this->tempDir),
			'Processing locale for [en]',
		], $neonExtractor->getLoggedMessages());

		$neon = <<<'NEON'
			messages:

				sample_identifier: ''
				sample_identifier_plural: ''
				sample_identifier_with_param: ''

			NEON;

		Assert::same($neon, file_get_contents($neonLocaleFile));

		// updates

		$updatedNeon = <<<'NEON'
			messages:

				sample_identifier: ''
				# Comment to keep
				sample_identifier_plural: ['Here is some plural tranlsation', 'Here are some plural translations']
				sample_identifier_with_param: ''

			NEON;

		file_put_contents($neonLocaleFile, $updatedNeon);

		$updatedClass = <<<'PHP'
			<?php
			class Translations extends Translateble {
			
				public function test(string $identifier): void
				{
					$this->translator->translate('sample_identifier');
					$this->translator->translate('sample_identifier_plural', count: 3);
					$this->translator->translate($identifier, ['param' => 2], count: 3);
					$this->translator->translate('new_sample_identifier');
				}
			
			}		
			PHP;

		file_put_contents($phpFile, $updatedClass);

		$neonExtractor->extract();

		$neon2 = <<<'NEON'
			messages:
			
				sample_identifier: ''
				# Comment to keep
				sample_identifier_plural: ['Here is some plural tranlsation', 'Here are some plural translations']
				new_sample_identifier: ''

			NEON;

		Assert::same($neon2, file_get_contents($neonLocaleFile));
	}


	public function testExceptions(): void
	{
		Assert::exception(static function (): void {
			new CatalogueExtractors\Neon('/locales', [], [], []);
		}, \InvalidArgumentException::class, 'You must provide at least one locale.');

		Assert::exception(static function (): void {
			new CatalogueExtractors\Neon('/locales', ['en'], [], []);
		}, \InvalidArgumentException::class, 'You must provide at least one source directory.');

		Assert::exception(static function (): void {
			new CatalogueExtractors\Neon('/locales', ['en'], ['/app-source'], []);
		}, \InvalidArgumentException::class, 'You must provide at least one extractor.');

		$neonLocaleFile = $this->tempDir . DIRECTORY_SEPARATOR . 'en.neon';

		file_put_contents($neonLocaleFile, '');
		Assert::exception(function (): void {
			$this->createNeonExtractor()->extract();
		}, \RuntimeException::class, sprintf('Array is expected in locale neon file \'%s\'.', $neonLocaleFile));

		file_put_contents($neonLocaleFile, 'message:' . PHP_EOL);
		Assert::exception(function (): void {
			$this->createNeonExtractor()->extract();
		}, \RuntimeException::class, sprintf('Locale file \'%s\' has no messages section.', $neonLocaleFile));
	}


	private function createNeonExtractor(): CatalogueExtractors\Neon
	{
		return new class($this->tempDir, ['en'], [$this->tempDir], [new MessageExtractors\Php()]) extends CatalogueExtractors\Neon {
			/** @var list<string> */
			private array $messages = [];


			protected function log(string $message): void
			{
				parent::log($message);
				$this->messages[] = $message;
			}


			/**
			 * @return list<string>
			 */
			public function getLoggedMessages(): array
			{
				return $this->messages;
			}

		};
	}

}

(new CatalogueExtractorNeonTest())->run();
