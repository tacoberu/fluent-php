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


	function testCorrect()
	{
		$res = (new FluentParser)->parse('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
');

		$this->assertEquals(self::makeMessage("-brand-name", "Foo 3000"), $res[0]);
		$this->assertEquals(self::makeMessage("welcome", 'Welcome, {$name}, to {-brand-name}!', ['$name', '-brand-name']), $res[1]);
		$this->assertEquals(self::makeMessage("greet-by-name", 'Hello, {$name}!', ['$name']), $res[2]);
	}



	function testFail()
	{
		$res = (new FluentParser)->parse('
welcome
');
		$this->assertEquals('Junk', $res->type);
		$this->assertEquals('welcome', $res->content);
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
