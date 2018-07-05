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


	/**
	 * @inheritdoc
	 */
	public function isLocaleUpdated(string $locale, string $cacheFile): bool
	{
		$localeFile = $this->source($locale);
		return !file_exists($localeFile) || (filemtime($localeFile) > filemtime($cacheFile));
	}


	/**
	 * @inheritdoc
	 * @throws SimpleTranslator\Exceptions\NoLocaleFileException
	 * @throws SimpleTranslator\Exceptions\ParsingErrorException
	 */
	public function loadData(string $locale): array
	{
		$localeFile = $this->source($locale);
		if (!file_exists($localeFile)) {
			throw new SimpleTranslator\Exceptions\NoLocaleFileException(sprintf('Locale file "%s" doesn\'t exists', $localeFile));
		}
		try {
			return NetteNeon\Neon::decode(file_get_contents($localeFile));
		} catch (NetteNeon\Exception $e) {
			throw new SimpleTranslator\Exceptions\ParsingErrorException('Error parsing Neon: ' . $e->getMessage(), 0, $e);
		}
	}


	/**
	 * @inheritdoc
	 */
	public function source(string $locale): string
	{
		return $this->localesDir . DIRECTORY_SEPARATOR . $locale . '.neon';
	}

}
