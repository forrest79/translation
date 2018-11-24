<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Nette\Localization;

interface ITranslator extends Localization\ITranslator
{
	const PARAM_LOCALE = 'locale';
	const PARAM_COUNT = 'count';


	/**
	 * @param string $message
	 * @param int|array|NULL $parameters (int = count, array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 * @param int|NULL $count
	 * @return string
	 */
	function translate($message, $parameters = NULL, ?int $count = NULL): string;


	function getLocale(): string;

}
