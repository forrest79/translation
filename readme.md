Forrest79/SimpleTranslator
==========================

[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/forrest79/SimpleTranslator/blob/master/license.md)
[![Build Status](https://travis-ci.org/forrest79/SimpleTranslator.svg?branch=master)](https://travis-ci.org/forrest79/SimpleTranslator)
[![Downloads this Month](https://img.shields.io/packagist/dm/forrest79/ntt-translator.svg)](https://packagist.org/packages/forrest79/ntt-translator)
[![Latest stable](https://img.shields.io/packagist/v/forrest79/ntt-translator.svg)](https://packagist.org/packages/forrest79/ntt-translator)

Simple and fast translator for Nette Frameword based on neon locale files.


Requirements
------------

Forrest79/SimpleTranslator requires PHP 7.1 or higher.

- [Nette Framework](https://github.com/nette/nette)


Installation
------------

* Install Forrest79/SimpleTranslator to your project using [Composer](http://getcomposer.org/):

```sh
$ composer require forrest79/ntt-translator
```


Documentation
------------

Locale files for translation are in [neon format](https://ne-on.org/). You must specify `plural` section and `messages` section.

Plural section contains conditions for plurals. It operates with `n` variable. Each line is one condition. When you need some message in plural, you must provide as much messages as plural conditions and first condition is equal to first message and so on.

Example for english and czech plurals:

```yml
plural:
    - 'n == 1'
    - 'n > 1'
```

```yml
plural:
    - 'n == 1'
    - '(n > 1) && (n < 5)'
    - 'n >= 5'
```

Messages can be simple - key => translation. Or can contains variables (`%var%`) or plurals.
 
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

And you can translate messages like this:

```php
echo $translator->translate('simpleMessage');
echo $translator->translate('messageWithVariable', ['user' => 'Jakub']);
echo $translator->translate('pluralMessageForEn', 1);
echo $translator->translate('pluralMessageForEn', 5);
echo $translator->translate('pluralMessageForEnWithVariable', ['count' => 1, 'user' => 'Jakub']);
echo $translator->translate('pluralMessageForEnWithVariable', ['count' => 5, 'Jakub']);
```

Second parameter of translate function can be count for plural or variables (array). If you use variables, there are two special values. First is `count`, that is used as count for plural and second is `locale`, that can set locale to translate.
When you use second parameter for variables, you can use third for plural count.

Locale files in neon format must have name `locale.neon`. For example for `en` locale is file name `en.neon`.

Enable this extension using your neon config.

```yml
extensions:
    translator: Forrest79\SimpleTranslator\DI\TranslatorExtension
```

Default settings is (this works out of the box):

```yml
extensions:
    locale, NULL # can set manual locale
    fallbackLocale, NULL # can set locale that is used, when main locale does't have message to translate (this is logged)
    localesDir: %appDir%/locales # directory with neon files
    tempDir: %tempDir% # for cached translation files: tempDir/cache/locales
    localeUtils: null # auto detect - use Zend OpCache clean if it's detect 
    requestResolver: locale # FALSE = disable
    debugger: %debugMode%
```

Translations are cached from neon files to PHP code. Cache is rebuild when neon file is changed. If you are using Zend OpCache, then there is default after cache is build hook, that remove this file for OpCache. For other opcaches, you can write your own hook.

Translator is registered to Nette and Latte. By default, there is resolver, that set actual locale from router variable. Default is `locale` variable, but it can be changed in configuration. Or you can set this to FALSE and then you must call `setLocale('..')` manually.   

In debug mode, there is a Tracy panel that shows untranslated messages and loaded locales and also this messages are saved to log. In production mode, only saving to log is active.
