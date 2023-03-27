<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

use Nette\Localization;

interface ITranslator extends Localization\Translator
{
	public const PARAM_LOCALE = 'locale';
	public const PARAM_COUNT = 'count';


	/**
	 * translate(string $message, int|array|NULL $translateParameters = NULL, ?int $count = NULL): string
	 *   param string $message
	 *   param int|array|NULL $translateParameters (int = count; array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 *   param int|NULL $count
	 */
	function translate(string|\Stringable $message, mixed ...$parameters): string;


	function getLocale(): string;

}
