<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

interface DataLoader
{

	/**
	 * Return TRUE if there is needs to rebuild cache in debug mode.
	 */
	function isLocaleUpdated(string $locale, string $cacheFile): bool;


	/**
	 * Return translation defition.
	 *
	 * @return array<string, array<string|array<string>>> with two keys
	 *   [
	 *     'plural' => [] plural definition with n variable,
	 *     'messages' => [message => translation, ...] where translation can be string or array of strings for plural
	 *   ]
	 */
	function loadData(string $locale): array;


	/**
	 * Return locale identification.
	 */
	function source(string $locale): string;

}
