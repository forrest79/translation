<?php

namespace Forrest79\SimpleTranslator;


class TranslatorException extends \Exception {}

class NoLocaleFileException extends TranslatorException {};

class PluralSectionMissingException extends TranslatorException {};

class ParsingErrorException extends TranslatorException {};

class NotPluralMessageException extends TranslatorException {};

class BadCountForPluralMessageException extends TranslatorException {};

class NoCountForPluralMessageException extends TranslatorException {};

class NoLocaleSelectedExceptions extends TranslatorException {};

class BadLocaleNameExceptions extends TranslatorException {};
