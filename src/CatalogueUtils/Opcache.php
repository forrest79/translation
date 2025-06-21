<?php declare(strict_types=1);

namespace Forrest79\Translation\CatalogueUtils;

use Forrest79\Translation;

class Opcache implements Translation\CatalogueUtils
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
		opcache_invalidate($localeCache, true);
	}

}
