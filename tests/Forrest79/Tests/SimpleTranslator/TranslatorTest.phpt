<?php

namespace Forrest79\Tests\SimpleTranslator;

use Forrest79;
use Forrest79\SimpleTranslator;
use Tester;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../../../bootstrap.php';


class TranslatorTest extends Tester\TestCase
{
	/** @var SimpleTranslator\Translator */
	private $translator;


	protected function setUp()
	{
		parent::setUp();

		$this->translator = new SimpleTranslator\Translator(TRUE, TEMP_DIR, TEMP_DIR, Tracy\Debugger::getLogger());
	}


	/**
	 * @throws Forrest79\SimpleTranslator\NoLocaleSelectedExceptions
	 */
	public function testNoLocaleSelected()
	{
		$this->translator->translate('message');
	}


	/**
	 * @throws Forrest79\SimpleTranslator\BadLocaleNameExceptions
	 */
	public function testBadLocaleName()
	{
		$this->translator
			->setLocale('bad-locale-name')
			->translate('message');
	}


	public function testSimpleTranslate()
	{
		$message = 'Test message.';
		$this->translator->setLocale($this->createLocale(['message' => $message]));
		Assert::same($message, $this->translator->translate('message'));
	}


	public function testFallbackTranslate()
	{
		$message = 'Test message.';

		$this->translator->setFallbackLocale($this->createLocale(['message' => $message]));
		$this->translator->setLocale($this->createLocale(['other.message' => 'what?']));

		Assert::same($message, $this->translator->translate('message'));
	}


	/**
	 * @throws Forrest79\SimpleTranslator\BadCountForPluralMessageException
	 */
	public function testBadPluralTranslate()
	{
		$this->translator->setLocale($this->createLocale(['message' => ['One item.']]));
		$this->translator->translate('message', 10);
	}


	/**
	 * @throws Forrest79\SimpleTranslator\NotPluralMessageException
	 */
	public function testNotPluralTranslate()
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


	public function testPluralCsTranslate()
	{
		$message1 = 'Jedno auto.';
		$message2 = '3 auta.';
		$message3 = '10 aut.';
		$this->translator->setLocale($this->createLocale(['message' => [$message1, $message2, $message3]], ['n == 1', '(n > 1) && (n < 5)', 'n >= 5']));
		Assert::same($message1, $this->translator->translate('message', 1));
		Assert::same($message2, $this->translator->translate('message', 3));
		Assert::same($message3, $this->translator->translate('message', 10));
	}


	public function testVariablesTranslate()
	{
		$this->translator->setLocale($this->createLocale(['message' => 'Welcome %user%.']));
		Assert::same('Welcome Jakub.', $this->translator->translate('message', ['user' => 'Jakub']));
	}


	public function testVariablesPluralTranslate()
	{
		$this->translator->setLocale($this->createLocale(['message' => ['I have one %type%.', 'I have more %type%s.']]));
		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car', 'count' => 1]));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car', 'count' => 10]));

		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car'], 1));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car'], 10));
	}


	private function createLocale(array $messages, array $plural = [])
	{
		return createLocale($messages, $plural);
	}

}

(new TranslatorTest)->run();
