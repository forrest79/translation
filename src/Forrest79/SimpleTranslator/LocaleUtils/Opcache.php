<?php

namespace Forrest79\SimpleTranslator\LocaleUtils;

use Forrest79\SimpleTranslator;


class Opcache implements SimpleTranslator\ILocaleUtils
{

	public function afterCacheBuild($locale, $localeFile, $localeCache)
	{
		opcache_invalidate($localeCache, TRUE);
	}

}
