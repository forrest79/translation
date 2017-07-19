<?php

namespace Forrest79\Tests\NttTranslator;

use Forrest79\NttTranslator;
use Nette\Application;
use Nette\Http;
use Tester\Assert;
use Tracy;

require_once __DIR__ . '/../../../bootstrap.php';


$translator = new NttTranslator\Translator(TRUE, TEMP_DIR, TEMP_DIR, Tracy\Debugger::getLogger());

class HomepagePresenter implements Application\IPresenter
{
	public $request;


	public function run(Application\Request $request)
	{
		$this->request = $request;
		return new Application\Responses\TextResponse('');
	}
}

$resolveBy = 'locale';
$testMessage = 'Test message';

$httpRequest = new Http\Request(new Http\UrlScript('https://www.test.com/?' . $resolveBy . '=' . createLocale(['test' => $testMessage])));
$httpResponse = new Http\Response;

$presenterFactory = (new Application\PresenterFactory)->setMapping(['*' => 'Forrest79\Tests\NttTranslator\*Presenter']);
$router = new Application\Routers\SimpleRouter('Homepage:default');

$app = new Application\Application($presenterFactory, $router, $httpRequest, $httpResponse);

$requestResolver = new NttTranslator\RequestResolver($resolveBy, $translator);
$app->onRequest[] = [$requestResolver, 'onRequest'];

$app->run();

Assert::exception(function () use ($translator) {
	$translator->translate('test', ['locale' => 'bad-locale']);
}, NttTranslator\NoLocaleFileException::class);

Assert::same($testMessage, $translator->translate('test'));
