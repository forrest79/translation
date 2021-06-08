<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

abstract class TranslatorData
{
	private string $locale;

	/** @var array<string|array<string>> */
	private array $messages;


	/**
	 * @param array<string|array<string>> $messages
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
	public function getTranslate(string $message, ?int $count = NULL): ?string
	{
		if (!isset($this->messages[$message])) {
			return NULL;
		}

		$translate = $this->messages[$message];

		if (is_array($translate) && ($count !== NULL)) {
			$index = $this->getPluralIndex($count);
			if (!isset($translate[$index])) {
				throw new Exceptions\BadCountForPluralMessageException(sprintf('Message "%s" for count "%d" in "%s" not exists', $message, $count, $this->locale));
			}
			return $translate[$index];
		} else if (is_array($translate) && ($count === NULL)) {
			throw new Exceptions\NoCountForPluralMessageException(sprintf('You must specify count for "%s" in "%s"', $message, $this->locale));
		} else if ($count !== NULL) {
			throw new Exceptions\NotPluralMessageException(sprintf('Message "%s" in "%s" is not plural', $message, $this->locale));
		}

		return $translate;
	}


	abstract protected function getPluralIndex(int $count): int;

}
