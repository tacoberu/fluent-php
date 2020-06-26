<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


class FluentTranslatorTest extends PHPUnit_Framework_TestCase
{


	function testGetMessage()
	{
		$msg = $this->getBundle()->getMessage("welcome");
		$this->assertEquals('welcome', $msg->id);
	}



	/**
	 * @dataProvider dataFormatPattern
	 */
	function testFormatPattern($id, $args, $out)
	{
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage($id);
		list($msg, $err) = $bundle->formatPattern($msg->value, $args);
		$this->assertSame($out, $msg);
		$this->assertSame([], $err);
	}



	function dataFormatPattern()
	{
		return [
			['welcome', ["name" => "Anna"]
				, 'Welcome, Anna, to Foo 3000!'],
			['welcome', ["name" => "Taco"]
				, 'Welcome, Taco, to Foo 3000!'],
			['-brand-name', ["name" => "Taco"]
				, 'Foo 3000'],
			['-brand-name', []
				, 'Foo 3000'],
			['greet-by-name', ["name" => "Taco"]
				, 'Hello, Taco!'],
			['shared-photos', ["userName" => "Taco", 'photoCount' => 'one']
				, '  Taco co  added a new photo for Taco.'],
			['shared-photos', ["userName" => "Taco", 'photoCount' => 'two']
				, '  Taco co  added two photos Foo 3000.'],
			['menu-save', []
				, 'Save'],
			['help-menu-save', []
				, 'Click Save to save the file.'],
		];
	}



	function testFail()
	{
		$bundle = $this->getBundle();
		$this->assertNull($bundle->getMessage('noop'));

		$msg = $bundle->getMessage('welcome');
		list($msg, $err) = $bundle->formatPattern($msg->value, []);
		//~ $this->assertSame('Welcome, {$name}, to Foo 3000!', $msg);
		$this->assertSame('Welcome, {$name}, to {-brand-name}!', $msg);
		$this->assertEquals([(object) [
			'type' => 'FluentReferenceError',
			'msg' => 'Unknown external: name',
		]], $err);
	}



	private function getBundle()
	{
		$bundle = new FluentTranslator("en-US");
		$bundle->addResource(new FluentResource('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
menu-save = Save
help-menu-save = Click { menu-save } to save the file.

shared-photos =
  {$userName} co {$photoCount ->
    *[one] added a new photo for {$userName}
     [two] added two photos {-brand-name}
  }.

'));
		return $bundle;
	}

}
