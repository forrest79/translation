<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class PluralsHelperTest extends TestCase
{

	public function testPluralsEn(): void
	{
		foreach (['en', 'en_US'] as $locale) {
			$pluralFunction = self::createPluralFunction(Translation\PluralsHelper::getPluralizationRule($locale));
			Assert::same(1, $pluralFunction(0));
			Assert::same(0, $pluralFunction(1));
			Assert::same(1, $pluralFunction(2));
			Assert::same(1, $pluralFunction(3));
			Assert::same(1, $pluralFunction(4));
			Assert::same(1, $pluralFunction(5));
			Assert::same(1, $pluralFunction(1000));
		}
	}


	public function testPluralsCs(): void
	{
		foreach (['cs', 'cs_CZ'] as $locale) {
			$pluralFunction = self::createPluralFunction(Translation\PluralsHelper::getPluralizationRule($locale));
			Assert::same(2, $pluralFunction(0));
			Assert::same(0, $pluralFunction(1));
			Assert::same(1, $pluralFunction(2));
			Assert::same(1, $pluralFunction(3));
			Assert::same(1, $pluralFunction(4));
			Assert::same(2, $pluralFunction(5));
			Assert::same(2, $pluralFunction(1000));
		}
	}


	public function testNonExistingRule(): void
	{
		Assert::null(Translation\PluralsHelper::getPluralizationRule('xyz'));
	}


	private static function createPluralFunction(string|null $definition): callable
	{
		// make IDE and PHPStan happy - is overwritten in eval()
		$callable = static function (): int {
			return -1;
		};

		eval(sprintf('$callable = function (int $count): int {return %s;};', $definition ?? '-1'));

		return $callable;
	}

}

(new PluralsHelperTest())->run();
