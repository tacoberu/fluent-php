<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


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

		$this->assertCount(4, $res);
		$this->assertEquals('Message', $res[3]->type);
		$this->assertEquals('shared-photos', $res[3]->id);
		$this->assertEquals("ahoj jak\n  se máš. {\$userName} co  {\$photoCount}.", $res[3]->value->expression);
		$this->assertEquals(['$userName' => Null, '$photoCount' => new Choice([
			'one' => ' added a new photo',
			'two' => ' added two photos',
		], 'one')], $res[3]->value->args);
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
		$this->assertCount(4, $res);
		$this->assertEquals(self::makeComment("# Simple things are simple."), $res[0]);
		$this->assertEquals(self::makeMessage("hello-user", 'Hello, {$userName}!', ['$userName' => Null]), $res[1]);
		$this->assertEquals(self::makeComment("# Complex things are possible."), $res[2]);
		$this->assertEquals(self::makeMessage("shared-photos", '    {$userName} {$photoCount}.', [
			'$userName' => Null,
			'$photoCount' => new Choice([
				'one' => ' added a new photo',
				'other' => ' their stream',
				'male' => ' his stream',
				'female' => ' her stream',
			], 'other', ['$photoCount' => Null]),
		]), $res[3]);
	}



	function testStringLiteral()
	{
		$res = (new FluentParser)->parse('opening-brace = This message features an opening curly brace: {"{-\"-"}.');
		$this->assertCount(1, $res);
		$this->assertEquals(self::makeMessage("opening-brace", 'This message features an opening curly brace: {-"-.'), $res[0]);
	}



	function testFail()
	{
		try {
			(new FluentParser)->parse('
welcome
');
		}
		catch (LogicException $e) {
			$this->assertSame(0, $e->getCode());
			$this->assertEquals("Unexpected token on line 2, column 1: expected token '9' or '1'
 1 > "."
 2 > welcome
  ---^
 3 > "."
", $e->getMessage());
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
			'value' => (object) [
				'expression' => $expression,
				'args' => $args,
			],
		];
	}

}
