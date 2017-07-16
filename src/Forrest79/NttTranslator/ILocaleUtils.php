<?php

namespace Forrest79\NttTranslator;


interface ILocaleUtils
{

	/**
	 * Call after cache is build.
	 * @param string $locale
	 * @param string $localeFile neon file with locale data
	 * @param string $localeCache PHP cache file with locale data
	 */
	function afterCacheBuild($locale, $localeFile, $localeCache);

}
