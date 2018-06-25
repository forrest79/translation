<?php

namespace Forrest79\SimpleTranslator;

use Forrest79\SimpleTranslator;
use Nette\Application;


class RequestResolver
{
	/** @var string */
	private $parameter;

	/** @var SimpleTranslator\Translator */
	private $translator;


	public function __construct($parameter, SimpleTranslator\Translator $translator)
	{
		$this->parameter = $parameter;
		$this->translator = $translator;
	}


	public function onRequest(Application\Application $application, Application\Request $request)
	{
		$this->translator->setLocale($request->getParameter($this->parameter));
	}

}
