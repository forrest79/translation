<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\DataLoaders;

use Forrest79\SimpleTranslator;
use Nette\Neon as NetteNeon;

class Neon implements SimpleTranslator\DataLoader
{
	/** @var string */
	private $localesDir;


	public function __construct(string $localesDir)
	{
		$this->localesDir = $localesDir;
	}


	public function isLocaleUpdated(string $locale, string $cacheFile): bool
	{
		$localeFile = $this->source($locale);
		return !file_exists($localeFile) || (filemtime($localeFile) > filemtime($cacheFile));
	}


	/**
	 * @return array<string, array<string|array<string>>>
	 * @throws SimpleTranslator\Exceptions\NoLocaleFileException
	 * @throws SimpleTranslator\Exceptions\ParsingErrorException
	 */
	public function loadData(string $locale): array
	{
		$localeFile = $this->source($locale);
		$data = @file_get_contents($localeFile); // intentionally @
		if ($data === FALSE) {
			throw new SimpleTranslator\Exceptions\NoLocaleFileException(sprintf('Locale file "%s" doesn\'t exists', $localeFile));
		}
		try {
			return NetteNeon\Neon::decode($data);
		} catch (NetteNeon\Exception $e) {
			throw new SimpleTranslator\Exceptions\ParsingErrorException('Error parsing Neon: ' . $e->getMessage(), 0, $e);
		}
	}


	public function source(string $locale): string
	{
		return $this->localesDir . DIRECTORY_SEPARATOR . $locale . '.neon';
	}

}
