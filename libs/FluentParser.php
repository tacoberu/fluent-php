<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use Taco\BNF\Parser;
use Taco\BNF\Token;
use Taco\BNF\Combinators\Whitechars;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Match;
use Taco\BNF\Combinators\OneOf;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\Indent;
use Taco\BNF\Combinators\Numeric;
use Taco\BNF\Combinators\Text;
use Taco\FluentIntl\BNF\FluentSelectExpression;
use LogicException;


class FluentParser
{

	private static $skipIndent = [['{', '}']];

	private static $symbolPattern = '[a-z\-\$][a-zA-Z0-9\-]*';

	private static $valuePattern = '[a-z0-9][a-zA-Z0-9\-]*';

	private $schema;

	private $parser;

	function __construct()
	{
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~[\r\n]+~',], False);
		$comment = new Pattern('Comment', ['~^#.*$~m'], False);
		$identifier = new Pattern('Identifier', ['~' . self::$symbolPattern . '~']);
		$textElement = new Pattern('TextElement', ['~[^\{\}]+~s']);
		$pattern = new Sequence('VariableReference', [
			new Pattern(Null, ['~\{[ \t]*~'], False),
			new OneOf('Pattern', [
				new Pattern('VariableReference', ['~' . self::$symbolPattern . '~']),
				new Text('StringLiteral'),
				new Sequence('FunctionReference', [
					new Pattern('Id', ['~[A-Z]+~']),
					new Match(Null, ['('], False),
					new Variants('Arguments', [
						new OneOf(Null, [
							new Sequence('NamedArgument', [
								new Pattern('Name', ['~[a-z][a-zA-Z]*~']),
								new Pattern(Null, ['~\s*\:\s*~'], False),
								new OneOf('Value', [
									new Numeric('NumericLiteral'),
									new Text('StringLiteral'),
								]),
							]),
							$identifier,
						]),
						new Pattern(Null, ['~\s*,\s*~'], False),
					]),
					new Match(Null, [')'], False),
				]),
			]),
			new Pattern(Null, ['~[ \t]*\}~'], False),
		]);
		$pattern = new Variants('Pattern', [
			$nl,
			$pattern,
			new FluentSelectExpression('SelectExpression'),
			$textElement,
		]);

		$message = new Sequence('Message', [
			$identifier,
			$sep,
			new Match('assign', ['='], False),
			(new Pattern(Null, ['~[ \t]+~'], False))->setOptional(),
			new Indent(Null, $pattern, self::$skipIndent),
		]);

		$this->schema = new Variants('Resource', [
			$message,
			$comment,
			$nl,
		]);

		$this->parser = new Parser($this->schema);
	}



	function parse($src)
	{
		return self::castAny($this->parser->parse($src));
	}



	private static function castAny(Token $node)
	{
		switch ($node->getName()) {
			case 'Resource':
				$res = [];
				foreach ($node->content as $x) {
					$res[] = self::castAny($x);
				}
				return $res;
			case 'Message':
				$id = self::castAny($node->content[0])->name;
				$value = self::castAny($node->content[1]);
				$args = [];
				return (object) [
					"type" => "Message",
					"id" => $id,
					"value" => new Expr($value->expression, $value->args),
				];
			case 'Identifier':
				return (object) [
					"type" => "Identifier",
					"name" => trim($node->content),
				];
			case 'Placeable':
				$res = [];
				foreach ($node->content as $x) {
					$res[] = self::castAny($x);
				}
				return (object) [
					"type" => "Placeable",
					"elements" => $res,
				];
			case 'Pattern':
				$elements = [];
				foreach ($node->content as $x) {
					$elements[] = self::castAny($x);
				}
				list($expression, $args) = self::flatElements($elements);
				return (object) [
					"type" => "Pattern",
					"expression" => $expression,
					"args" => $args,
				];
			case 'Comment':
				return (object) [
					"type" => "Comment",
					"content" => $node->content,
				];
			case 'Name':
			case 'TextElement':
				return $node->content;
			case 'StringLiteral':
				return substr($node->content, 1, -1);
			case 'NumericLiteral':
				if (strpos($node->content, '.') !== False) {
					return (float) $node->content;
				}
				else {
					return (int) $node->content;
				}
			case 'VariableReference':
				return (object) [
					'type' => 'VariableReference',
					'id' => trim($node->content, " \t{}"),
				];
			case 'SelectExpression':
				$vars = [];
				foreach ($node->content[1]->content as $opt) {
					$vars[] = self::castAny($opt);
				}
				return (object) [
					"type" => "SelectExpression",
					"selector" => trim($node->content[0], " \t{}"),
					"variants" => $vars,
				];
			case 'SelectOption':
				$expr = self::castAny($node->content[2]);
				$args = [];
				$el = (object) [
					'id' => trim($node->content[1], '[]'),
					'expression' => $expr->expression,
					'args' => $expr->args,
				];
				if (trim($node->content[0]) === '*') {
					$el->default = True;
				}
				return $el;

			case 'FunctionReference':
				$args = [];
				foreach ($node->content[1]->content as $arg) {
					$args[] = self::castAny($arg);
				}
				$fn = $node->content[0]->content;
				return (object) [
					'type' => 'FunctionReference',
					'name' => $fn,
					'arguments' => $args,
				];

			case 'NamedArgument':
				return (object) [
					'type' => 'NamedArgument',
					'name' => self::castAny($node->content[0]),
					'value' => self::castAny($node->content[1]),
				];

			case 'term-reference':
				return (object) [
					'type' => trim($node->name),
					'value' => trim($node->content),
				];
			default:
				dump($node);
				throw new LogicException("Unsupported node type: {$node->getName()}.");
		}
	}



	private static function flatElements(array $xs)
	{
		$args = [];
		$value = [];
		foreach ($xs as $i => $x) {
			if (is_scalar($x)) {
				$value[] = $x;
			}
			else {
				switch ($x->type) {
					case 'VariableReference':
						$value[] = "{{$x->id}}";
						$args[$x->id] = Null;
						break;
					case 'SelectExpression':
						$value[] = "{{$x->selector}}";
						$args[$x->selector] = Choice::createFrom($x->variants);
						break;
					case 'FunctionReference':
						$inst = new Format($x->name, [substr(reset($x->arguments)->name, 0)], self::formatArguments(array_splice($x->arguments, 1)));
						$symbol = Format::formatPlacement($inst);
						$args[$symbol] = $inst;
						$value[] = "{{$symbol}}";
						break;
					default:
						dump($x);
						throw new LogicException("Unsupported node type: {$x->type}.");
				}
			}
		}
		return [implode('', $value), $args];
	}



	private static function formatArguments(array $xs)
	{
		$ret = [];
		foreach ($xs as $x) {
			$ret[$x->name] = $x->value;
		}
		return $ret;
	}

}



class Expr
{
	public $expression, $args;

	function __construct($expr, array $args)
	{
		$this->expression = $expr;
		$this->args = $args;
	}



	function getArguments()
	{
		return $this->args;
	}



	function invoke(FluentFunctionResource $functions, array $args)
	{
		$map = [];
		foreach ($this->args as $key => $type) {
			switch (True) {
				// Argument přijímá scalar.
				case $type === Null && $key[0] !== '$':
					$map['{$' . $key . '}'] = $map['{' . $key . '}'] = $this->formatValue($functions, self::requireValue($key, $args), $key);
					break;
				case $type === Null:
					$key = ltrim($key, '$');
					$map['{$' . $key . '}'] = $this->formatValue($functions, self::requireValue($key, $args), $key);
					break;
				// Výběr z monžostí.
				case $type instanceof Choice:
					$key = ltrim($key, '$');
					$map['{$' . $key . '}'] = $type->invoke($functions, self::requireValue($key, $args), $args);
					break;
				// Argument se musí nejdříve naformátovat.
				case $type instanceof Format:
					$map['{' . $key . '}'] = $type->invoke($functions, self::requireValue(substr($type->getArguments()[0], 1), $args));
					break;
				default:
					throw new LogicException("Unsupported type of argument: $key => '$type'.");
			}
		}
		return strtr($this->expression, $map);
	}



	function __toString()
	{
		return "expr({$this->expression})";
	}



	private function formatValue(FluentFunctionResource $functions, $val, $key)
	{
		if (is_string($val)) {
			return $val;
		}
		if (is_numeric($val)) {
			return self::requireTranslator('NUMBER', $functions)->format($val, []);
		}
		if ($val instanceof \DateTime) {
			return self::requireTranslator('DATETIME', $functions)->format($val, []);
		}
		if (is_scalar($val)) {
			return (string) $val;
		}
		throw new LogicException("Unsupported type of argument: $key.");
	}



	private static function requireValue($key, array $args)
	{
		if ( ! array_key_exists($key, $args)) {
			throw new InvokeException([$key]);
		}
		return $args[$key];
	}



	private static function requireTranslator($key, FluentFunctionResource $functions)
	{
		if ( ! isset($functions[$key])) {
			throw new LogicException("Function of: $key is not found.");
		}
		return $functions[$key];
	}
}



class InvokeException extends LogicException
{

	private $keys;

	function __construct(array $keys, $code = 0)
	{
		$this->keys = $keys;
		$keys = implode(', ', $keys);
		parent::__construct("Missing reqired keys: $keys.", $code);
	}



	function getMissingKeys()
	{
		return $this->keys;
	}
}



class Choice
{
	private $default, $opts, $args;


	static function createFrom(array $src)
	{
		$opts = [];
		$args = [];
		foreach ($src as $x) {
			if (count($x->args)) {
				$opts[$x->id] = new Expr($x->expression, $x->args);
			}
			else {
				$opts[$x->id] = $x->expression;
			}
			if (isset($x->default)) {
				$default = $x->id;
			}
			foreach ($x->args as $id => $formater) {
				$args[$id] = $formater;
			}
		}
		return new static($opts, $default, $args);
	}



	function __construct(array $opts, $default, array $args = [])
	{
		$this->opts = $opts;
		$this->default = $default;
		$this->args = $args;
	}



	function getArguments()
	{
		return $this->args;
	}



	function getAllArguments()
	{
		$res = [];
		foreach ($this->args as $k => $v) {
			if (empty($v)) {
				$res[$k] = $v;
			}
			else {
				throw new LogicException("Unexpected value of argument.");
			}
		}
		return $res;
	}



	function invoke($functions, $key, array $args)
	{
		if (array_key_exists($key, $this->opts)) {
			$msg = $this->opts[$key];
		}
		if (!isset($msg)) {
			$key = $this->default;
			if (array_key_exists($key, $this->opts)) {
				$msg = $this->opts[$key];
			}
		}

		switch (True) {
			case is_scalar($msg):
				return $msg;
			case $msg instanceof Expr:
				// assert value exists
				return $msg->invoke($functions, $args);
			default:
				throw new LogicException("Unsupported type of argument: $val => '$msg'.");
		}
	}



	function __toString()
	{
		$xs = [];
		foreach ($this->opts as $key => $_) {
			if ($key === $this->default) {
				$key = '*' . $key;
			}
			$xs[] = $key;
		}
		return 'choice(' . implode(', ', $xs) . ')';
	}
}



class Format
{

	private $func, $args, $opts;


	static function formatPlacement(self $inst)
	{
		$xs = [$inst->func];
		foreach ($inst->args as $x) {
			$xs[] = trim($x, '$');
		}
		foreach ($inst->opts as $k => $v) {
			$xs[] = "{$k}:" . md5($v);
		}
		return '$' . implode('_', $xs);
	}



	function __construct($func, array $args, array $opts = [])
	{
		if (empty($args)) {
			throw new LogicException("Empty arguments.");
		}
		$this->func = $func;
		$this->args = $args;
		$this->opts = $opts;
	}



	function getArguments()
	{
		return $this->args;
	}



	function invoke(FluentFunctionResource $functions, $val)
	{
		return self::requireTranslator($this->func, $functions)->format($val, $this->opts);
	}



	function __toString()
	{
		$xs = [];
		foreach ($this->opts as $k => $v) {
			$xs[] = "$k: $v";
		}
		return 'func(' . $this->func . ' ' . implode(', ', $xs) . ')';
	}



	private static function requireTranslator($key, FluentFunctionResource $resource)
	{
		if ( ! isset($resource[$key])) {
			throw new LogicException("Function of: $key is not found.");
		}
		return $resource[$key];
	}

}
