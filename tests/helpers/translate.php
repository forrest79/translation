<?php declare(strict_types=1);

use Forrest79\SimpleTranslator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$tempDir = $argv[1];
$locale = $argv[2];
$cacheFile = isset($argv[3]) ? $argv[3] : NULL;

$translator = (new SimpleTranslator\Translator(TRUE, $tempDir, Tracy\Debugger::getLogger()))
	->setLocaleUtils(new Forrest79\SimpleTranslator\Tests\TestLocaleUtils)
	->setDataLoader(new SimpleTranslator\DataLoaders\Neon($tempDir))
	->setLocale($locale);

$cacheHash = NULL;

try {
	echo $translator->translate('test');
} catch (Forrest79\SimpleTranslator\Tests\TestLocaleUtilsException $e) {
	$data = explode('|', $e->getMessage(), 3);
	$cacheFile = $data[2];
	$cacheHash = md5_file($cacheFile);
}

if ($cacheHash !== NULL) {
	echo $translator->translate('test');
} else {
	$cacheHash = md5_file($cacheFile);
}

echo '|' . $cacheHash . '|' . $cacheFile;

// @hack - this file act as test
Assert::true(TRUE);
