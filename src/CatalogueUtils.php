<?php declare(strict_types=1);

namespace Forrest79\Translation;

interface CatalogueUtils
{

	/**
	 * Call after cache is built.
	 */
	function afterCacheBuild(string $locale, string $source, string $localeCache): void;


	/**
	 * Call after cache is cleared.
	 */
	function afterCacheClear(string $locale, string $localeCache): void;

}
