<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


class FluentResourceTest extends PHPUnit_Framework_TestCase
{


	function testCorrect()
	{
		$res = new FluentResource('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
');
		$this->assertEquals('Messages', $res->getType());
		$this->assertEquals([
			self::makeMessage("-brand-name", "Foo 3000"),
			self::makeMessage("welcome", 'Welcome, {$name}, to {-brand-name}!', ['$name' => Null, '-brand-name' => Null]),
			self::makeMessage("greet-by-name", 'Hello, {$name}!', ['$name' => Null]),
		], $res->getMessages());
	}



	function testFail()
	{
		$this->setExpectedException(LogicException::class, '');
		$res = new FluentResource('
welcome
');
		$this->assertEquals('Junk', $res->getType());
		$this->assertEquals([], $res->getMessages());
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
