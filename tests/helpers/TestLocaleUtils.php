<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Tests;

use Forrest79\SimpleTranslator;

class TestLocaleUtils implements SimpleTranslator\LocaleUtils
{

	public function afterCacheBuild(string $locale, string $source, string $localeCache): void
	{
		throw new TestLocaleUtilsException($locale . '|' . $source . '|' . $localeCache);
	}

}
