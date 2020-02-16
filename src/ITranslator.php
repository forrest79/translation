<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Nette\Localization;

interface ITranslator extends Localization\ITranslator
{
	public const PARAM_LOCALE = 'locale';
	public const PARAM_COUNT = 'count';


	/**
	 * @param mixed $message string
	 * @param mixed $parameters int|array|NULL (int = count, array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 */
	function translate($message, $parameters = NULL, ?int $count = NULL): string;


	function getLocale(): string;

}
