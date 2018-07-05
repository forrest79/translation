<?php

namespace Tests\Forrest79\SimpleTranslator;

use Forrest79\SimpleTranslator;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../bootstrap.php';

$testMessage = 'Test message';

class TestLocaleUtilsException extends \Exception
{
};

class TestLocaleUtils implements SimpleTranslator\LocaleUtils
{

	public function afterCacheBuild(string $locale, string $source, string $localeCache): void
	{
		throw new TestLocaleUtilsException($locale . '|' . $source . '|' . $localeCache);
	}

}

$locale = createLocale(['test' => $testMessage]);

$translator = (new SimpleTranslator\Translator(TRUE, TEMP_DIR, Tracy\Debugger::getLogger()))
	->setLocaleUtils(new TestLocaleUtils)
	->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR))
	->setLocale($locale);


// 1) building cache
try {
	$translator->translate('test');
} catch (TestLocaleUtilsException $e) {
	$data = explode('|', $e->getMessage(), 3);
	Assert::equal((string) $locale, $data[0]);
	Assert::contains('.neon', $data[1]);
	Assert::contains('.php', $data[2]);
}

// 2) cache is build, so translate will work in this test
Assert::same($testMessage, $translator->translate('test'));
