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
			// Your DPI ratio is { NUMBER($ratio, minimumFractionDigits: 2) }
			[new Expr('Your DPI ratio is {$ratio}', [
					'ratio' => new Format('NUMBER', [
						'minimumFractionDigits' => 2,
					]),
				]),
				['ratio' => 4321],
				'expr(Your DPI ratio is {$ratio})',
				'Your DPI ratio is 4321.00'
				],
		];
	}



	function testChoice()
	{
		$inst = new Choice([
			'male' => 'his stream',
			'female' => 'her stream',
			'other' => 'their stream',
		], 'other');
		$this->assertEquals('choice(male, female, *other)', (string)$inst);
		$this->assertEquals('his stream', $inst->invoke('male', ['photoCount' => 4]));
		$this->assertEquals('their stream', $inst->invoke('?', ['photoCount' => 4]));
	}



	/*

	time-elapse = Time elapsed: {NUMBER($duration, maximumFractionDigits: 0)}s.
	 */
	function testFormat()
	{
		$inst = new Format('NUMBER', [
			'minimumFractionDigits' => 3,
		]);
		$this->assertSame('func(NUMBER minimumFractionDigits: 3)', (string)$inst);
		$this->assertSame('12345.600', $inst->invoke(12345.6));
	}


}