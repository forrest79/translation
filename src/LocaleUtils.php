<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

interface LocaleUtils
{

	/**
	 * Call after cache is build.
	 */
	function afterCacheBuild(string $locale, string $source, string $localeCache): void;

}
