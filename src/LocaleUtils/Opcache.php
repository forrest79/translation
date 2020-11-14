<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\LocaleUtils;

use Forrest79\SimpleTranslator;

class Opcache implements SimpleTranslator\LocaleUtils
{

	public function afterCacheBuild(string $locale, string $source, string $localeCache): void
	{
		$this->invalidateCache($localeCache);
	}


	public function afterCacheClear(string $locale, string $localeCache): void
	{
		$this->invalidateCache($localeCache);
	}


	private function invalidateCache(string $localeCache): void
	{
		opcache_invalidate($localeCache, TRUE);
	}

}
