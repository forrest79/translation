<?php

namespace Forrest79\NttTranslator\DI;

use Forrest79\NttTranslator;
use Nette;


class TranslatorExtension extends Nette\DI\CompilerExtension
{

	public $defaults = [
		'locale' => NULL,
		'fallbackLocale' => NULL,
		'localesDir' => '%appDir%/locales',
		'tempDir' => '%tempDir%',
		'localeUtils' => NULL, // NULL = auto detect, FALSE = disable
		'requestResolver' => 'locale', // FALSE = disable
		'debugger' => '%debugMode%',
	];


	public function loadConfiguration()
	{
		$this->config += $this->defaults;

		$builder = $this->getContainerBuilder();
		$config = Nette\DI\Helpers::expand($this->config, $builder->parameters);

		$translator = $builder->addDefinition($this->prefix('default'))
			->setClass(NttTranslator\Translator::class, [
				$config['debugger'],
				$config['localesDir'],
				$config['tempDir'],
			]);

		$localeUtils = $config['localeUtils'];
		if (($localeUtils === NULL) && function_exists('opcache_invalidate')){
			$builder->addDefinition($this->prefix('localeUtils.opcache'))
				->setClass(NttTranslator\LocaleUtils\Opcache::class);
			$localeUtils = $this->prefix('@localeUtils.opcache');
		}

		if ($localeUtils) {
			$translator->addSetup('setLocaleUtils', [$localeUtils]);
		}

		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('panel'))
				->setClass(NttTranslator\Diagnostics\Panel::class)
				->setFactory(NttTranslator\Diagnostics\Panel::class . '::register');

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
				->setClass(NttTranslator\RequestResolver::class, [$config['requestResolver']]);
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
