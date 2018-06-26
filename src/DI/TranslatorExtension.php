<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\DI;

use Forrest79\SimpleTranslator;
use Nette;

class TranslatorExtension extends Nette\DI\CompilerExtension
{
	private $defaults = [
		'locale' => NULL,
		'fallbackLocale' => NULL,
		'dataLoader' => NULL, // will use DataLoaders/Neon
		'localesDir' => '%appDir%/locales', // for DataLoaders/Neon
		'tempDir' => '%tempDir%',
		'localeUtils' => NULL, // NULL = auto detect, FALSE = disable
		'requestResolver' => 'locale', // FALSE = disable
		'debugger' => '%debugMode%',
	];


	public function loadConfiguration(): void
	{
		$this->config += $this->defaults;

		$builder = $this->getContainerBuilder();
		$config = Nette\DI\Helpers::expand($this->config, $builder->parameters);

		$translator = $builder->addDefinition($this->prefix('default'))
			->setFactory(SimpleTranslator\Translator::class, [
				$config['debugger'],
				$config['tempDir'],
			]);

		$dataLoader = $config['dataLoader'];
		if ($dataLoader === NULL) {
			$builder->addDefinition($this->prefix('dataLoader.neon'))
				->setFactory(SimpleTranslator\DataLoaders\Neon::class, [$config['localesDir']]);
			$dataLoader = $this->prefix('@dataLoader.neon');
		}
		$translator->addSetup('setDataLoader', [$dataLoader]);

		$localeUtils = $config['localeUtils'];
		if (($localeUtils === NULL) && function_exists('opcache_invalidate')){
			$builder->addDefinition($this->prefix('localeUtils.opcache'))
				->setFactory(SimpleTranslator\LocaleUtils\Opcache::class);
			$localeUtils = $this->prefix('@localeUtils.opcache');
		}

		if ($localeUtils) {
			$translator->addSetup('setLocaleUtils', [$localeUtils]);
		}

		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('panel'))
				->setFactory(SimpleTranslator\Diagnostics\Panel::class . '::register');

			$translator->addSetup('?->setPanel(?)', ['@self', $this->prefix('@panel')]);
		}

		if ($config['locale'] !== NULL) {
			$translator->addSetup('setLocale', [$config['locale']]);
		}

		if ($config['fallbackLocale'] !== NULL) {
			$translator->addSetup('setFallbackLocale', [$config['fallbackLocale']]);
		}

		if ($config['requestResolver'] !== FALSE) {
			$builder->addDefinition($this->prefix('requestResolver'))
				->setFactory(SimpleTranslator\RequestResolver::class, [$config['requestResolver']]);
		}
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		if ($builder->hasDefinition('nette.latteFactory')) {
			$builder->getDefinition('nette.latteFactory')
				->addSetup('addFilter', ['translate', [$this->prefix('@default'), 'translate']]);
		}

		if ($config['requestResolver'] !== FALSE) {
			$applicationService = $builder->getByType(Nette\Application\Application::class);
			if ($builder->hasDefinition($applicationService)) {
				$builder->getDefinition($applicationService)
					->addSetup('$service->onRequest[] = ?', [[$this->prefix('@requestResolver'), 'onRequest']]);
			}
		}
	}

}
