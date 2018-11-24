<?php declare(strict_types=1);

use Forrest79\SimpleTranslator;
use Nette\Application;
use Nette\Http;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$translator = new SimpleTranslator\Translator(TRUE, TEMP_DIR, Tracy\Debugger::getLogger());
$translator->setDataLoader(new SimpleTranslator\DataLoaders\Neon(TEMP_DIR));

$resolveBy = 'locale';
$testMessage = 'Test message';

$httpRequest = new Http\Request(new Http\UrlScript('https://www.test.com/?' . $resolveBy . '=' . createLocale(['test' => $testMessage])));
$httpResponse = new Http\Response;

$presenterFactory = (new Application\PresenterFactory)->setMapping(['*' => 'Forrest79\SimpleTranslator\Tests\*Presenter']);
$router = new Application\Routers\SimpleRouter('Homepage:default');

$app = new Application\Application($presenterFactory, $router, $httpRequest, $httpResponse);

$requestResolver = new SimpleTranslator\RequestResolver($resolveBy, $translator);
$app->onRequest[] = [$requestResolver, 'onRequest'];

$app->run();

Assert::exception(function () use ($translator) {
	$translator->translate('test', ['locale' => 'bad-locale']);
}, SimpleTranslator\Exceptions\NoLocaleFileException::class);

Assert::same($testMessage, $translator->translate('test'));
