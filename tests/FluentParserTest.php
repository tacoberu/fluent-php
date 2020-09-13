<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;
use Taco\BNF\ParseException;


class FluentParserTest extends PHPUnit_Framework_TestCase
{


	function testSample1()
	{
		$res = (new FluentParser)->parse('
# comment
## comment 2
### comment 3
shared-photos = ahoj jak
  se máš. {$userName} co  {$photoCount ->
    *[one] added a new photo
     [two] added two photos
  }.');

		$this->assertCount(1, $res);
		$this->assertEquals('Message', $res[0]->type);
		$this->assertEquals('shared-photos', $res[0]->id);
		$this->assertEquals("ahoj jak\n  se máš. {\$userName} co  {\$photoCount}.", $res[0]->value->expression);
		$this->assertEquals(['$userName' => Null, '$photoCount' => new Choice([
			'one' => ' added a new photo',
			'two' => ' added two photos',
		], 'one')], $res[0]->value->args);
	}



	function testSample2()
	{
		$res = (new FluentParser)->parse('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
');

		$this->assertEquals(self::makeMessage("-brand-name", "Foo 3000"), $res[0]);
		$this->assertEquals(self::makeMessage("welcome", 'Welcome, {$name}, to {-brand-name}!', ['$name' => Null, '-brand-name' => Null]), $res[1]);
		$this->assertEquals(self::makeMessage("greet-by-name", 'Hello, {$name}!', ['$name' => Null]), $res[2]);
	}



	function testSample3()
	{
		$res = (new FluentParser)->parse('
# Simple things are simple.
hello-user = Hello, {$userName}!

# Complex things are possible.
shared-photos =
    {$userName} {$photoCount ->
        [one] added a new photo
       *[other] added {$photoCount} new photos
    } to {$userGender ->
        [male] his stream
        [female] her stream
       *[other] their stream
    }.
');
		$this->assertCount(2, $res);
		//~ $this->assertEquals(self::makeComment("# Simple things are simple."), $res[0]);
		$this->assertEquals(self::makeMessage("hello-user", 'Hello, {$userName}!', ['$userName' => Null]), $res[0]);
		//~ $this->assertEquals(self::makeComment("# Complex things are possible."), $res[2]);
		$this->assertEquals(self::makeMessage("shared-photos", '    {$userName} {$photoCount}.', [
			'$userName' => Null,
			'$photoCount' => new Choice([
				'one' => ' added a new photo',
				'other' => ' their stream',
				'male' => ' his stream',
				'female' => ' her stream',
			], 'other', ['$photoCount' => Null]),
		]), $res[1]);
	}



	function testStringLiteral()
	{
		$res = (new FluentParser)->parse('opening-brace = This message features an opening curly brace: {"{-\"-"}.');
		$this->assertCount(1, $res);
		$this->assertEquals(self::makeMessage("opening-brace", 'This message features an opening curly brace: {-"-.'), $res[0]);
	}



	function testFunctionDateTime()
	{
		$res = (new FluentParser)->parse('today-is = Today is { DATETIME($date, weekday: 3.14) }');
		$this->assertCount(1, $res);
		$format1 = new Format('DATETIME', [
				'$date',
			], [
				'weekday' => 3.14,
			]);
		$this->assertEquals(self::makeMessage("today-is", 'Today is {$DATETIME_date_weekday:4beed3b9c4a886067de0e3a094246f78}', [
			Format::formatPlacement($format1) => $format1,
		]), $res[0]);
	}



	function testChoiceSimple()
	{
		$translators = ['NUMBER' => new NumberIntl('cs-CZ')];
		$inst = new Choice([
			'first' => 'premier',
			'second' => 'deuxième',
			'other' => 'tous les autres',
		], 'other', []);
		$this->assertEquals('deuxième', $inst->invoke($translators, 'second', []));
		$this->assertEquals('premier', $inst->invoke($translators, 'first', []));
		$this->assertEquals('tous les autres', $inst->invoke($translators, 'other', []));
		$this->assertEquals('tous les autres', $inst->invoke($translators, 'noop', []));
	}



	function testChoiceWithArgs()
	{
		$translators = ['NUMBER' => new NumberIntl('cs-CZ')];
		$inst = new Choice([
			'first' => 'premier {$value}',
			'second' => 'deuxième',
			'other' => 'tous les autres',
		], 'other', []);
		$this->assertEquals('premier {$value}', $inst->invoke($translators, 'first', []));
	}



	function testExpr()
	{
		$translators = ['NUMBER' => new NumberIntl('cs-CZ')];
		$inst = new Expr('Welcome, {$name}, to {-brand-name}!', [
			'$name' => null,
			'-brand-name' => null,
		]);
		$this->assertEquals('Welcome, Nome, to Fluent!', $inst->invoke($translators, ['name' => 'Nome', '-brand-name' => 'Fluent']));
	}



	function testFail()
	{
		try {
			(new FluentParser)->parse('
welcome
');
		}
		catch (ParseException $e) {
			$this->assertSame(12, $e->getCode());
			$this->assertEquals("Unexpected token on line 2, column 1: expected token 'assign' or 'Comment'", $e->getMessage());
		}
	}



	private static function makeComment($content)
	{
		return (object) [
			'type' => "Comment",
			'content' => $content,
		];
	}



	private static function makeMessage($id, $expression, $args = [])
	{
		return (object) [
			'type' => "Message",
			'id' => $id,
			'value' => new Expr($expression, $args),
		];
	}

}
