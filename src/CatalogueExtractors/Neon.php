<?php declare(strict_types=1);

namespace Forrest79\Translation\CatalogueExtractors;

use Forrest79\Translation;
use Nette;

class Neon extends Translation\CatalogueExtractor
{
	private string $localesDir;

	/** @var array<string, array<int, string>> */
	private array $lines = [];


	/**
	 * @param list<string> $locales
	 * @param list<string> $sourceDirectories
	 * @param list<Translation\MessageExtractor> $fileExtractors
	 */
	public function __construct(string $localesDir, array $locales, array $sourceDirectories, array $fileExtractors)
	{
		parent::__construct($locales, $sourceDirectories, $fileExtractors);
		$this->localesDir = $localesDir;
	}


	/**
	 * @param list<string> $messages
	 */
	protected function process(string $locale, array $messages): void
	{
		parent::process($locale, $messages);

		file_put_contents($this->getLocaleNeonFile($locale), implode(PHP_EOL, $this->lines[$locale] ?? []));
	}


	/**
	 * @return list<string>
	 */
	protected function loadExistingMessages(string $locale): array
	{
		$path = $this->getLocaleNeonFile($locale);

		$content = @file_get_contents($path); // intentionally @ - file may not exists
		if ($content === false) {
			throw new \RuntimeException(sprintf('Can\'t load locale file \'%s\'.', $path));
		}

		$neonData = Nette\Neon\Neon::decode($content);
		if (!is_array($neonData)) {
			throw new \RuntimeException(sprintf('Array is expected in locale neon file \'%s\'.', $path));
		}

		if (!array_key_exists('messages', $neonData)) {
			throw new \RuntimeException(sprintf('Locale file \'%s\' has no messages section.', $path));
		}

		$lines = preg_split('/$\R?^/m', $content);
		if (is_array($lines)) {
			$this->lines[$locale] = $lines;
		}

		$messages = $neonData['messages'] ?? [];
		assert(is_array($messages));

		/** @phpstan-var list<string> */
		return array_keys($messages);
	}


	/**
	 * @param list<string> $messages
	 */
	protected function processMessagesToInsert(string $locale, array $messages): void
	{
		if ($messages !== []) {
			sort($messages);
			foreach ($messages as $message) {
				$this->lines[$locale][] = "\t" . trim(Nette\Neon\Neon::encode([$message => ''], true));
			}
			$this->lines[$locale][] = '';
		}
	}


	/**
	 * @param list<string> $messages
	 */
	protected function processMessagesToRemove(string $locale, array $messages): void
	{
		$messagesSection = false;
		foreach ($this->lines[$locale] as $i => $line) {
			$line = trim($line);
			if (($messagesSection === false) && ($line === 'messages:')) {
				$messagesSection = true;
				continue;
			}

			if ($messagesSection) {
				$neonLine = Nette\Neon\Neon::decode($line);
				if (is_array($neonLine)) {
					$key = key($neonLine);
					if (in_array($key, $messages, true)) {
						unset($this->lines[$locale][$i]);
					}
				}
			}
		}
	}


	private function getLocaleNeonFile(string $locale): string
	{
		return $this->localesDir . DIRECTORY_SEPARATOR . $locale . '.neon';
	}


	protected function log(string $message): void
	{
		echo $message . PHP_EOL;
	}

}
