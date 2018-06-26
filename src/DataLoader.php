<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

interface DataLoader
{

	function isLocaleUpdated(string $locale, string $cacheFile): bool;


	function loadData(string $locale): array;


	function source(string $locale): string;

}
