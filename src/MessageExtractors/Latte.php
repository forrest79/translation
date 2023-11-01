<?php declare(strict_types=1);

namespace Forrest79\Translation\MessageExtractors;

use Forrest79\Translation;
use Latte\Compiler;
use Latte\Engine;
use Latte\Essential\Nodes\PrintNode;

class Latte implements Translation\MessageExtractor
{
	private Engine $engine;

	private string $filterName;


	public function __construct(Engine $engine, string $filterName = 'translate')
	{
		$this->engine = $engine;
		$this->filterName = $filterName;
	}


	public function fileExtension(): string
	{
		return 'latte';
	}


	/**
	 * @return list<string>
	 */
	public function extract(\SplFileInfo $file): array
	{
		return $this->parseContent($file);
	}


	/**
	 * @return list<string>
	 */
	public function parseContent(\SplFileInfo $file): array
	{
		$messages = [];

		$source = $this->engine->getLoader()->getContent($file->getPathname());
		$ast = $this->engine->parse($source);

		// {='web.xxx'|translate}
		$printNodes = Compiler\NodeHelpers::find($ast, static fn (Compiler\Node $node) => $node instanceof PrintNode);
		foreach ($printNodes as $node) {
			assert($node instanceof PrintNode);
			if ($node->expression instanceof Compiler\Nodes\Php\Scalar\StringNode) { // only 'web.xxx'|translate, ignore $var|translate
				if ($node->modifier->hasFilter($this->filterName)) {
					$messages[] = $node->expression->value;
				}
			}
		}

		// ('web.xxx'|translate)
		$filterNodes = Compiler\NodeHelpers::find($ast, static fn (Compiler\Node $node) => $node instanceof Compiler\Nodes\Php\Expression\FilterCallNode);
		foreach ($filterNodes as $el) {
			assert($el instanceof Compiler\Nodes\Php\Expression\FilterCallNode);
			if ($el->expr instanceof Compiler\Nodes\Php\Scalar\StringNode) { // ignore $var|translate
				if ($el->filter->name->name === $this->filterName) {
					$messages[] = $el->expr->value;
				}
			}
		}

		return $messages;
	}

}
