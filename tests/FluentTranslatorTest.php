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



	function testGetMessage2()
	{
		$id = "time-elapse1";
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage($id);

		$args = [
			'name' => 'Mia',
			'userName' => 'Mia',
			'photoCount' => 'two',
			'duration' => 3.14,
		];
		list($out, $err) = $bundle->formatPattern($msg->value, $args);
		$this->assertSame('Time elapsed: 3.14s.', $out);
		$this->assertSame([], $err);
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
			['time-elapse1', ['duration' => 3.14]
				, 'Time elapsed: 3.14s.'],
			['time-elapse2', ['duration' => 3.14]
				, 'Time elapsed: 3.14s.'],
			['time-elapse3', ['duration' => 3.14]
				, 'Time elapsed: 3.14s.'],
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



	function testFunctionNumber1()
	{
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage('time-elapse1');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['duration' => 1234.54]);
		$this->assertSame('Time elapsed: 1234.54s.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionNumber2()
	{
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage('time-elapse2');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['duration' => 1234.54]);
		$this->assertSame('Time elapsed: 1234.54s.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionNumber3()
	{
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage('time-elapse3');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['duration' => 1234.54]);
		$this->assertSame('Time elapsed: 1234.54s.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionNumber3CZ()
	{
		$bundle = $this->getBundle('cs-CZ');
		$msg = $bundle->getMessage('time-elapse3');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['duration' => 1234.54]);
		$this->assertSame('Time elapsed: 1234.54s.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionDate0CZ()
	{
		$bundle = $this->getBundle('cs-CZ');
		$msg = $bundle->getMessage('date0');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['date' => new \DateTime('2012-02-05 11:50:13')]);
		$this->assertSame('Today is: 5. 2. 2012.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionDate1CZ()
	{
		$bundle = $this->getBundle('cs-CZ');
		$msg = $bundle->getMessage('date1');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['date' => new \DateTime('2012-02-05 11:50:13')]);
		$this->assertSame('Today is: 5. 2. 2012.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionDate1EN()
	{
		$bundle = $this->getBundle('en-GB');
		$msg = $bundle->getMessage('date1');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['date' => new \DateTime('2012-02-05 11:50:13')]);
		$this->assertSame('Today is: 05/02/2012.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionDate2CZ()
	{
		$bundle = $this->getBundle('cs-CZ');
		$msg = $bundle->getMessage('date2');
		list($msg, $err) = $bundle->formatPattern($msg->value, ['date' => new \DateTime('2012-02-05 11:50:13')]);
		$this->assertSame('Now is: 11:50.', $msg);
		$this->assertSame([], $err);
	}



	function testFunctionFail()
	{
		$bundle = $this->getBundle();
		$msg = $bundle->getMessage('time-elapse1');
		list($msg, $err) = $bundle->formatPattern($msg->value, []);
		$this->assertEquals([(object)[
			'type' => 'FluentReferenceError',
			'msg' => 'Unknown external: duration',
		]], $err);
	}



	private function getBundle($locale = 'en-US')
	{
		$bundle = new FluentTranslator($locale);
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

date0 = Today is: {$date}.
date1 = Today is: {DATETIME($date)}.
date2 = Now is: {DATETIME($date, hour: "numeric", minute: "numeric")}.
time-elapse1 = Time elapsed: {NUMBER($duration, minimumFractionDigits: 0)}s.
time-elapse2 = Time elapsed: {NUMBER($duration)}s.
time-elapse3 = Time elapsed: {NUMBER($duration, minimumFractionDigits: 2)}s.
'));
		return $bundle;
	}

}
