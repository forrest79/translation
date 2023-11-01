<?php declare(strict_types=1);

namespace Forrest79\Translation\Loggers;

use Forrest79\Translation;
use Tracy;

class TracyLogger implements Translation\Logger
{
	private const DEFAULT_LEVEL = 'translator';

	private Tracy\ILogger $tracyLogger;

	private string $level;


	public function __construct(Tracy\ILogger $tracyLogger, string $level = self::DEFAULT_LEVEL)
	{
		$this->tracyLogger = $tracyLogger;
		$this->level = $level;
	}


	public function addUntranslated(string $locale, string $message): void
	{
		$this->log(sprintf('No translation for "%s" in locale "%s"', $message, $locale));
	}


	public function addError(string $locale, string $error): void
	{
		$this->log(sprintf('Translation error [%s] in locale "%s"', $error, $locale));
	}


	public function addLocaleFile(string $locale, string $source): void
	{
		// No logging in Tracy\Logger.
	}


	private function log(string $message): void
	{
		$this->tracyLogger->log($message, $this->level);
	}

}
