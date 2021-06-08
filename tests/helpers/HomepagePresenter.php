<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Tests;

use Nette\Application;

final class HomepagePresenter implements Application\IPresenter
{

	public function run(Application\Request $request): Application\IResponse
	{
		return new Application\Responses\TextResponse('');
	}

}
