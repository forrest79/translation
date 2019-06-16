<?php declare(strict_types=1);

function createLocale(array $messages, array $plural = [], ?string $manualLocale = NULL, ?callable $updateNeonData = NULL): string
{
	static $locale = 0;

	if ($plural === []) {
		$plural = ['n == 1', 'n > 1'];
	}

	$messages = [
		'plural' => $plural,
		'messages' => $messages,
	];

	$neon = (new Nette\Neon\Encoder())->encode($messages);

	if ($updateNeonData !== NULL) {
		$neon = $updateNeonData($neon);
	}

	file_put_contents(
		TEMP_DIR . DIRECTORY_SEPARATOR . (($manualLocale === NULL) ? $locale : $manualLocale) . '.neon',
		$neon
	);

	return ($manualLocale === NULL) ? (string) $locale++ : $manualLocale;
}

function translate(string $testLocale, ?string $cacheFile = NULL): array
{
	$data = exec('php ' . __DIR__ . '/translate.php ' . TEMP_DIR . ' ' . $testLocale . (($cacheFile === NULL) ? '' : (' ' . $cacheFile)));
	return explode('|', $data);
}
