<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Tests\Nette\DI;

use Forrest79;
use Forrest79\SimpleTranslator;
use Forrest79\SimpleTranslator\Tests;
use Nette\DI;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ExtensionTest extends Tests\TestCase
{
	private int $container = 0;


	public function testDefaultConfig(): void
	{
		$container = $this->createContainer();

		$translator = $container->getService('translator.default');
		Assert::type(SimpleTranslator\Translator::class, $translator);
	}


	public function testNoLocaleUtils(): void
	{
		$container = $this->createContainer([
			'localeUtils: false',
		]);

		$translator = $container->getService('translator.default');
		Assert::type(SimpleTranslator\Translator::class, $translator);
	}


	public function testSetFallbackLocale(): void
	{
		$container = $this->createContainer([
			'fallbackLocale: cs',
		]);

		$translator = $container->getService('translator.default');
		Assert::type(SimpleTranslator\Translator::class, $translator);
	}


	public function testAutoLocaleUtils(): void
	{
		if (!function_exists('opcache_invalidate')) {
			eval('function opcache_invalidate() {};');
		}

		$container = $this->createContainer();

		$localeUtils = $container->getService('translator.localeUtils.opcache');
		Assert::type(SimpleTranslator\LocaleUtils\Opcache::class, $localeUtils);
	}


	public function testOpcacheLocaleUtils(): void
	{
		$container = $this->createContainer([
			'localeUtils: @opcache',
		], [
			'opcache: Forrest79\SimpleTranslator\LocaleUtils\Opcache',
		]);

		Assert::exception(static function () use ($container): void {
			$container->getService('translator.localeUtils.opcache');
		}, DI\MissingServiceException::class);

		$localeUtils = $container->getService('opcache');
		Assert::type(SimpleTranslator\LocaleUtils\Opcache::class, $localeUtils);
	}


	public function testExternalDataLoader(): void
	{
		$container = $this->createContainer([
			'dataLoader: @neon',
		], [
			'neon: Forrest79\SimpleTranslator\DataLoaders\Neon(%tempDir%)',
		]);

		$translator = $container->getService('translator.default');
		Assert::type(SimpleTranslator\Translator::class, $translator);
	}


	public function testRequestResolver(): void
	{
		$container = $this->createContainer([
			'requestResolver: false',
		]);

		Assert::exception(static function () use ($container): void {
			$container->getService('translator.requestResolver');
		}, DI\MissingServiceException::class);

		$container = $this->createContainer();

		$requestResolver = $container->getService('translator.requestResolver');
		Assert::type(SimpleTranslator\RequestResolver::class, $requestResolver);
	}


	public function testSetLocale(): void
	{
		$container = $this->createContainer([
			'locale: en',
		]);

		/** @var Forrest79\SimpleTranslator\ITranslator $translator */
		$translator = $container->getService('translator.default');
		Assert::same('en', $translator->getLocale());
	}


	/**
	 * @param array<string> $config
	 * @param array<string> $services
	 */
	private function createContainer(array $config = [], array $services = []): DI\Container
	{
		$configNeon = '';
		if (count($config) > 0) {
			foreach ($config as $line) {
				$configNeon .= "\n" . str_repeat("\t", 5) . $line;
			}
		}

		$servicesNeon = '';
		if (count($services) > 0) {
			foreach ($services as $line) {
				$servicesNeon .= "\n" . str_repeat("\t", 5) . $line;
			}
		}

		$containerName = 'Container' . $this->container++;

		$loader = new DI\Config\Loader();
		$config = $loader->load(Tester\FileMock::create('
				translator:' . $configNeon . '
				parameters:
					appDir: /tmp
					tempDir: /tmp
					debugMode: true
				services:' . $servicesNeon . '
					- Tracy\Logger(%appDir%)
			', 'neon'));
		$compiler = new DI\Compiler();
		$compiler->addExtension('translator', new SimpleTranslator\Nette\DI\Extension(TRUE));
		eval($compiler->addConfig($config)->setClassName($containerName)->compile());

		$containerName = '\\' . $containerName;

		$container = new $containerName();
		$container->initialize();

		return $container;
	}

}

(new ExtensionTest())->run();
