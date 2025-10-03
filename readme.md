# Forrest79/Translation

[![Latest Stable Version](https://poser.pugx.org/forrest79/translation/v)](//packagist.org/packages/forrest79/translation)
[![Monthly Downloads](https://poser.pugx.org/forrest79/translation/d/monthly)](//packagist.org/packages/forrest79/translation)
[![License](https://poser.pugx.org/forrest79/translation/license)](//packagist.org/packages/forrest79/translation)
[![Build](https://github.com/forrest79/translation/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/translation/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/forrest79/translation/graph/badge.svg?token=67FHZ4MKLV)](https://codecov.io/gh/forrest79/translation)

Simple and fast translator and tools to internationalize your PHP application.


## Requirements

Forrest79/Translation requires PHP 8.0 or higher.


## Installation

* Install Forrest79/Translation to your project using [Composer](http://getcomposer.org/):

```sh
$ composer require forrest79/translation
```


## Documentation

### Basics

There is main `Translator` object to translate messages. Every `Translator` object is immutable for concrete locale and can be createdvia `TranslatorFactory` or manually.

Messages are loaded by `CatalogueLoader`s. You can write your own or use the shipped one - loader from [neon](https://ne-on.org/) files.

For the best performance, Catalogues are cached into PHP files. It's your responsibility to invalidate cache to force reload catalogue. Neon loader is automatically invalidating cache in debug mode when catalogue neon file is updated.

`CatalogueLoader` can return plural definition for locale (if it is missing, translator will try to use internal definition based on locale/language code) and must return messages structure with translations for passed locale.      

Plural section contains condition for plurals. Input is count, and return is a zero-based index for contrete plural translation. It operates with `$i` variable and example for english could look like `($i === 1) ? 0 : 1`. This says - if count is `1`, get translation on position `0`, otherwise get translation on position `1`. So all english plural messages must contain two translations.

#### Plurals helper

Plural definition for most of the languages is defined in `PluralsHelper` that is basen on [symfony/translation](https://github.com/symfony/translation). It's used internally when plural defintion is missing in the catalogue.

To proper locale detection, use the correct locale name - 2-chars language codes (`en`, `de`, `fr`, `cs`, ...) or locale code (`en_US`, `en_GB`, `de_DE`, `fr_FR`, `cs_CZ`, ...).

### Translator

The `Translator` object has the main method `translate(string $message, array $parameters = [], int|null $count = null)`.

The only required parameters is the `$message`. As the name sugges, it's the message from your catalogue to translate. Prefer to use identifiers (`web.form.password`) as messages, not real texts (`Enter password`).

Your message can contain some dynamic parameter replaced during translation with the actual value. Pass these parameters as the second parameter `$parameters`. For example:

```php
$translator->translate('web.error.message', ['max_length' => Validator::MAX_LENGTH]);
``` 

Message must contain `%max_length%` parameter - it's the parmameter name surrounded by `%` character. For example `Text must be %max_length% long.`.

And finally, if you're translating a plural message, use the third parameter `$count`. In combination with the parameters:

```php
$translator->translate('web.error.message', ['max_length' => Validator::MAX_LENGTH, 'entered_length' => number_format(strlen($text))], strlen($text));
```

And the message could be something like:

```php
[
    'web.error.message' => [
        'Text must be %max_length% long. You enter %entered_length% character.',
        'Text must be %max_length% long. You enter %entered_length% characters.',
    ],
]
```

Or just a simple plural message:

```php
$translator->translate('web.error.message', count: strlen($text));
```

And the message could be something like:

```php
[
    'web.error.message' => [
        'Only one characted was entered.',
        'Too many characters was entered.',
    ],
]
```

Other methods are only to set logger (`setLogger()` - more about this later), to get current locale (`getLocale()`), current fallback locales (`getFallbackLocales()`) and clean locale cache (`cleanCache()` - cache will be rebuilt on the next request).

To create a `Translator` object, you must provide:
- `bool $debugMode` - in debug mode is raised exception about errors (not about missing translations), in production mode, these are only logged
- `Catalogues $catalogues` object - more about this below
- `string $locale` - locale name
- `array $fallbackLocales` - fallback locales name, in priority order. When translation in the main locale is missing (info about this is logged), the first fallback locale is tried if this translation is also missing, the second is tried and so on... If no translation is found, the message identifier is returned as translation.

The `Catalogues` object is the heart of this library. It provides catalogue loading, caching, invalidation cache and searching messages in the catalogue.

To create this object, you must provide:
- `bool $debugMode` - in the debug mode can be the catalogue automatically reloaded. In the production mode, you must manually remove cached file (the reason why is automatically reload working only in the debug mode is, that checking if reload should be made can be expensive to be made on every request)
- `string $tempDir` - there will ba saved cached locales `$tempDir/cache/locales`
- `CatalogueLoader $catalogueLoader` - some catalogue loader, your own (for example, to load messages from the datatabase) or internal `Neon` catalogue loader
- `CatalogueUtils $catalogueUtils` - optional, more about this later

You can prepare these objects manually, but the preferred way is to use `TranslatorFactory`...  


#### TranslatorFactory

`TranslatorFactory` will help you create `Translator` object. To create a factory, you must provide:

- `bool $debugMode`
- `string $tempDir`
- `CatalogueLoader $catalogueLoader`
- `array $fallbackLocales` - *optional* - you can for locales their fallback locales - for example `['en' => ['de', 'fr'], 'fr' => ['de'], 'de' => ['en']]` - english has fallback german and french, french has fallback germen and german has fallback english, locales without fallback definition has no fallback, you don't to put it here with a blank array
- `CatalogueUtils $catalogueUtils` - *optional* - if none is provided and an Opcache extension is loaded (function `opcache_invalidate()` exists), the `CatalogueUtils\Opcache` is used automatically
- `Logger $logger` - *optional*

With prepated `TranslatorFactory` just call `create(string $locale, array|null $fallbackLocales = null)` method, and you will get `Translator` object for `$locale`.

If `$fallbackLocales` is `null`, fallback locales are get from the one defined on `TranslatorFactory`, if it's an array (even blank), this value is used. 

`Translator` objects for the same `$locale` and `$fallbackLocales` are cached. If you call `create()` method twice for the same parameters, you will get the same `Translator` object.


### Catalogues

Messages in catalogue can be simple pair - `message => translation`. Or can contain variables (`%var%`) or plurals.

This is the example in `neon` format: 

```yml
messages:
    simpleMessage: This is simple message.
    messageWithVariable: Hello %user%.
    pluralMessageForEn:
        - One item.
        - More items.
    pluralMessageForEnWithVariable:
        - I have %count% car for user %user%.
        - I have %count% cars for user %user%.
```

A catalogue can contain info about plurals. Example in `neon` format for `en` locale:

```yml
plural: '($i === 1) ? 0 : 1'
```

If you use internal `CatalogueLoaders\Neon` catalogue loader, you must pass directory where the neon files are stored in constructor.

Then, when, for example, you want to create `Translator` for `en` locale, the `en.neon` file is used. For `en_US` locale, the `en_US.neon` file is used.

If you want to implement your own `CatalogueLoader`, you must implement these methods from the interface:
- `isLocaleUpdated(string $locale, string $cacheFile): bool` - returns `true` if cache needs to be rebuild in debug mode, `false` otherwise (`CatalogueLoaders\Neon` returns `true` is source neon file was updated)
- `loadData(string $locale): array` - returns array with two keys, `plural` (*optional*) definition and `messages` with array `message => translation|list<translation>` (the list if for plural messages)
- `source(string $locale): string` - return source identification, file path for neon file or whatever you want to identify the correct source for the locale


### CatalogueUtils

`CatalogueUtils` object can react on the two events:

- when the cache file is built (the PHP cache file is created in the temp directory) - `afterCacheBuild(string $locale, string $source, string $localeCache)` method
- when the cache is clear (the PHP cache file is deleted via `Translator::clearCache()` or `Catalogues::clearCache(string $locale)`) - `afterCacheClear(string $locale, string $localeCache)` method

By default, with `TranslatorFactory`, when the Opcache extension is loaded, the shipped one `CatalogueUtils\Opcache` object is used.
This one will clear PHP cache file from Opcache. This could be tricky if you have on production environment set to not automatically check for changed PHP file (or have some big time to check).
Then after the cache is cleared or rebuilt, you will still see the old PHP file content till Opache reload PHP file content. This `CatalogueUtils` will clear Opcache for this file immediatelly.


### Logger


If you want to be informed about not existing translations, errors and loaded locales during the request, you can optionally use a `Logger`.

`Logger` implements 3 methods:

- `addUntranslated(string $locale, string $message)` is called when the message has no translation in the main locale (not in the one of the fallback locales)
- `addError(string $locale, string $error)` - is called only in production mode (in debug mode is an exception thrown) when some error occurs, for an example if you tried to translate a singular message as a plural one or the opposite 
- `addLocaleFile(string $locale, string $source)` - is called when new locale is loaded with the `Catalogues` object

In this library are shipped two loggers (you can write your own):

- `Loggers\TracyLogger` - prepared for a production environment, logs untranslated messages and errors using `Tracy\ILogger`
- `Loggers\TracyBarPanel` - prepared for a development environemnt, shows untranslated messages and loaded catalogues in Tracy BarPanels


### Extractor

To successfully translate your application, you need to know about all texts need to be translated. One of the best options to achieve this
is to extract all text from your application source code. To help you with this, the library is shipped with `CatalogueExtractor` and `MessageExtractors`.

`MessageExtractors` extract text from source code, `CatalogueExtractor` check existing translations and compares it with extracted messages and tell
you what you need to add or delete from your translations.

There are two existing `MessageExtractors`:
- `Php` - extract all calls for method `->translate()` and use the first parameter as a message identifier (`->translate('identifier', count: 3)` will extract `identifier` as a message)
- `Latte` - extract all usages of `translate` filter and use the first parameter as a message identifier (`{='identifier'|translate:3}` and `{var $trans = ('identifier'|translate:['var' => 'test'])}` will extract `identifier` as a message)

There is one existing `CatalogueExtractor` - `Neon`. It can read existing translations from locale neon files and add a new or remove old message. It also keeps your comments in neon files.

To use (for example) `Neon` catalogue extractor, you must prepare a simple PHP script. For example, for a cli, it could look like: 

```php
#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$localesDir = __DIR__ . '/../app/locales';

$locales = ['en', 'cs'];

$sourceDirectories = [
	__DIR__ . '/../app',
];

$latteEngine = new Latte\Engine();
$latteEngine->addExtension(new Nette\Bridges\ApplicationLatte\UIExtension(null));
$latteEngine->addExtension(new Nette\Bridges\FormsLatte\FormsExtension());

$messageExtractors = [
	new Forrest79\Translation\MessageExtractors\Php(),
	new Forrest79\Translation\MessageExtractors\Latte($latteEngine),
];

(new Forrest79\Translation\CatalogueExtractors\Neon($localesDir, $locales, $sourceDirectories, $messageExtractors))
	->extract();
```

The catalogue extractor needs to know:
- what are the locales to process (`$locales`)
- what are the directories with the source code files (`$sourceDirectories`)
- what message extractors use - `Php` has no dependencies, for `Latte` you must prepare `Latte\Engine`

Neon catalogu extractor also needs to know a directory, where to source neon locales are saved (`$localesDir`).

> It's a good idea don't use variables as a message identifier in the `translate` method or latte filter because this can't be extracted.
>   - `$id = 'identifier'; $translator->translate($id)` - message extractor don't know, that `$id` is `identifier` and this message is skipped
>   - `$translator->translate('identifier')` - this is OK
> When you need to use variables, you must manually add there message to the catalogue and update extractor to not delete them.

To use your own catalogue extractor you must extend `CatalogueExtractor`. For example, a simple extractor working with a DB can look like this:

```php
$locales = ['en', 'cs'];

$sourceDirectories = [
	__DIR__ . '/../app',
];

$messageExtractors = [
	new Forrest79\Translation\MessageExtractors\Php(),
];

(new class($dbConnection, $locales, $sourceDirectories, $messageExtractors) extends Forrest79\Translation\CatalogueExtractor {
	private DbConnection $dbConnection;

	public function __construct(
		DbConnection $dbConnection,
		array $locales,
		array $sourceDirectories,
		array $messageExtractors,
	)
	{
		parent::__construct($locales, $sourceDirectories, $messageExtractors);
		$this->dbConnection = $dbConnection;
	}

	protected function loadExistingMessages(string $locale): array
	{
		return $this->dbConnection->query('
			SELECT ti.identifier
			  FROM public.translation_identifiers AS ti
			  LEFT JOIN public.translations AS t ON t.identifier_id = ti.id AND t.lang = ?
		', $locale)->fetchPairs(null, 'identifier');
	}

	protected function processMessagesToInsert(string $locale, array $messages): void
	{
		foreach ($messages as $message) {
			$this->log(sprintf('SELECT public.translation_insert(constant.lang_%s(), \'%s\', ARRAY[\'\']);', $locale, $message));
		}
	}

	protected function processMessagesToRemove(string $locale, array $messages): void
	{
		foreach ($messages as $message) {
			$this->log(sprintf('DELETE FROM public.translation_identifiers WHERE identifier = \'%s\';', $message));
		}
	}

	protected function log(string $message): void
	{
		echo $message . PHP_EOL;
	}

})
	->extract();
```

### Nette

This library can be simply integrated into [Nette Framework](https://nette.org/). You already know about two loggers `TracyLogger` and `TracyBarPanel` for [Tracy debugging tool](https://tracy.nette.org/).

There is also special `Nette\TranslatorFactory`. This factory extends classic `TranslatorFactory` and add one method `createByRequest(Application\Application $application, string|null $defaultLocale = null)`.

This method tries to detect current locale from application request. If there is none locale, `$defaultLocale` is used (when the `$defaultLocale` is `null`, an exception is thrown).

The preferred way is to register services in your DI container:

```yaml
services:
    - Forrest79\Translation\Nette\TranslatorFactory(%debugMode%, %tempDir%, parameter: lang, fallbackLocales: ['en': ['cs'], 'cs': ['en']])::createByRequest()
    - Forrest79\Translation\CatalogueLoaders\Neon(%appDir%/locales)
    - Forrest79\Translation\Loggers\TracyLogger
```

Or define factory on the two lines:

```yaml
services:
    - Forrest79\Translation\Nette\TranslatorFactory(%debugMode%, %tempDir%, parameter: lang, fallbackLocales: ['en': ['cs'], 'cs': ['en']])
    - @Forrest79\Translation\Nette\TranslatorFactory::createByRequest()
    - Forrest79\Translation\CatalogueLoaders\Neon(%appDir%/locales)
    - Forrest79\Translation\Loggers\TracyLogger
```

This will register `Translator` object always set with the correct locale into your DI container. The `parameter` defines what parameter is used from the request.
It could be some query parameter or parameter comes from a router. Also default `Neon` catalogue loader is used.

If you want to overwrite some service, for example, to use `TracyDebugPanel` on you local environment, use names services:

```yaml
services:
    translationLogger: Forrest79\Translation\Loggers\TracyLogger
```

And overwrite it in your local config:

```yaml
services:
    translationLogger: Forrest79\Translation\Loggers\TracyBarPanel::register()
```

You can define your own `CatalogueUtils`:

```yaml
services:
	- Forrest79\Translation\CatalogueUtils\Opcache
```

But this one is used automatically, unless you choose another one.

You probably want to register translator also to [Latte](https://latte.nette.org). You can create your own [Latte Extension](https://latte.nette.org/en/creating-extension) with the `translate` filter or
simply register `translate` filter to the template. Filter can look like this: 

```php
$template->addFilter('translate', function (string $message, array|int $parameters = [], int|null $count = null): string {
    if (is_int($parameters)) {
        $count = $parameters;
        $parameters = [];
    }

    return $this->translator->translate($message, $parameters, $count);
});
```

And in latte is call:

```
{='identifier'|translate}
{='identifier_with_var'|translate:['var' => 'test']}
{='identifier_plural'|translate:3}
{='identifier_with_var_plural'|translate:['var' => 'test'],3}
```

Or save output to a variable:

```
{var $trans = ('identifier'|translate)}
```
