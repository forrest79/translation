<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

abstract class TranslatorData
{
	/** @var string */
	private $locale;

	/** @var array */
	private $messages = [];


	public function __construct(string $locale, array $messages)
	{
		$this->locale = $locale;
		$this->messages = $messages;
	}


	/**
	 * @param string $message
	 * @param int|NULL $count
	 * @return string|array
	 * @throws Exceptions\BadCountForPluralMessageException
	 * @throws Exceptions\NoCountForPluralMessageException
	 * @throws Exceptions\NotPluralMessageException
	 */
	public function getTranslate(string $message, ?int $count = NULL)
	{
		if (!isset($this->messages[$message])) {
			return NULL;
		}

		$translate = $this->messages[$message];

		if (is_array($translate) && ($count !== NULL)) {var_dump(1);
			$index = $this->getPluralIndex($count);
			if (!isset($translate[$index])) {
				throw new Exceptions\BadCountForPluralMessageException('Message "' . $message . '" for count "' . $count . '" in "' . $this->locale . '" not exists');
			}
			return $translate[$index];
		} else if (is_array($translate) && ($count === NULL)) {var_dump(2);
			throw new Exceptions\NoCountForPluralMessageException('You must specify count for "' . $message . '" in "' . $this->locale . '"');
		} else if ($count !== NULL) {
			throw new Exceptions\NotPluralMessageException('Message "' . $message . '" in "' . $this->locale . '" is not plural');
		}

		return $translate;
	}


	abstract protected function getPluralIndex(int $count): int;

}
