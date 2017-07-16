<?php

namespace Forrest79\NttTranslator\DI;

use Forrest79\NttTranslator;
use Nette;


class TranslatorExtension extends Nette\DI\CompilerExtension
{

	public $defaults = [
		'fallbackLocale' => NULL,
		'localesDir' => '%appDir%/locales',
		'tempDir' => '%tempDir%',
		'localeUtils' => NULL, // auto detect
		'requestResolver' => 'locale', // FALSE = disable
		'debugger' => '%debugMode%',
	];


	public function loadConfiguration()
	{
		$this->config += $this->defaults;

		$builder = $this->getContainerBuilder();
		$config = Nette\DI\Helpers::expand($this->config, $builder->parameters);

		$localeUtils = $config['localeUtils'];
		if (($localeUtils === NULL) && function_exists('opcache_invalidate')){
			$builder->addDefinition($this->prefix('localeUtils.opcache'))
				->setClass(NttTranslator\LocaleUtils\Opcache::class);
			$localeUtils = $this->prefix('@localeUtils.opcache');
		}

		if ($config['requestResolver'] !== FALSE) {
			$builder->addDefinition($this->prefix('requestResolver'))
				->setClass(NttTranslator\RequestResolver::class, [$config['requestResolver']]);
		}

		$translator = $builder->addDefinition($this->prefix('default'))
			->setClass(NttTranslator\Translator::class)
			->setFactory(NttTranslator\TranslatorFactory::class . '::create', [
				$builder->parameters['debugMode'],
				$config['debugger'],
				$config['localesDir'],
				$config['tempDir'],
				$localeUtils,
			]);

		if ($config['fallbackLocale'] !== NULL) {
			$translator->addSetup('setFallbackLocale', [$config['fallbackLocale']]);
		}
	}


	public function beforeCompile()
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
