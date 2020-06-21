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
		$nl = new Pattern(Null, ['~\n+~'], False);
		$comment = new Pattern('Comment', ['~^#.*$~m']);
		$identifier = new Pattern('Identifier', ['~' . self::$symbolPattern . '~']);
		$textElement = new Pattern('TextElement', ['~[^\{\}]+~s']);
		$variableReference = new Pattern('VariableReference', ['~\{\s*' . self::$symbolPattern . '\s*\}~i']);
		$selectExpression = new FluentSelectExpression('SelectExpression');
		$stringLiteral = new Sequence('StringLiteral', [
			new Pattern(Null, ['~\{[ \t]*~'], False),
			new Text('StringLiteral'),
			new Pattern(Null, ['~[ \t]*\}~'], False),
		]);

		$pattern = new Variants('Pattern', [
			$nl,
			$variableReference,
			$selectExpression,
			$stringLiteral,
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
					"value" => (object) ['expression' => $value->expression, 'args' => $value->args],
					//~ "value" => new Expr($value->expression, $value->args),
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
	private $expr, $args;

	function __construct($expr, array $args)
	{
		$this->expr = $expr;
		$this->args = $args;
	}



	function invoke(array $args)
	{
		$map = [];
		foreach ($this->args as $key => $type) {
			switch (True) {
				// Argument přijímá scalar.
				case $type === Null:
					// assert value exists
					$map['{$' . $key . '}'] = $args[$key];
					break;
				// Argument se musí nejdříve naformátovat.
				case $type instanceof Choice:
					// assert value exists
					$map['{$' . $key . '}'] = $type->invoke($args[$key], $args);
					break;
				case $type instanceof Format:
					// assert value exists
					$map['{$' . $key . '}'] = $type->invoke($args[$key]);
					break;
				// @TODO Expr
				default:
					throw new LogicException("Unsupported type of argument: $key => '$type'.");
			}
		}
		return strtr($this->expr, $map);
	}



	function __toString()
	{
		return "expr({$this->expr})";
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
			$opts[$x->id] = $x->expression;
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



	function getAllArguments()
	{
		$res = [];
		foreach ($this->args as $k => $v) {
			if (empty($v)) {
				$res[$k] = $v;
			}
			else {
				dump($v);
				die('=====[' . __line__ . '] ' . __file__);
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



class Format
{

	private $func, $args;

	function __construct($func, array $args)
	{
		$this->func = $func;
		$this->args = $args;
	}



	/**
	 * @TODO Zobecnit a přidat další funkce.
	 */
	function invoke($val)
	{
		switch ($this->func) {
			case 'NUMBER':
				return self::formatNumber($val, $this->args);
			default:
				throw new LogicException("Unsupported function {$this->func}.");
		}
	}



	function __toString()
	{
		$xs = [];
		foreach ($this->args as $k => $v) {
			$xs[] = "$k: $v";
		}
		return 'func(' . $this->func . ' ' . implode(', ', $xs) . ')';
	}



	/**
	 * @TODO Doplnit další volby.
	 */
	private static function formatNumber($val, array $args)
	{
		$format = '%F';
		if (array_key_exists('minimumFractionDigits', $args)) {
			$format = '%01.' . $args['minimumFractionDigits'] . 'F';
		}
		return sprintf($format, $val);
	}

}
