<?php declare(strict_types=1);

namespace Forrest79\Tests\SimpleTranslator\DI;

use Forrest79;
use Forrest79\SimpleTranslator;
use Nette\DI;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class TranslatorExtensionTest extends Tester\TestCase
{
	private $container = 0;


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

		Assert::exception(function() use ($container) {
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

		Assert::exception(function() use ($container) {
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

		$translator = $container->getService('translator.default');
		Assert::same('en', $translator->getLocale());
	}


	/** @return DI\Container */
	private function createContainer(array $config = [], array $services = [])
	{
		$configNeon = '';
		if ($config) {
			foreach ($config as $line) {
				$configNeon .= "\n" . str_repeat("\t", 5) . $line;
			}
		}

		$servicesNeon = '';
		if ($services) {
			foreach ($services as $line) {
				$servicesNeon .= "\n" . str_repeat("\t", 5) . $line;
			}
		}

		$containerName = 'Container' . $this->container++;

		$loader = new DI\Config\Loader;
		$config = $loader->load(Tester\FileMock::create('
				translator:' . $configNeon . '
				parameters:
					appDir: /tmp
					tempDir: /tmp
					debugMode: true
				services:' . $servicesNeon . '
					- Tracy\Logger(%appDir%)
			', 'neon'));
		$compiler = new DI\Compiler;
		$compiler->addExtension('translator', new SimpleTranslator\DI\TranslatorExtension);
		eval($compiler->addConfig($config)->setClassName($containerName)->compile());

		$containerName = '\\' . $containerName;

		$container = new $containerName;
		$container->initialize();

		return $container;
	}

}

(new TranslatorExtensionTest)->run();
