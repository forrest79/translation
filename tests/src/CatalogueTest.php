<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\Catalogue;
use Forrest79\Translation\Exceptions;
use Tester\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class CatalogueTest extends TestCase
{
	private const MESSAGES = [
		'simple_identifier' => 'simple_translation',
		'plural_identifier' => ['plural_translation1', 'plural_translation2'],
		'plural_error_identifier' => ['plural_error_translation1'],
	];

	private Catalogue $catalogue;


	protected function setUp(): void
	{
		parent::setUp();

		$this->catalogue = new class('xx', self::MESSAGES) extends Catalogue {

			protected function getPluralIndex(int $count): int
			{
				return ($count === 1) ? 0 : 1;
			}

		};
	}


	public function testSimpleMessage(): void
	{
		Assert::same('simple_translation', $this->catalogue->getTranslation('simple_identifier'));
	}


	public function testPluralMessage(): void
	{
		Assert::same('plural_translation1', $this->catalogue->getTranslation('plural_identifier', 1));
		Assert::same('plural_translation2', $this->catalogue->getTranslation('plural_identifier', 10));
	}


	public function testPluralMessageMissingPlural(): void
	{
		Assert::same('plural_error_translation1', $this->catalogue->getTranslation('plural_error_identifier', 1));

		Assert::exception(function (): void {
			$this->catalogue->getTranslation('plural_error_identifier', 10);
		}, Exceptions\BadCountForPluralMessageException::class);
	}


	public function testPluralMessageWithoutCount(): void
	{
		Assert::exception(function (): void {
			$this->catalogue->getTranslation('plural_error_identifier');
		}, Exceptions\NoCountForPluralMessageException::class);
	}


	public function testSimpleMessageWithCount(): void
	{
		Assert::exception(function (): void {
			$this->catalogue->getTranslation('simple_identifier', 1);
		}, Exceptions\NotPluralMessageException::class);
	}


	public function testNonExistingMessage(): void
	{
		Assert::null($this->catalogue->getTranslation('non_existing_identifier'));
	}

}

(new CatalogueTest())->run();
