<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Forrest79\SimpleTranslator;
use Nette\Application;

class RequestResolver
{
	/** @var string */
	private $parameter;

	/** @var SimpleTranslator\Translator */
	private $translator;


	public function __construct(string $parameter, SimpleTranslator\Translator $translator)
	{
		$this->parameter = $parameter;
		$this->translator = $translator;
	}


	/**
	 * @throws Exceptions\BadLocaleNameException
	 */
	public function onRequest(Application\Application $application, Application\Request $request): void
	{
		$this->translator->setLocale($request->getParameter($this->parameter));
	}

}
