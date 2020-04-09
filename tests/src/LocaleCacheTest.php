<?php declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$testLocale = 'testlocale';
$testMessage = 'Test message';
$updatedTestMessage = 'New test message';

// 1) Generate locale and translate (cache is generated)
createLocale(['test' => $testMessage], [], $testLocale);

$data = translate($testLocale);

$originalHash = $data[1];
$cacheFile = $data[2];
Assert::same($testMessage, $data[0]);

// 2) Wait and translate with the same cache file

sleep(2);

$data = translate($testLocale, $cacheFile);
Assert::same($originalHash, $data[1]);
Assert::same($testMessage, $data[0]);

// 3) Change locale file, wait and get new message and cache hash (only with debugMode = TRUE, on production, you have to delete manually cache files - step 1 is run again)

createLocale(['test' => $updatedTestMessage], [], $testLocale);

sleep(2);

$data = translate($testLocale, $cacheFile);
Assert::notSame($originalHash, $data[1]);
Assert::same($updatedTestMessage, $data[0]);
