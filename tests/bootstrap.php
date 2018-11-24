<?php declare(strict_types=1);

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

// create temporary directory
define('TEMP_DIR', __DIR__ . '/temp/' . getmypid());
Tester\Helpers::purge(TEMP_DIR);
Tracy\Debugger::$logDirectory = TEMP_DIR;

require __DIR__ . '/helpers/functions.php';
require __DIR__ . '/helpers/HomepagePresenter.php';
require __DIR__ . '/helpers/TestLocaleUtils.php';
require __DIR__ . '/helpers/TestLocaleUtilsException.php';

// configure environment
Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');
