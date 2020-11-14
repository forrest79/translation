<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Nette\DI;

use Forrest79\SimpleTranslator;
use Nette;
use Nette\Schema;
use Tracy;

/**
 * @property-read \stdClass $config
 */
class Extension extends Nette\DI\CompilerExtension
{
	private bool $debugMode;


	public function __construct(bool $debugMode)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$translator = $builder->addDefinition($this->prefix('default'))
			->setFactory(SimpleTranslator\Translator::class, [
				$this->debugMode,
				$this->config->tempDir,
			]);

		$dataLoader = $this->config->dataLoader;
		if ($dataLoader === NULL) {
			$builder->addDefinition($this->prefix('dataLoader.neon'))
				->setFactory(SimpleTranslator\DataLoaders\Neon::class, [$this->config->localesDir]);
			$dataLoader = $this->prefix('@dataLoader.neon');
		}
		$translator->addSetup('setDataLoader', [$dataLoader]);

		$localeUtils = $this->config->localeUtils;
		if (($localeUtils === NULL) && function_exists('opcache_invalidate')) {
			$builder->addDefinition($this->prefix('localeUtils.opcache'))
				->setFactory(SimpleTranslator\LocaleUtils\Opcache::class);
			$localeUtils = $this->prefix('@localeUtils.opcache');
		}

		if ($localeUtils) {
			$translator->addSetup('setLocaleUtils', [$localeUtils]);
		}

		if ($this->debugMode && $this->config->debugger) {
			$builder->addDefinition($this->prefix('panel'))
				->setFactory(SimpleTranslator\Diagnostics\Panel::class . '::register');

			$translator->addSetup('?->setPanel(?)', ['@self', $this->prefix('@panel')]);
		}

		if ($this->config->locale !== NULL) {
			$translator->addSetup('setLocale', [$this->config->locale]);
		}

		if ($this->config->fallbackLocale !== NULL) {
			$translator->addSetup('setFallbackLocale', [$this->config->fallbackLocale]);
		}

		if ($this->config->requestResolver !== FALSE) {
			$builder->addDefinition($this->prefix('requestResolver'))
				->setFactory(SimpleTranslator\RequestResolver::class, [$this->config->requestResolver]);
		}
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		if (($this->config->latteFilter === TRUE) && $builder->hasDefinition('nette.latteFactory')) {
			$latteFactoryDefinition = $builder->getDefinition('nette.latteFactory');
			assert($latteFactoryDefinition instanceof Nette\DI\Definitions\FactoryDefinition);
			$latteFactoryDefinition->getResultDefinition()
				->addSetup('addFilter', ['translate', [$this->prefix('@default'), 'translate']]);
		}

		if ($this->config->requestResolver !== FALSE) {
			$applicationService = $builder->getByType(Nette\Application\Application::class);
			if (($applicationService !== NULL) && $builder->hasDefinition($applicationService)) {
				$applicationServiceDefinition = $builder->getDefinition($applicationService);
				assert($applicationServiceDefinition instanceof Nette\DI\Definitions\ServiceDefinition);
				$applicationServiceDefinition->addSetup(
					'$service->onRequest[] = ?',
					[[$this->prefix('@requestResolver'), 'onRequest']],
				);
			}
		}
	}


	public function getConfigSchema(): Schema\Schema
	{
		$builder = $this->getContainerBuilder();

		return Schema\Expect::structure([
			'locale' => Schema\Expect::string(NULL),
			'fallbackLocale' => Schema\Expect::string(NULL),
			'dataLoader' => Schema\Expect::string(NULL), // will use DataLoaders/Neon
			'localesDir' => Schema\Expect::string($builder->parameters['appDir'] . '/locales'), // for DataLoaders/Neon
			'tempDir' => Schema\Expect::string($builder->parameters['tempDir']),
			'localeUtils' => Schema\Expect::mixed(NULL), // NULL = auto detect, FALSE = disable
			'latteFilter' => Schema\Expect::bool(TRUE),
			'requestResolver' => Schema\Expect::mixed('locale'), // FALSE = disable
			'debugger' => Schema\Expect::bool(class_exists(Tracy\BlueScreen::class)),
		]);
	}

}
