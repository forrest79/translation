<?php declare(strict_types=1);

namespace Forrest79\Translation;

abstract class CatalogueExtractor
{
	/** @var list<string> */
	private array $locales;

	/** @var list<string> */
	private array $sourceDirectories;

	/** @var array<string, MessageExtractor> */
	private array $messageExtractors;

	private string $fileExtensionsPattern;


	/**
	 * @param list<string> $locales
	 * @param list<string> $sourceDirectories
	 * @param list<MessageExtractor> $messageExtractors
	 */
	public function __construct(array $locales, array $sourceDirectories, array $messageExtractors)
	{
		$this->locales = $locales;
		$this->sourceDirectories = $sourceDirectories;

		if (count($locales) === 0) {
			throw new \InvalidArgumentException('You must provide at least one locale.');
		} else if (count($sourceDirectories) === 0) {
			throw new \InvalidArgumentException('You must provide at least one source directory.');
		} else if (count($messageExtractors) === 0) {
			throw new \InvalidArgumentException('You must provide at least one extractor.');
		}

		$fileExtensions = [];
		foreach ($messageExtractors as $extractor) {
			$fileExtension = $extractor->fileExtension();
			$this->messageExtractors[$fileExtension] = $extractor;
			$fileExtensions[] = '.*\.' . $fileExtension;
		}

		$this->fileExtensionsPattern = implode('|', $fileExtensions);
	}


	public function extract(): void
	{
		$messages = [];

		foreach ($this->sourceDirectories as $sourceDirectory) {
			foreach (self::findFilesInDirectory($sourceDirectory, $this->fileExtensionsPattern) as $file) {
				assert(is_array($file));
				$messages = array_merge($messages, $this->extractFile(new \SplFileInfo($file[0])));
			}
		}

		$this->processLocales(array_unique($messages));
	}


	/**
	 * @return list<string>
	 */
	private function extractFile(\SplFileInfo $file): array
	{
		$this->log('Extracting: ' . realpath($file->getPathname()));

		$extractor = $this->messageExtractors[$file->getExtension()];
		return $extractor->extract($file);
	}


	/**
	 * @param list<string> $messages
	 */
	private function processLocales(array $messages): void
	{
		foreach ($this->locales as $locale) {
			$this->process($locale, $messages);
		}
	}


	/**
	 * @param list<string> $messages
	 */
	protected function process(string $locale, array $messages): void
	{
		$this->log(sprintf('Processing locale for [%s]', $locale));

		$existingMessages = $this->loadExistingMessages($locale);

		$this->processMessagesToInsert($locale, array_diff($messages, $existingMessages));

		$this->processMessagesToRemove($locale, array_diff($existingMessages, $messages));
	}


	private static function findFilesInDirectory(string $directory, string $fileExtensionsPattern): \RegexIterator
	{
		$directoryIterator = new \RecursiveDirectoryIterator($directory);
		$recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);
		return new \RegexIterator($recursiveIterator, sprintf('~^(%s)$~i', $fileExtensionsPattern), \RegexIterator::GET_MATCH);
	}


	/**
	 * @return list<string>
	 */
	abstract protected function loadExistingMessages(string $locale): array;


	/**
	 * @param list<string> $messages
	 */
	abstract protected function processMessagesToInsert(string $locale, array $messages): void;


	/**
	 * @param list<string> $messages
	 */
	abstract protected function processMessagesToRemove(string $locale, array $messages): void;


	abstract protected function log(string $message): void;

}
