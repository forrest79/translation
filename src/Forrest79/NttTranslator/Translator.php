<?php

namespace Forrest79\NttTranslator;

use Nette\Localization;
use Nette\Neon;
use Nette\Utils;
use Tracy;


class Translator implements Localization\ITranslator
{
	/** @var bool */
	private $debugMode;

	/** @var string */
	private $localesDir;

	/** @var string */
	private $tempDir;

	/** @var Tracy\ILogger */
	private $logger;

	/** @var string */
	private $locale;

	/** @var string */
	private $fallbackLocale;

	/** @var TranslatorData[] */
	private $data = [];

	/** @var ILocaleUtils */
	private $localeUtils;

	/** @var Diagnostics\Panel */
	private $panel;


	public function __construct($debugMode, $localesDir, $tempDir, Tracy\ILogger $logger)
	{
		$this->debugMode = $debugMode;
		$this->localesDir = $localesDir;
		$this->tempDir = $tempDir;
		$this->logger = $logger;
	}


	public function setLocale($locale)
	{
		$this->locale = strtolower($locale);
	}


	public function getLocale()
	{
		if ($this->locale === NULL) {
			throw new NoLocaleSelectedExceptions();
		}
		return $this->locale;
	}


	public function setFallbackLocale($locale)
	{
		$this->fallbackLocale = strtolower($locale);
	}


	public function translate($message, $parameters = NULL, $count = NULL)
	{
		if (is_array($parameters) && isset($parameters['locale'])) {
			$locale = strtolower($parameters['locale']);
		} else {
			$locale = $this->getLocale();
		}

		if (is_array($parameters) && isset($parameters['count'])) {
			$count = (int) $parameters['count'];
		} else if (is_numeric($parameters)) {
			$count = (int) $parameters;
			$parameters = NULL;
		}

		try {
			$translate = $this->loadData($locale)->getTranslate($message, $count);
		} catch (TranslatorException $e) {
			return $this->processTranslatorException($e, $message, $locale);
		}
		if ($translate === NULL) {
			if ($this->panel) {
				$this->panel->addUntranslated($locale, $message);
			} else {
				$this->logger->log('No translation for "' . $message . '" in locale "' . $locale . '"', 'translator');
			}

			if (($this->fallbackLocale !== NULL) && ($this->fallbackLocale !== $locale)) {
				try {
					$translate = $this->loadData($this->fallbackLocale)->getTranslate($message, $count);
				} catch (TranslatorException $e) {
					return $this->processTranslatorException($e, $message, $this->fallbackLocale);
				}
			}

			if ($translate === NULL) {
				return $message;
			}
		}

		if (is_array($parameters) && $parameters) {
			$tmp = [];
			foreach ($parameters as $key => $value) {
				$tmp['%' . trim($key, '%') . '%'] = $value;
			}
			$parameters = $tmp;

			return strtr($translate, $parameters);
		}

		return $translate;
	}


	private function processTranslatorException(TranslatorException $e, $message, $locale)
	{
		if ($this->debugMode) {
			throw $e;
		} else {
			$this->logger->log('Translation error "' . $e->getMessage() . '" in locale "' . $locale . '"', 'translator');
			return $message;
		}
	}


	private function loadData($locale)
	{
		if (!isset($this->data[$locale])) {
			$localeFile = $this->localesDir . DIRECTORY_SEPARATOR . $locale . '.neon';
			$localeCache = $this->tempDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . $locale . '.php';
			if (!file_exists($localeCache) || ($this->debugMode && (!file_exists($localeFile) || (filemtime($localeFile) > filemtime($localeCache))))) {
				if (!file_exists($localeFile)) {
					throw new NoLocaleFileException('Locale file "' . $localeFile . '" doesn\'t exists');
				}
				try {
					$data = Neon\Neon::decode(file_get_contents($localeFile));
					if (!isset($data['plural']) || !isset($data['messages'])) {
						throw new PluralSectionMissingException('You must have "plural" and "messages" section in neon');
					}

					$pluralCondition = '';
					foreach ($data['plural'] as $i => $plural) {
						$pluralCondition .= (($i > 0) ? 'else ' : '') . 'if (' . str_replace('n', '$count', $plural) . ') return ' . $i . ';';
					}

					$localeData = '';
					foreach ($data['messages'] as $identificator => $translate) {
						if (is_array($translate)) {
							$translateData = '';
							foreach ($translate as $translateItem) {
								$translateData .= '\'' . addslashes($translateItem) . '\',';
							}
							$translateData = '[' . $translateData . ']';
						} else {
							$translateData = '\'' . addslashes($translate) . '\'';
						}
						$localeData .= '\'' . $identificator . '\'=>' . $translateData . ',';
					}

					Utils\FileSystem::createDir(dirname($localeCache), 0755);
					file_put_contents($localeCache . '.tmp', '<?php class TranslatorData' . ucfirst($locale) . ' extends Forrest79\NttTranslator\TranslatorData {protected function getPluralIndex($count) {' . $pluralCondition . 'throw new Forrest79\NttTranslator\TranslateException(\'No definition for count \' . $count);}}; return new TranslatorData' . ucfirst($this->locale) . '(\'' . $this->locale . '\', [' . $localeData . ']);');
					rename($localeCache . '.tmp', $localeCache); // atomic replace (in Linux)
					if ($this->localeUtils !== NULL) {
						$this->localeUtils->afterCacheBuild($locale, $localeFile, $localeCache);
					}
				} catch (Neon\Exception $e) {
					throw new ParsingErrorException('Error parsing Neon: ' . $e->getMessage(), 0, $e);
				}
			}

			$this->data[$locale] = require $localeCache;

			if ($this->panel) {
				$this->panel->addLocaleFile($locale, $localeFile);
			}
		}

		return $this->data[$locale];
	}


	public function setLocaleUtils(ILocaleUtils $localeUtils)
	{
		$this->localeUtils = $localeUtils;
	}


	public function setPanel(Diagnostics\Panel $panel)
	{
		$this->panel = $panel;
	}

}
