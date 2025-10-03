<?php declare(strict_types=1);

namespace Forrest79\Translation\MessageExtractors;

use ArrayIterator;
use Forrest79\Translation;

class Php implements Translation\MessageExtractor
{
	private const OPEN_TOKENS = ['[' => ']', '(' => ')'];
	private const CLOSE_TOKENS = [']' => '[', ')' => '('];

	/** @var ArrayIterator<int, array{int, string, int}|string> */
	private ArrayIterator $tokens;


	public function fileExtension(): string
	{
		return 'php';
	}


	/**
	 * @return list<string>
	 */
	public function extract(\SplFileInfo $file): array
	{
		$content = @file_get_contents($file->getPathname()); // intentionally @ - file may not exist
		if ($content === false) {
			throw new \RuntimeException(sprintf('Can\'t read file "%s".', $file->getPathname()));
		}

		$messages = [];
		foreach ($this->getCalledTranslateMethodParameters($content) as $parameters) {
			$parameters = $parameters[0];
			if ($parameters[0] === '$') { // ignore identifiers defined with a variable
				continue;
			}
			$messages[] = trim($parameters, '\'"');
		}
		return $messages;
	}


	/**
	 * @return list<string|list<string>>
	 */
	private function getCalledTranslateMethodParameters(string $content): array
	{
		$methods = [];

		/** @var list<array{int, string, int}|string> $tokens */
		$tokens = token_get_all($content);

		$this->tokens = new ArrayIterator($tokens);
		foreach ($this->tokens as $i => $meta) {
			if (is_array($meta) && ($meta[0] === T_OBJECT_OPERATOR)) {
				$this->tokens->next();
				$meta = $this->tokens->current();
				$hasOpenBracket = $this->tokens[$i + 2] === '(';
				if (is_array($meta) && ($meta[1] === 'translate') && $hasOpenBracket) {
					$methods[] = $this->loadMethodParameters();
				}
			}
		}

		return $methods;
	}


	/**
	 * @return list<string>
	 */
	private function loadMethodParameters(): array
	{
		$parameters = [];
		while ($this->isValidToken()) {
			$meta = $this->tokens->current();
			if (in_array($meta, ['(', ','], true)) {
				$parameters[] = trim($this->loadMethodParameter());
			} else if ($meta === ')') {
				break;
			}
		}
		return $parameters;
	}


	private function loadMethodParameter(): string
	{
		$parameter = '';
		$stack = 0;
		while ($this->isValidToken()) {
			$meta = $this->tokens->current();
			if (($meta === ',') && ($stack === 0)) {
				$this->tokens->seek($this->tokens->key() - 1);
				break;
			}

			if (is_string($meta)) {
				if (isset(self::OPEN_TOKENS[$meta])) {
					++$stack;
				} else if (isset(self::CLOSE_TOKENS[$meta])) {
					--$stack;
					if ($stack < 0) {
						$this->tokens->seek($this->tokens->key() - 1);
						break;
					}
				}
				$parameter .= $meta;
			} else {
				$parameter .= $meta[1];
			}
		}

		return $parameter;
	}


	private function isValidToken(): bool
	{
		$this->tokens->next();
		return $this->tokens->valid();
	}

}
