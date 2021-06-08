<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Tests;

use Forrest79\SimpleTranslator;
use Tester;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class TranslatorTest extends TestCase
{
	private SimpleTranslator\Translator $translator;


	protected function setUp(): void
	{
		parent::setUp();

		$this->translator = new SimpleTranslator\Translator(TRUE, TEMP_DIR, Tracy\Debugger::getLogger());
		$this->translator->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR));
	}


	public function testNoLocaleSelected(): void
	{
		Tester\Assert::exception(function (): void {
			$this->translator->translate('message');
		}, SimpleTranslator\Exceptions\NoLocaleSelectedException::class);
	}


	public function testBadLocaleName(): void
	{
		Tester\Assert::exception(function (): void {
			$this->translator
				->setLocale('bad*locale*name')
				->translate('message');
		}, SimpleTranslator\Exceptions\BadLocaleNameException::class);
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


	public function testBadPluralTranslate(): void
	{
		Tester\Assert::exception(function (): void {
			$this->translator->setLocale($this->createLocale(['message' => ['One item.']]));
			$this->translator->translate('message', 10);
		}, SimpleTranslator\Exceptions\BadCountForPluralMessageException::class);
	}


	public function testNotPluralTranslate(): void
	{
		Tester\Assert::exception(function (): void {
			$this->translator->setLocale($this->createLocale(['message' => 'One item.']));
			$this->translator->translate('message', 10);
		}, SimpleTranslator\Exceptions\NotPluralMessageException::class);
	}


	public function testPluralNoCountTranslate(): void
	{
		Tester\Assert::exception(function (): void {
			$this->translator->setLocale($this->createLocale(['message' => ['One item.']]));
			$this->translator->translate('message');
		}, SimpleTranslator\Exceptions\NoCountForPluralMessageException::class);
	}


	public function testPluralTranslate(): void
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
		$this->translator->setLocale(
			$this->createLocale(
				['message' => [$message1, $message2, $message3]],
				['n == 1', '(n > 1) && (n < 5)', 'n >= 5'],
			),
		);
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
		$this->translator->setLocale(
			$this->createLocale(['message' => ['I have one %type%.', 'I have more %type%s.']]),
		);
		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car', 'count' => 1]));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car', 'count' => 10]));

		Assert::same('I have one car.', $this->translator->translate('message', ['type' => 'car'], 1));
		Assert::same('I have more cars.', $this->translator->translate('message', ['type' => 'car'], 10));
	}


	public function testCreateImmutableTranslator(): void
	{
		$locale = 'cs';
		$immutable = $this->translator->createImmutableTranslator($locale);
		Tester\Assert::type(SimpleTranslator\TranslatorImmutable::class, $immutable);
		Tester\Assert::same($locale, $immutable->getLocale());
	}


	public function testImmutableTranslatorChangeLocale(): void
	{
		$immutable = $this->translator->createImmutableTranslator('cs');
		Tester\Assert::exception(static function () use ($immutable): void {
			$immutable->translate('test', [$immutable::PARAM_LOCALE => 'en']);
		}, SimpleTranslator\Exceptions\CantChangeLocaleForImmutableTranslatorException::class);
	}


	public function testImmutableTranslatorSimpleTranslate(): void
	{
		$message = 'Test message.';
		$locale = $this->createLocale(['message' => $message]);
		$immutable = $this->translator->setLocale($locale)->createImmutableTranslator($locale);
		Assert::same($message, $immutable->translate('message'));
	}


	public function testImmutableTranslatorPluralTranslate(): void
	{
		$message1 = 'One item.';
		$message2 = 'More items.';
		$locale = $this->createLocale(['message' => [$message1, $message2]]);
		$immutable = $this->translator->setLocale($locale)->createImmutableTranslator($locale);

		Assert::same($message1, $immutable->translate('message', 1));
		Assert::same($message2, $immutable->translate('message', 10));

		Assert::same($message1, $immutable->translate('message', [], 1));
		Assert::same($message2, $immutable->translate('message', [], 10));

		Assert::same($message1, $immutable->translate('message', [$immutable::PARAM_COUNT => 1]));
		Assert::same($message2, $immutable->translate('message', [$immutable::PARAM_COUNT => 10]));
	}


	public function testNoDataLoader(): void
	{
		$translator = new SimpleTranslator\Translator(TRUE, TEMP_DIR, Tracy\Debugger::getLogger());
		$translator->setLocale('cs');

		Tester\Assert::exception(static function () use ($translator): void {
			$translator->translate('test');
		}, SimpleTranslator\Exceptions\NoDataLoaderException::class);
	}


	public function testClearCache(): void
	{
		$message = 'test';
		$locale = $this->createLocale(['message' => $message]);
		$this->translator->setLocale($locale);

		$cacheFile = TEMP_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . $locale . '.php';

		Tester\Assert::same($message, $this->translator->translate('message'));

		Tester\Assert::true(file_exists($cacheFile));

		$this->translator->clearCache($locale);

		Tester\Assert::false(file_exists($cacheFile));
	}


	public function testCorruptedNeon(): void
	{
		$this->translator->setLocale($this->createLocale([], [], TRUE));

		Tester\Assert::exception(function (): void {
			$this->translator->translate('message');
		}, SimpleTranslator\Exceptions\ParsingErrorException::class);
	}


	public function testMissingSectionInNeon(): void
	{
		$this->translator->setLocale($this->createLocale([], [], FALSE, TRUE));

		Tester\Assert::exception(function (): void {
			$this->translator->translate('message');
		}, SimpleTranslator\Exceptions\SomeSectionMissingException::class);
	}


	public function testProcessErrorInProductionMode(): void
	{
		$translator = new SimpleTranslator\Translator(FALSE, TEMP_DIR, Tracy\Debugger::getLogger());
		$translator->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR));
		$translator->setLocale($this->createLocale(['message' => 'test']));
		Tester\Assert::same('message', $translator->translate('message', 1));
	}


	/**
	 * @param array<string, string|array<string>> $messages
	 * @param array<string> $plural
	 */
	private function createLocale(
		array $messages,
		array $plural = [],
		bool $corruptNeon = FALSE,
		bool $missingSections = FALSE
	): string
	{
		$updateNeon = NULL;
		if ($corruptNeon === TRUE) {
			$updateNeon = static function ($neon): string {
				return $neon . PHP_EOL . 'error';
			};
		} else if ($missingSections) {
			$updateNeon = static function (): string {
				return 'messages:';
			};
		}

		return createLocale($messages, $plural, NULL, $updateNeon);
	}

}

(new TranslatorTest())->run();
