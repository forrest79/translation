<?php

namespace Forrest79\NttTranslator;

use Tracy;


class TranslatorFactory
{

	/**
	 * @param bool $debugMode
	 * @param bool $debugger
	 * @param string $localesDir
	 * @param string $tempDir
	 * @param ILocaleUtils $localeUtils
	 * @param Tracy\ILogger $logger
	 * @return Translator
	 */
	public static function create($debugMode, $debugger, $localesDir, $tempDir, $localeUtils, Tracy\ILogger $logger)
	{
		$translator = new Translator($debugMode, $localesDir, $tempDir, $logger);

		if ($localeUtils !== NULL) {
			if (!($localeUtils instanceof ILocaleUtils)) {
				throw new \InvalidArgumentException('$localeUtils is not instance of ILocaleUtils');
			}
			$translator->setLocaleUtils($localeUtils);
		}

		if ($debugger) {
			$translator->setPanel(Diagnostics\Panel::register());
		}

		return $translator;
	}

}
