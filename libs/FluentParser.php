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
		$pattern = new Sequence(Null, [
			new Pattern(Null, ['~\{[ \t]*~'], False),
			new OneOf('Pattern', [
				new Pattern('VariableReference', ['~' . self::$symbolPattern . '~']),
				new Text('StringLiteral'),
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
			case 'TextElement':
				return $node->content;
			case 'StringLiteral':
				return substr($node->content, 1, -1);
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
					default:
						dump($x);
						throw new LogicException("Unsupported node type: {$x->type}.");
				}
			}
		}
		return [implode('', $value), $args];
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



	function invoke(array $args)
	{
		$map = [];
		foreach ($this->args as $key => $type) {
			switch (True) {
				// Argument přijímá scalar.
				case $type === Null && $key[0] !== '$':
					$map['{' . $key . '}'] = self::requireValue($key, $args);
					$map['{$' . $key . '}'] = self::requireValue($key, $args); //@TODO
					break;
				case $type === Null:
					$key = ltrim($key, '$');
					$map['{$' . $key . '}'] = self::requireValue($key, $args);
					break;
				// Výběr z monžostí.
				case $type instanceof Choice:
					$key = ltrim($key, '$');
					$map['{$' . $key . '}'] = $type->invoke(self::requireValue($key, $args), $args);
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



	private static function requireValue($key, array $args)
	{
		if ( ! array_key_exists($key, $args)) {
			throw new InvokeException([$key]);
		}
		return $args[$key];
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



	function invoke($key, array $args)
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
				return $msg->invoke($args);
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
