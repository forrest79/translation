<?php declare(strict_types=1);

namespace Forrest79\Translation;

interface MessageExtractor
{

	function fileExtension(): string;


	/**
	 * @return list<string>
	 */
	function extract(\SplFileInfo $file): array;

}
