<?php

namespace Forrest79\NttTranslator\LocaleUtils;

use Forrest79\NttTranslator;


class Opcache implements NttTranslator\ILocaleUtils
{

	public function afterCacheBuild($locale, $localeFile, $localeCache)
	{
		opcache_invalidate($localeCache, TRUE);
	}

}
