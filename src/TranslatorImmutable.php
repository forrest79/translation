<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator;

class TranslatorImmutable implements ITranslator
{
	/** @var Translator */
	private $translator;

	/** @var string */
	private $locale;


	public function __construct(Translator $translator, string $locale)
	{
		$this->translator = $translator;
		$this->locale = $locale;
	}


	public function getLocale(): string
	{
		return $this->locale;
	}


	/**
	 * @param mixed $message string
	 * @param mixed $parameters int|array|NULL (int = count, array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 * @param int|NULL $count
	 * @return string
	 * @throws Exceptions\CantChangeLocaleForImmutableTranslatorException
	 * @throws Exceptions\Exception
	 */
	public function translate($message, $parameters = NULL, ?int $count = NULL): string
	{
		if (is_array($parameters) && isset($parameters[self::PARAM_LOCALE]) && (strcasecmp($parameters[self::PARAM_LOCALE], $this->locale) !== 0)) {
			throw new Exceptions\CantChangeLocaleForImmutableTranslatorException(sprintf('Immutable translator is set with "%s" locale, you tried to use "%s" locale.', $this->locale, $parameters[self::PARAM_LOCALE]));
		}

		if (is_numeric($parameters)) {
			$count = (int) $parameters;
			$parameters = [];
		} else if (!is_array($parameters)) {
			$parameters = [];
		}

		$parameters[self::PARAM_LOCALE] = $this->locale;

		return $this->translator->translate($message, $parameters, $count);
	}

}
