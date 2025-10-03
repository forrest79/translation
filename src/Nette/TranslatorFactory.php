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
		Translation\CatalogueUtils|null $catalogueUtils = null,
		Translation\Logger|null $logger = null,
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
		string|null $defaultLocale = null,
	): Translation\Translator
	{
		$locale = $defaultLocale;

		foreach ($application->getRequests() as $request) {
			$detectedLocale = $request->getParameter($this->parameter);
			if ($detectedLocale !== null) {
				$locale = $detectedLocale;
				break;
			}
		}

		if ($locale === null) {
			throw new Exceptions\NoLocaleParameterException();
		}

		assert(is_string($locale));
		return $this->create($locale);
	}

}
