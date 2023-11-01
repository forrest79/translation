<?php declare(strict_types=1);

namespace Forrest79\Translation\CatalogueLoaders;

use Forrest79\Translation;
use Nette\Neon as NetteNeon;

class Neon implements Translation\CatalogueLoader
{
	private string $localesDir;


	public function __construct(string $localesDir)
	{
		$this->localesDir = $localesDir;
	}


	public function isLocaleUpdated(string $locale, string $cacheFile): bool
	{
		$localeFile = $this->source($locale);
		return file_exists($localeFile) && file_exists($cacheFile) && (filemtime($localeFile) > filemtime($cacheFile));
	}


	/**
	 * @return array<string, string|array<string, string|list<string>>|NULL>
	 * @throws Translation\Exceptions\IOException
	 * @throws Translation\Exceptions\ParsingErrorException
	 */
	public function loadData(string $locale): array
	{
		$localeFile = $this->source($locale);

		$data = @file_get_contents($localeFile); // intentionally @ - file may not exists
		if ($data === FALSE) {
			throw new Translation\Exceptions\IOException(sprintf('Locale file "%s" doesn\'t exists', $localeFile));
		}

		try {
			$decodedData = NetteNeon\Neon::decode($data);
			assert(is_array($decodedData));
			return $decodedData;
		} catch (NetteNeon\Exception $e) {
			throw new Translation\Exceptions\ParsingErrorException('Error parsing Neon: ' . $e->getMessage(), 0, $e);
		}
	}


	public function source(string $locale): string
	{
		return $this->localesDir . DIRECTORY_SEPARATOR . $locale . '.neon';
	}

}
