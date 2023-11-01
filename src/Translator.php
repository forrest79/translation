<?php declare(strict_types=1);

namespace Forrest79\Translation;

class Translator
{
	private bool $debugMode;

	private string $locale;

	/** @var list<string> */
	private array $fallbackLocales = [];

	private Catalogues $catalogues;

	private Logger|NULL $logger = NULL;


	/**
	 * @param list<string> $fallbackLocales
	 * @throws Exceptions\BadLocaleNameException
	 * @throws Exceptions\FallbackLocaleIsTheSameAsMainLocaleException
	 */
	public function __construct(bool $debugMode, Catalogues $catalogues, string $locale, array $fallbackLocales = [])
	{
		$this->debugMode = $debugMode;
		$this->catalogues = $catalogues;

		$locale = strtolower($locale);
		self::checkLocaleName($locale);
		$this->locale = $locale;

		foreach ($fallbackLocales as $fallbackLocale) {
			$fallbackLocale = strtolower($fallbackLocale);
			self::checkLocaleName($fallbackLocale);

			if ($fallbackLocale === $locale) {
				throw new Exceptions\FallbackLocaleIsTheSameAsMainLocaleException(sprintf('Fallback locale \'%s\' is the same as the main locale \'%s\'.', $fallbackLocale, $locale));
			}

			$this->fallbackLocales[] = $fallbackLocale;
		}
	}


	public function getLocale(): string
	{
		return $this->locale;
	}


	/**
	 * @return list<string>
	 */
	public function getFallbackLocales(): array
	{
		return $this->fallbackLocales;
	}


	public function setLogger(Logger $logger): static
	{
		$this->logger = $logger;

		return $this;
	}


	/**
	 * @param array<string|int, string|int|float> $parameters
	 * @throws Exceptions\Exception
	 */
	public function translate(string $message, array $parameters = [], int|NULL $count = NULL): string
	{
		$translation = $this->getTranslation($this->locale, $message, $count);
		if ($translation === NULL) {
			$this->logger?->addUntranslated($this->locale, $message);

			foreach ($this->fallbackLocales as $fallbackLocale) {
				$translation = $this->getTranslation($fallbackLocale, $message, $count);

				if ($translation !== NULL) {
					break;
				}
			}

			if ($translation === NULL) {
				return $message;
			}
		}

		if ($parameters !== []) {
			$translationParams = [];
			foreach ($parameters as $key => $value) {
				$translationParams['%' . $key . '%'] = $value;
			}

			return strtr($translation, $translationParams);
		}

		return $translation;
	}


	private function getTranslation(string $locale, string $message, int|NULL $count): string|NULL
	{
		try {
			return $this->catalogues->getTranslation($locale, $message, $count);
		} catch (Exceptions\Exception $e) {
			return $this->processTranslatorException($e, $message, $locale);
		}
	}


	/**
	 * @throws Exceptions\Exception
	 */
	private function processTranslatorException(Exceptions\Exception $e, string $message, string $locale): string
	{
		if ($this->debugMode) {
			throw $e;
		} else {
			$this->logger?->addError($locale, $e->getMessage());
			return $message;
		}
	}


	public function clearCache(): void
	{
		$this->catalogues->clearCache($this->locale); // @todo tests!!!
	}


	/**
	 * @throws Exceptions\BadLocaleNameException
	 */
	private static function checkLocaleName(string $locale): void
	{
		if (preg_match('/^[a-z0-9_\-]+$/', $locale) === 0) {
			throw new Exceptions\BadLocaleNameException('Only "a-z", "0-9", "_" and "-" characters are allowed for locale name.');
		}
	}

}
