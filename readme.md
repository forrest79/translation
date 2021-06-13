# Forrest79/SimpleTranslator

[![Latest Stable Version](https://poser.pugx.org/forrest79/simple-translator/v)](//packagist.org/packages/forrest79/simple-translator)
[![Monthly Downloads](https://poser.pugx.org/forrest79/simple-translator/d/monthly)](//packagist.org/packages/forrest79/simple-translator)
[![License](https://poser.pugx.org/forrest79/simple-translator/license)](//packagist.org/packages/forrest79/simple-translator)
[![Build](https://github.com/forrest79/SimpleTranslator/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/SimpleTranslator/actions/workflows/build.yml)

Simple and fast translator for Nette Framework based (default) on neon locale files.


## Requirements

Forrest79/SimpleTranslator requires PHP 7.4 or higher and [Nette Framework](https://github.com/nette/nette) 3.0.


## Installation

* Install Forrest79/SimpleTranslator to your project using [Composer](http://getcomposer.org/):

```sh
$ composer require forrest79/simple-translator
```


## Documentation

This translator is basically distributes with [neon format](https://ne-on.org/) data loader for locale files. You must specify `plural` section and `messages` section.

Plural section contains conditions for plurals. It operates with `n` variable. Each line is one condition. When you need some message in plural, you must provide as much messages as plural conditions and first condition is equal to first message and so on.

Example for english and czech plurals:

```yml
plural:
    - 'n === 1'
    - 'n > 1'
```

```yml
plural:
    - 'n === 1'
    - '(n > 1) && (n < 5)'
    - 'n >= 5'
```

Messages can be simple - ```key => translation```. Or can contains variables (`%var%`) or plurals.

```yml
messages:
    simpleMessage: This is simple message.
    messageWithVariable: Hello %user%.
    pluralMessageForEn:
        - One item.
        - More items.
    pluralMessageForEnWithVariable
        - I have %count% car for user %user%.
        - I have %count% cars for user %user%.
```

And you can translate these messages like this:

```php
echo $translator->translate('simpleMessage');
echo $translator->translate('messageWithVariable', ['user' => 'Jakub']);
echo $translator->translate('pluralMessageForEn', 1);
echo $translator->translate('pluralMessageForEn', 5);
echo $translator->translate('pluralMessageForEnWithVariable', ['count' => 1, 'user' => 'Jakub']);
echo $translator->translate('pluralMessageForEnWithVariable', ['count' => 5, 'Jakub']); // or use $translator::PARAM_COUNT instead of 'count'
```

Second parameter of translate function can be count for plural or variables (array). If you use variables, there are two special values. First is `count`, that is used as count for plural and second is `locale`, that can set locale to translate.

```php
echo $translator->translate('simpleMessage', ['locale' => 'cs']); // message in 'cs' locale even if translator is set to 'en' (or other different) locale, you can use $translator::PARAM_LOCALE instead of 'locale'
```

When you use second parameter for variables, you can use third for plural count:

```php
echo $translator->translate('pluralMessageForEnWithVariable', ['user' => 'Jakub'], 1);
echo $translator->translate('pluralMessageForEnWithVariable', ['user' => 'Jakub'], 5);
```

When you want to be sure, that you have translator with set locale and be sure, that no one can change this locale, you can call ```createImmutableTranslator($locale)``` that return ```TranslatorImmutable``` object that has the same interface ```SimpleTranslator\ITranslator``` as main translator and always return translation in provided locale and on this object locale can be changed to another.

Locale files in neon format must have name `locale.neon`. For example for `en` locale is file name `en.neon`.

Enable this extension using your neon config and add as parameter debug mode:

```yml
extensions:
    translator: Forrest79\SimpleTranslator\Nette\DI\Extension(%debugMode%)
```

Default settings is (this works out of the box):

```yml
translator:
    locale: NULL # can set manual locale
    fallbackLocale: NULL # can set locale that is used, when main locale does't have message to translate (this is logged)
    dataLoader: NULL # will use as default DataLoaders\Neon data loader, you can specify your own ('@customDataLoaderService')
    localesDir: %appDir%/locales # directory with neon files for Neon data loader
    tempDir: %tempDir% # for cached translation files: tempDir/cache/locales
    localeUtils: null # auto detect - use Zend OpCache clean if it's detect or you can pass service name ('@customLocaleUtilsService')
    latteFilter: TRUE # when TRUE and Latte is used in application, trehe is automatically registered translate filter
    requestResolver: locale # FALSE = disable
    debugger: TRUE # when TRUE - show Tracy bar in debug mode
```

Translations are cached to PHP files. In debug mode, cache is rebuild when translation definition is changed, in productin mode is cache build only once and translation source definitions are not checked for changes. If you want to regenerate cache, you can clean cache by calling 'clearCache($locale)'.

If you are using Zend OpCache, then there is default after build cache hook, that remove this file from OpCache. For other opcaches (or if you want to do anything else after cache is built), you can write your own hook by writing object with ```LocaleUtils``` interface and register it to translator via neon setting ```localeUtils``` or manually with ```setLocaleUtils($localeUtils)```. Method `afterCacheBuild()` is called after new cache is build. There is also posibility to do something after manually clearing cache (`afterCacheClear()`) via `Translator::clearCache()` method.

Translator is registered to Nette and Latte. By default, there is resolver, that set actual locale from router variable. Default is `locale` variable, but it can be changed in configuration. Or you can set this to FALSE and then you must call `setLocale($locale)` manually. You can also set fallback locale, which is used, when main locale translation doen't exists.

As default translations are loaded from neon files. This translator is shipped only with this possibility but you can write your own data loader to load translation from the source you want. Just implement ```DataLoader``` interface to some object and set this object via neon settings ```dataLoader``` or by calling ```setDataLoader($dataLoader)```. This interface has three methods:
- ```isLocaleUpdated(string $locale, string $cacheFile)``` that returns ```true/false``` if cache needs to be rebuild in debug mode
- ```loadData(string $locale)``` that returns array with two keys, ```plural``` definition and ```messages``` with array ```key => trasnlate```
- ```source(string $locale)``` that return source identification, file path for neon file or whatever you want

In debug mode, there is a Tracy panel that shows untranslated messages and loaded locales and also this messages are saved to log. In production mode, only saving to log is active.
