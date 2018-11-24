<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Tests;

use Nette\Application;

class HomepagePresenter implements Application\IPresenter
{
	/** @var Application\Request */
	public $request;


	/**
	 * @param Application\Request $request
	 * @return Application\IResponse
	 */
	public function run(Application\Request $request)
	{
		$this->request = $request;
		return new Application\Responses\TextResponse('');
	}

}
