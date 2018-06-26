<?php

namespace Forrest79\Tests\SimpleTranslator;

use Forrest79\SimpleTranslator;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../../../../bootstrap.php';


$tempDir = $argv[1];
$locale = $argv[2];
$cacheFile = isset($argv[3]) ? $argv[3] : NULL;

class TestLocaleUtilsException extends \Exception {};

class TestLocaleUtils implements SimpleTranslator\LocaleUtils
{

	public function afterCacheBuild(string $locale, string $source, string $localeCache): void
	{
		throw new TestLocaleUtilsException($locale . '|' . $source . '|' . $localeCache);
	}

}

$translator = (new SimpleTranslator\Translator(TRUE, $tempDir, Tracy\Debugger::getLogger()))
	->setLocaleUtils(new TestLocaleUtils)
	->setDataLoader(new SimpleTranslator\DataLoaders\Neon($tempDir))
	->setLocale($locale);

$cacheHash = NULL;

try {
	echo $translator->translate('test');
} catch (TestLocaleUtilsException $e) {
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