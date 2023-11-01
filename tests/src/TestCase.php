<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Tester;

/**
 * @testCase
 */
abstract class TestCase extends Tester\TestCase
{
	private static int|NULL $microtime = NULL;


	public function run(): void
	{
		Tester\Environment::setup();
		date_default_timezone_set('Europe/Prague');

		parent::run();
	}


	final protected static function getSharedTempDir(): string
	{
		return __DIR__ . '/../temp';
	}


	final protected static function getCurrentTestTempDir(): string
	{
		if (self::$microtime === NULL) {
			self::$microtime = (int) (microtime(TRUE) * 10000);
		}

		return self::getSharedTempDir() . '/' . self::$microtime . '-' . getmypid() . '-' . self::getCurrentTestThread();
	}


	final protected static function getCurrentTestThread(): int
	{
		return (int) getenv(Tester\Environment::VariableThread);
	}


	final protected static function prepareCurrentTestTempDir(): string
	{
		$testTempDir = self::getCurrentTestTempDir();
		Tester\Helpers::purge($testTempDir);
		$testTempDir = realpath($testTempDir);
		assert(is_string($testTempDir));
		return $testTempDir;
	}

}
