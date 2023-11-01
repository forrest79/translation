<?php declare(strict_types=1);

namespace Forrest79\Translation;

class PluralsHelper
{

	/**
	 * Returns the plural position to use for the given locale and number.
	 *
	 * The plural rules are derived from code of the Zend Framework (2010-09-25),
	 * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
	 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
	 */
	public static function getPluralizationRule(string $locale): string|NULL
	{
		if (($locale !== 'pt_BR') && ($locale !== 'en_US_POSIX') && (strlen($locale) > 3)) {
			$parts = explode('_', $locale, 2);
			$code = $parts[0];
		} else {
			$code = $locale;
		}

		return match ($code) {
			'af',
			'bn',
			'bg',
			'ca',
			'da',
			'de',
			'el',
			'en',
			'en_US_POSIX',
			'eo',
			'es',
			'et',
			'eu',
			'fa',
			'fi',
			'fo',
			'fur',
			'fy',
			'gl',
			'gu',
			'ha',
			'he',
			'hu',
			'is',
			'it',
			'ku',
			'lb',
			'ml',
			'mn',
			'mr',
			'nah',
			'nb',
			'ne',
			'nl',
			'nn',
			'no',
			'oc',
			'om',
			'or',
			'pa',
			'pap',
			'ps',
			'pt',
			'so',
			'sq',
			'sv',
			'sw',
			'ta',
			'te',
			'tk',
			'ur',
			'zu' => '($count === 1) ? 0 : 1',
			'am',
			'bh',
			'fil',
			'fr',
			'gun',
			'hi',
			'hy',
			'ln',
			'mg',
			'nso',
			'pt_BR',
			'ti',
			'wa' => '($count < 2) ? 0 : 1',
			'be',
			'bs',
			'hr',
			'ru',
			'sh',
			'sr',
			'uk' => '((($count % 10) === 1) && (($count % 100) !== 11)) ? 0 : ((($count % 10 >= 2) && (($count % 10) <= 4) && ((($count % 100) < 10) || (($count % 100) >= 20))) ? 1 : 2)',
			'cs',
			'sk' => '($count === 1) ? 0 : ((($count >= 2) && ($count <= 4)) ? 1 : 2)',
			'ga' => '($count === 1) ? 0 : (($count === 2) ? 1 : 2)',
			'lt' => '((($count % 10) === 1) && (($count % 100) !== 11)) ? 0 : (((($count % 10) >= 2) && ((($count % 100) < 10) || (($count % 100) >= 20))) ? 1 : 2)',
			'sl' => '(($count % 100) === 1) ? 0 : ((($count % 100) === 2) ? 1 : (((($count % 100) === 3) || (($count % 100) === 4)) ? 2 : 3))',
			'mk' => '(($count % 10) === 1) ? 0 : 1',
			'mt' => '($count === 1) ? 0 : ((($count === 0) || ((($count % 100) > 1) && (($count % 100) < 11))) ? 1 : (((($count % 100) > 10) && (($count % 100) < 20)) ? 2 : 3))',
			'lv' => '($count === 0) ? 0 : (((($count % 10) === 1) && (($count % 100) !== 11)) ? 1 : 2)',
			'pl' => '($count === 1) ? 0 : (((($count % 10) >= 2) && (($count % 10) <= 4) && ((($count % 100) < 12) || (($count % 100) > 14))) ? 1 : 2)',
			'cy' => '($count === 1) ? 0 : (($count === 2) ? 1 : ((($count === 8) || ($count === 11)) ? 2 : 3))',
			'ro' => '($count === 1) ? 0 : ((($count === 0) || ((($count % 100) > 0) && (($count % 100) < 20))) ? 1 : 2)',
			'ar' => '($count === 0) ? 0 : (($count === 1) ? 1 : (($count === 2) ? 2 : (((($count % 100) >= 3) && (($count % 100) <= 10)) ? 3 : (((($count % 100) >= 11) && (($count % 100) <= 99)) ? 4 : 5))))',
			default => NULL,
		};
	}

}
