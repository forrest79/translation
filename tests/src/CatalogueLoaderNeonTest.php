<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoaders;
use Forrest79\Translation\Exceptions;
use Nette\Neon;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class CatalogueLoaderNeonTest extends TestCase
{
	private string $localesDir;

	private CatalogueLoaders\Neon $neonCatalogueLoader;


	protected function setUp(): void
	{
		parent::setUp();

		$this->localesDir = self::prepareCurrentTestTempDir();
		$this->neonCatalogueLoader = new CatalogueLoaders\Neon($this->localesDir);
	}


	public function testLoadData(): void
	{
		$data = [
			'plural' => '($count === 1) ? 0 : 1',
			'messages' => [
				'identifier_single' => 'translation_single',
				'identifier_plural' => ['translation_plural1', 'translation_plural1'],
			],
		];

		file_put_contents($this->localesDir . '/xx.neon', Neon\Neon::encode($data));

		Assert::same($data, $this->neonCatalogueLoader->loadData('xx'));
	}


	public function testIsLocaleUpdated(): void
	{
		$localeFile = $this->localesDir . '/xx.neon';
		$cacheFile = $this->localesDir . '/xx.php';
		Assert::false($this->neonCatalogueLoader->isLocaleUpdated('xx', $cacheFile));

		$time = time();

		touch($localeFile, $time);
		touch($cacheFile, $time);
		Assert::false($this->neonCatalogueLoader->isLocaleUpdated('xx', $cacheFile));

		touch($localeFile, $time + 1);
		touch($cacheFile, $time);
		Assert::true($this->neonCatalogueLoader->isLocaleUpdated('xx', $cacheFile));

		touch($localeFile, $time);
		touch($cacheFile, $time + 1);
		Assert::false($this->neonCatalogueLoader->isLocaleUpdated('xx', $cacheFile));
	}


	public function testSource(): void
	{
		Assert::same($this->localesDir . '/xx.neon', $this->neonCatalogueLoader->source('xx'));
	}


	public function testNonExistingFile(): void
	{
		Assert::exception(function (): void {
			$this->neonCatalogueLoader->loadData('xx');
		}, Exceptions\IOException::class);
	}


	public function testBadNeonFormat(): void
	{
		file_put_contents($this->localesDir . '/xx.neon', "  xxx:\n\txxx:");

		Assert::exception(function (): void {
			$this->neonCatalogueLoader->loadData('xx');
		}, Exceptions\ParsingErrorException::class);
	}

}

(new CatalogueLoaderNeonTest())->run();
