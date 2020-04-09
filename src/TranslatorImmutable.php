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
	 * translate(string $message, int|array|NULL $translateParameters = NULL, ?int $count = NULL): string
	 *   param string $message
	 *   param int|array|NULL $translateParameters (int = count; array = parameters, can contains self::PARAM_COUNT and self::PARAM_LOCALE value)
	 *   param int|NULL $count
	 *
	 * @param mixed $message
	 * @param mixed ...$parameters
	 * @throws Exceptions\CantChangeLocaleForImmutableTranslatorException
	 * @throws Exceptions\Exception
	 */
	public function translate($message, ...$parameters): string
	{
		$translationParams = $parameters[0] ?? NULL;
		$count = $parameters[1] ?? NULL;

		if (is_array($translationParams) && isset($translationParams[self::PARAM_LOCALE]) && (strcasecmp($translationParams[self::PARAM_LOCALE], $this->locale) !== 0)) {
			throw new Exceptions\CantChangeLocaleForImmutableTranslatorException(sprintf('Immutable translator is set with "%s" locale, you tried to use "%s" locale.', $this->locale, $translationParams[self::PARAM_LOCALE]));
		}

		if (is_numeric($translationParams)) {
			$count = (int) $translationParams;
			$translationParams = [];
		} else if (!is_array($translationParams)) {
			$translationParams = [];
		}

		$translationParams[self::PARAM_LOCALE] = $this->locale;

		return $this->translator->translate($message, $translationParams, $count);
	}

}
