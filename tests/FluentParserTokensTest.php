<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


class FluentParserTokensTest extends PHPUnit_Framework_TestCase
{


	function testExprArguments()
	{
		$inst = new Expr('Added {$photoCount} new photos', [
			'photoCount' => Null,
		]);
		$this->assertEquals([
			'photoCount' => null,
		], $inst->getArguments());
	}



	/**
	 * @dataProvider dataExpr
	 */
	function testExpr($token, array $args, $str, $msg)
	{
		$this->assertEquals($str, (string)$token);
		$this->assertEquals($msg, $token->invoke($args));
	}



	function dataExpr()
	{
		return [
			[new Expr('Added {$photoCount} new photos', [
					'photoCount' => Null,
				]),
				['photoCount' => 4],
				'expr(Added {$photoCount} new photos)',
				'Added 4 new photos'
				],
			[new Expr('Hello, {$user}, awn {$gender}!', [
					'user' => Null,
					'gender' => new Choice([
						'male' => 'his stream',
						'female' => 'her stream',
						'other' => 'their stream',
					], 'other'),
				]),
				['user' => "Martin", 'gender' => 'male'],
				'expr(Hello, {$user}, awn {$gender}!)',
				'Hello, Martin, awn his stream!'
				],
			[new Expr('{$userName} {$photoCount} to {$userGender}.', [
					'userName' => Null,
					'photoCount' => new Choice([
						'one' => 'added a new photo',
						'other' => new Expr('added {$photoCount} new photos', [
							'photoCount' => Null,
						]),
					], 'other'),
					'userGender' => new Choice([
						'male' => 'his stream',
						'female' => 'her stream',
						'other' => 'their stream',
					], 'other'),
				]),
				['userName' => "Martin", 'userGender' => 'male', 'photoCount' => 13],
				'expr({$userName} {$photoCount} to {$userGender}.)',
				'Martin added 13 new photos to his stream.'
				],
		];
	}



	function testChoice1()
	{
		$inst = new Choice([
			'male' => 'his stream',
			'female' => 'her stream',
			'other' => 'their stream',
		], 'other');
		$this->assertEquals('choice(male, female, *other)', (string)$inst);
		$this->assertEquals('his stream', $inst->invoke('male', ['photoCount' => 4]));
		$this->assertEquals('their stream', $inst->invoke('?', ['photoCount' => 4]));
		$this->assertEquals([], $inst->getArguments());
	}



	function testChoice2()
	{
		$inst = new Choice([
			'male' => 'his stream',
			'other' => new Expr('added {$photoCount} new photos', [
				'photoCount' => Null,
			]),
		], 'other');
		$this->assertEquals('choice(male, *other)', (string)$inst);
		$this->assertEquals('his stream', $inst->invoke('male', ['photoCount' => 4]));
		$this->assertEquals('added 4 new photos', $inst->invoke('?', ['photoCount' => 4]));
		$this->assertEquals([], $inst->getArguments());
	}



	/**
	 * time-elapse = Time elapsed: {NUMBER($duration, maximumFractionDigits: 0)}s.
	 */
	function testFormat()
	{
		$inst = new Choice([
			'Male' => 'his stream',
			'other' => new Expr('added {$photoCount} new photos', [
				'photoCount' => Null,
			]),
		], 'other');
		$this->assertEquals('choice(Male, *other)', (string)$inst);
		$this->assertEquals('his stream', $inst->invoke('Male', ['photoCount' => 4]));
		$this->assertEquals('added 4 new photos', $inst->invoke('?', ['photoCount' => 4]));
		$this->assertEquals([], $inst->getArguments());
	}


}
