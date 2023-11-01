<?php declare(strict_types=1);

namespace Forrest79\Translation\Nette;

use Forrest79\Translation;
use Nette\Application;

class TranslatorFactory extends Translation\TranslatorFactory
{
	private const DEFAULT_PARAMETER = 'locale';

	private string $parameter;


	/**
	 * @param array<string, list<string>> $fallbackLocales
	 */
	public function __construct(
		bool $debugMode,
		string $tempDir,
		Translation\CatalogueLoader $catalogueLoader,
		string $parameter = self::DEFAULT_PARAMETER,
		array $fallbackLocales = [],
		Translation\CatalogueUtils|NULL $catalogueUtils = NULL,
		Translation\Logger|NULL $logger = NULL,
	)
	{
		parent::__construct($debugMode, $tempDir, $catalogueLoader, $fallbackLocales, $catalogueUtils, $logger);
		$this->parameter = $parameter;
	}


	/**
	 * @throws Translation\Exceptions\BadLocaleNameException
	 * @throws Translation\Exceptions\FallbackLocaleIsTheSameAsMainLocaleException
	 */
	public function createByRequest(
		Application\Application $application,
		string|NULL $defaultLocale = NULL,
	): Translation\Translator
	{
		$locale = $defaultLocale;
		foreach ($application->getRequests() as $request) {
			$locale = $request->getParameter($this->parameter);
			if ($locale !== NULL) {
				break;
			}
		}

		if ($locale === NULL) {
			throw new Exceptions\NoLocaleParameterException();
		}

		assert(is_string($locale));
		return $this->create($locale);
	}

}
