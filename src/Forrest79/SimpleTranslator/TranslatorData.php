<?php

namespace Forrest79\SimpleTranslator;


abstract class TranslatorData
{
	/** @var string */
	private $locale;

	/** @var array */
	private $messages = [];


	public function __construct($locale, array $messages)
	{
		$this->locale = $locale;
		$this->messages = $messages;
	}


	public function getTranslate($message, $count = NULL)
	{
		if (!isset($this->messages[$message])) {
			return NULL;
		}

		$translate = $this->messages[$message];

		if (is_array($translate) && ($count !== NULL)) {
			$index = $this->getPluralIndex($count);
			if (!isset($translate[$index])) {
				throw new BadCountForPluralMessageException('Message "' . $message . '" for count "' . $count . '" in "' . $this->locale . '" not exists');
			}
			return $translate[$index];
		} else if (is_array($translate) && ($count === NULL)) {
			throw new NoCountForPluralMessageException('You must specify count for "' . $message . '" in "' . $this->locale . '"');
		} else if ($count !== NULL) {
			throw new NotPluralMessageException('Message "' . $message . '" in "' . $this->locale . '" is not plural');
		}

		return $translate;
	}


	abstract protected function getPluralIndex($count);

}
