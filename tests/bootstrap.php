<?php

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

// configure environment
Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/temp/' . getmypid());
Tester\Helpers::purge(TEMP_DIR);
Tracy\Debugger::$logDirectory = TEMP_DIR;

function createLocale(array $messages, array $plural = [], ?string $manualLocale = NULL)
{
	static $locale = 0;

	if ($plural === []) {
		$plural = ['n == 1', 'n > 1'];
	}

	$messages = [
		'plural' => $plural,
		'messages' => $messages,
	];

	file_put_contents(TEMP_DIR . DIRECTORY_SEPARATOR . (($manualLocale === NULL) ? $locale : $manualLocale) . '.neon', (new Nette\Neon\Encoder)->encode($messages));

	return ($manualLocale === NULL) ? (string) $locale++ : $manualLocale;
}
