<?php

namespace Forrest79\Tests\SimpleTranslator;

use Forrest79\SimpleTranslator;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../../../bootstrap.php';


$testMessage = 'Test message';

class TestLocaleUtilsException extends \Exception {};

class TestLocaleUtils implements SimpleTranslator\ILocaleUtils
{

	public function afterCacheBuild($locale, $localeFile, $localeCache)
	{
		throw new TestLocaleUtilsException($locale . '|' . $localeFile . '|' . $localeCache);
	}

}

$locale = createLocale(['test' => $testMessage], []);

$translator = (new SimpleTranslator\Translator(TRUE, TEMP_DIR, TEMP_DIR, Tracy\Debugger::getLogger()))
	->setLocaleUtils(new TestLocaleUtils)
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
