<?php declare(strict_types=1);

namespace Forrest79\Translation\Tests;

use Forrest79\Translation\CatalogueLoader;
use Forrest79\Translation\CatalogueUtils;
use Forrest79\Translation\Logger;
use Forrest79\Translation\Nette;
use Forrest79\Translation\Tests;
use Nette\Application\Application;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Http;
use Nette\Routing;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @testCase
 */
final class NetteTest extends Tests\TestCase
{
	private const DEFAULT_LOCALE_PARAMETER = 'locale';

	private string $tempDir;


	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDir = self::prepareCurrentTestTempDir();

		if (PHP_MINOR_VERSION === 0) {
			Environment::skip('This test needs Nette\Http with PHP 8.1 support.');
		}
	}


	public function testCreateByRequest(): void
	{
		$application = self::createApplication(new Request(
			'TestRequest',
			params: [self::DEFAULT_LOCALE_PARAMETER => 'en'],
		));

		$translatorFactory = $this->createTranslatorFactory();
		$translator = $translatorFactory->createByRequest($application);

		Assert::same('en', $translator->getLocale());
		Assert::same(['cs'], $translator->getFallbackLocales());
	}


	public function testCreateByRequestWithDefault(): void
	{
		$translatorFactory = $this->createTranslatorFactory();

		$application1 = self::createApplication(new Request(
			'TestRequest',
			params: [self::DEFAULT_LOCALE_PARAMETER => 'en'],
		));

		$translator1 = $translatorFactory->createByRequest($application1, 'fr');

		Assert::same('en', $translator1->getLocale());

		$application2 = self::createApplication(new Request(
			'TestRequest',
		));

		$translator2 = $translatorFactory->createByRequest($application2, 'fr');

		Assert::same('fr', $translator2->getLocale());
	}


	public function testNoLocaleParameter(): void
	{
		$application = self::createApplication(new Request(
			'TestRequest',
			params: [self::DEFAULT_LOCALE_PARAMETER => NULL],
		));

		Assert::exception(function () use ($application): void {
			$this->createTranslatorFactory()->createByRequest($application);
		}, Nette\Exceptions\NoLocaleParameterException::class);
	}


	private static function createApplication(Request $request): Application
	{
		$presenterFactory = new class implements IPresenterFactory {

			public function getPresenterClass(string &$name): string
			{
				throw new \RuntimeException('Not implemented');
			}


			public function createPresenter(string $name): IPresenter
			{
				throw new \RuntimeException('Not implemented');
			}

		};

		$router = new class implements Routing\Router {

			/**
			 * @return array<mixed>|NULL
			 */
			public function match(Http\IRequest $httpRequest): array|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @param array<mixed> $params
			 */
			public function constructUrl(array $params, Http\UrlScript $refUrl): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}

		};

		$httpRequest = new class implements Http\IRequest {

			public function getUrl(): Http\UrlScript
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getQuery(string|NULL $key = NULL): mixed
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getPost(string|NULL $key = NULL): mixed
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @return array<mixed>|Http\FileUpload|NULL
			 */
			public function getFile(string $key): array|Http\FileUpload|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @return array<mixed>
			 */
			public function getFiles(): array
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getCookie(string $key): mixed
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @return array<mixed>
			 */
			public function getCookies(): array
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getMethod(): string
			{
				throw new \RuntimeException('Not implemented');
			}


			public function isMethod(string $method): bool
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getHeader(string $header): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @return array<mixed>
			 */
			public function getHeaders(): array
			{
				throw new \RuntimeException('Not implemented');
			}


			public function isSecured(): bool
			{
				throw new \RuntimeException('Not implemented');
			}


			public function isAjax(): bool
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getRemoteAddress(): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getRemoteHost(): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getRawBody(): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}

		};

		$httpResponse = new class implements Http\IResponse {

			public function setCode(int $code, string|NULL $reason = NULL)
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getCode(): int
			{
				throw new \RuntimeException('Not implemented');
			}


			public function setHeader(string $name, string $value): static
			{
				throw new \RuntimeException('Not implemented');
			}


			public function addHeader(string $name, string $value): static
			{
				throw new \RuntimeException('Not implemented');
			}


			public function setContentType(string $type, string|NULL $charset = NULL): static
			{
				throw new \RuntimeException('Not implemented');
			}


			public function redirect(string $url, int $code = self::S302_Found): void
			{
				throw new \RuntimeException('Not implemented');
			}


			public function setExpiration(string|NULL $expire): static
			{
				throw new \RuntimeException('Not implemented');
			}


			public function isSent(): bool
			{
				throw new \RuntimeException('Not implemented');
			}


			public function getHeader(string $header): string|NULL
			{
				throw new \RuntimeException('Not implemented');
			}


			/**
			 * @return array<mixed>
			 */
			public function getHeaders(): array
			{
				throw new \RuntimeException('Not implemented');
			}


			public function setCookie(
				string $name,
				string $value,
				int|NULL $expire,
				string|NULL $path = NULL,
				string|NULL $domain = NULL,
				bool|NULL $secure = NULL,
				bool|NULL $httpOnly = NULL,
			): static
			{
				throw new \RuntimeException('Not implemented');
			}


			public function deleteCookie(
				string $name,
				string|NULL $path = NULL,
				string|NULL $domain = NULL,
				bool|NULL $secure = NULL,
			): static
			{
				throw new \RuntimeException('Not implemented');
			}

		};

		$application = new Application($presenterFactory, $router, $httpRequest, $httpResponse);

		(function () use ($request): void {
			$this->requests = [$request];
		})->call($application);

		return $application;
	}


	private function createTranslatorFactory(): Nette\TranslatorFactory
	{
		return new Nette\TranslatorFactory(
			TRUE,
			$this->tempDir,
			self::createCatalogueLoader(),
			self::DEFAULT_LOCALE_PARAMETER,
			['en' => ['cs']],
			new CatalogueUtils\Opcache(),
			self::createLogger(),
		);
	}


	private static function createCatalogueLoader(): CatalogueLoader
	{
		return new class() implements CatalogueLoader {

			public function isLocaleUpdated(string $locale, string $cacheFile): bool
			{
				return FALSE;
			}


			/**
			 * @return array<string, string|array<string, string|list<string>>|NULL>
			 */
			public function loadData(string $locale): array
			{
				return ['messages' => []];
			}


			public function source(string $locale): string
			{
				return 'test_' . $locale;
			}

		};
	}


	private static function createLogger(): Logger
	{
		return new class implements Logger {

			public function addUntranslated(string $locale, string $message): void
			{
				// nothing important in this test
			}


			public function addError(string $locale, string $error): void
			{
				// nothing important in this test
			}


			public function addLocaleFile(string $locale, string $source): void
			{
				// nothing important in this test
			}

		};
	}

}

(new NetteTest())->run();
