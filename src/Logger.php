<?php declare(strict_types=1);

namespace Forrest79\Translation;

interface Logger
{

	function addUntranslated(string $locale, string $message): void;


	function addError(string $locale, string $error): void;


	function addLocaleFile(string $locale, string $source): void;

}
