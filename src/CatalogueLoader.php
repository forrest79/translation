<?php declare(strict_types=1);

namespace Forrest79\Translation;

interface CatalogueLoader
{

	/**
	 * Return TRUE if there is needs to rebuild cache in debug mode.
	 */
	function isLocaleUpdated(string $locale, string $cacheFile): bool;


	/**
	 * Return translation defition.
	 *
	 * @return array<string, string|array<string, string|list<string>>|NULL> with two keys
	 *   [
	 *     'plural' (optional) => string - plural definition with $count variable, if missing, definition is taken from PluralsHelper by $locale
	 *     'messages' => array - [message => translation, ...] where translation can be string or list of strings for plural
	 *   ]
	 */
	function loadData(string $locale): array;


	/**
	 * Return locale identification.
	 */
	function source(string $locale): string;

}
