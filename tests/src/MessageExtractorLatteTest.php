<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\MessageExtractors;
use Latte;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class MessageExtractorLatteTest extends TestCase
{
	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();
	}


	public function testExtractLatteFile(): void
	{
		$latteFile = $this->tempDir . DIRECTORY_SEPARATOR . 'extract.latte';

		$class = <<<'LATTE'
			{='sample_identifier'|translate}
			{='sample_identifier_with_param'|translate:['param' => 1]}
			{='sample_identifier_plural'|translate:3}
			{=$identifier|translate:['param' => 2],3}
			
			{var $a = ('sample_variable_identifier'|translate)}
			{var $b = ('sample_variable_identifier_with_param'|translate:['param' => 1])}
			{var $c = ('sample_variable_identifier_plural'|translate:3)}
			{var $d = ($identifier|translate:['param' => 2],3)}
			LATTE;

		file_put_contents($latteFile, $class);

		$latteFileExtractor = new MessageExtractors\Latte(new Latte\Engine());

		Assert::same('latte', $latteFileExtractor->fileExtension());
		Assert::same([
			'sample_identifier',
			'sample_identifier_with_param',
			'sample_identifier_plural',
			'sample_variable_identifier',
			'sample_variable_identifier_with_param',
			'sample_variable_identifier_plural',
		], $latteFileExtractor->extract(new \SplFileInfo($latteFile)));
	}

}

(new MessageExtractorLatteTest())->run();
