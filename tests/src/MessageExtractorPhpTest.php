<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\MessageExtractors;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class MessageExtractorPhpTest extends TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testExtractPhpFile(): void
	{
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

		$phpFileExtractor = new MessageExtractors\Php();

		Assert::same('php', $phpFileExtractor->fileExtension());
		Assert::same([
			'sample_identifier',
			'sample_identifier_with_param',
			'sample_identifier_plural',
		], $phpFileExtractor->extract(new \SplFileInfo($phpFile)));
	}


	public function testExtractNonExistingPhpFile(): void
	{
		Assert::exception(function (): void {
			(new MessageExtractors\Php())
				->extract(new \SplFileInfo($this->tempDir . DIRECTORY_SEPARATOR . 'non-existing-extract.php'));
		}, \RuntimeException::class);
	}

}

(new MessageExtractorPhpTest())->run();
