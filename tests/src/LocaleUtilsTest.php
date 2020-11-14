<?php declare(strict_types=1);

use Forrest79\SimpleTranslator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$testMessage = 'Test message';

$locale = createLocale(['test' => $testMessage]);

$translator = (new SimpleTranslator\Translator(FALSE, TEMP_DIR, Tracy\Debugger::getLogger()))
	->setLocaleUtils(new Forrest79\SimpleTranslator\Tests\TestLocaleUtils())
	->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR))
	->setLocale($locale);

$cacheFile = NULL;

// 1) building cache
try {
	$translator->translate('test');
} catch (Forrest79\SimpleTranslator\Tests\TestLocaleUtilsException $e) {
	$data = explode('|', $e->getMessage(), 3);
	$cacheFile = $data[2];
	Assert::equal($locale, $data[0]);
	Assert::contains('.neon', $data[1]);
	Assert::contains('.php', $cacheFile);
}

// 2) cache is build, so translate will work in this test
Assert::same($testMessage, $translator->translate('test'));

// 3) manually clear cache
try {
	$translator->clearCache($locale);
} catch (Forrest79\SimpleTranslator\Tests\TestLocaleUtilsException $e) {
	$data = explode('|', $e->getMessage(), 2);
	Assert::equal($locale, $data[0]);
	Assert::contains($cacheFile, $data[1]);
}
