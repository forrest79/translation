<?php declare(strict_types=1);

namespace Forrest79\Translation;

abstract class Catalogue
{
	private string $locale;

	/** @var array<string, string|list<string>> */
	private array $messages;


	/**
	 * @param array<string, string|list<string>> $messages
	 */
	public function __construct(string $locale, array $messages)
	{
		$this->locale = $locale;
		$this->messages = $messages;
	}


	/**
	 * @throws Exceptions\BadCountForPluralMessageException
	 * @throws Exceptions\NoCountForPluralMessageException
	 * @throws Exceptions\NotPluralMessageException
	 */
	public function getTranslation(string $message, int|null $count = null): string|null
	{
		if (!isset($this->messages[$message])) {
			return null;
		}

		$translation = $this->messages[$message];

		if (is_array($translation) && ($count !== null)) {
			$index = $this->getPluralIndex($count);
			if (!isset($translation[$index])) {
				throw new Exceptions\BadCountForPluralMessageException(sprintf('Message "%s" for count "%d" in "%s" not exists', $message, $count, $this->locale));
			}
			return $translation[$index];
		} else if (is_array($translation) && ($count === null)) {
			throw new Exceptions\NoCountForPluralMessageException(sprintf('You must specify count for "%s" in "%s"', $message, $this->locale));
		} else if ($count !== null) {
			throw new Exceptions\NotPluralMessageException(sprintf('Message "%s" in "%s" is not plural', $message, $this->locale));
		}

		return $translation;
	}


	abstract protected function getPluralIndex(int $count): int;

}
