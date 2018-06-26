<?php declare(strict_types=1);

namespace Forrest79\Tests\SimpleTranslator;

use Forrest79;
use Forrest79\SimpleTranslator;
use Tester;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
class TranslatorTest extends Tester\TestCase
{
	/** @var SimpleTranslator\Translator */
	private $translator;


	protected function setUp(): void
	{
		parent::setUp();

		$this->translator = new SimpleTranslator\Translator(TRUE, TEMP_DIR, Tracy\Debugger::getLogger());
		$this->translator->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR));
	}


	/**
	 * @throws Forrest79\SimpleTranslator\Exceptions\NoLocaleSelectedExceptions
	 */
	public function testNoLocaleSelected(): void
	{
		$this->translator->translate('message');
	}


	/**
	 * @throws Forrest79\SimpleTranslator\Exceptions\BadLocaleNameExceptions
	 */
	public function testBadLocaleName(): void
	{
		$this->translator
			->setLocale('bad*locale*name')
			->translate('message');
	}


	public function testSimpleTranslate(): void
	{
		$message = 'Test message.';
		$this->translator->setLocale($this->createLocale(['message' => $message]));
		Assert::same($message, $this->translator->translate('message'));
	}


	public function testFallbackTranslate(): void
	{
		$message = 'Test message.';

		$this->translator->setFallbackLocale($this->createLocale(['message' => $message]));
		$this->translator->setLocale($this->createLocale(['other.message' => 'what?']));

		Assert::same($message, $this->translator->translate('message'));
	}


	/**
	 * @throws Forrest79\SimpleTranslator\Exceptions\BadCountForPluralMessageException
	 */
	public function testBadPluralTranslate(): void
	{
		$this->translator->setLocale($this->createLocale(['message' => ['One item.']]));
		$this->translator->translate('message', 10);
	}


	/**
	 * @throws Forrest79\SimpleTranslator\Exceptions\NotPluralMessageException
	 */
	public function testNotPluralTranslate(): void
	{
		$this->translator->setLocale($this->createLocale(['message' => 'One item.']));
		$this->translator->translate('message', 10);
	}


	public function testPluralTranslate()
	{
		$message1 = 'One item.';
		$message2 = 'More items.';
		$this->translator->setLocale($this->createLocale(['message' => [$message1, $message2]]));
		Assert::same($message1, $this->translator->translate('message', 1));
		Assert::same($message2, $this->translator->translate('message', 10));
	}


	public function testPluralCsTranslate(): void
	{
		$message1 = 'Jedno auto.';
		$message2 = '3 auta.';
		$message3 = '10 aut.';
		$this->translator->setLocale($this->createLocale(['message' => [$message1, $message2, $message3]], ['n == 1', '(n > 1) && (n < 5)', 'n >= 5']));
		Assert::same($message1, $this->translator->translate('message', 1));
		Assert::same($message2, $this->translator->translate('message', 3));
		Assert::same($message3, $this->translator->translate('message', 10));
	}


	public function testVariablesTranslate(): void
	{
		$this->translator->setLocale($this->createLocale(['message' => 'Welcome %user%.']));
		Assert::same('Welcome Jakub.', $this->translator->translate('message', ['user' => 'Jakub']));
	}


	public function testVariablesPluralTranslate(): void
	{
		$this->translator->setLocale($this->createLocale(['message' => ['I have one %type%.', 'I have more %type%s.']]));
		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car', 'count' => 1]));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car', 'count' => 10]));

		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car'], 1));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car'], 10));
	}


	private function createLocale(array $messages, array $plural = []): string
	{
		return createLocale($messages, $plural);
	}

}

(new TranslatorTest())->run();
