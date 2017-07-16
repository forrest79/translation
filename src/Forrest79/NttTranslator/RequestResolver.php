<?php

namespace Forrest79\NttTranslator;

use Forrest79\NttTranslator;
use Nette\Application;


class RequestResolver
{
	/** @var string */
	private $parameter;

	/** @var NttTranslator\Translator */
	private $translator;


	public function __construct($parameter, NttTranslator\Translator $translator)
	{
		$this->parameter = $parameter;
		$this->translator = $translator;
	}


	public function onRequest(Application\Application $application, Application\Request $request)
	{
		$this->translator->setLocale($request->getParameter($this->parameter));
	}

}
