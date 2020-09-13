<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


class NumberIntlTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($val, $opts, $expected)
	{
		$this->assertSame($expected, NumberIntl::format($val, $opts));
	}



	function dataCorrect()
	{
		return [
			[3.14, [], '3.14'],
			[3, [], '3'],

			[3.14, ['minimumFractionDigits' => 0,], '3.14'],
			[3.14, ['minimumFractionDigits' => 2,], '3.14'],
			[3.14, ['minimumFractionDigits' => 4,], '3.1400'],

			[3, ['minimumFractionDigits' => 2,], '3.00'],

			[3.14, ['maximumFractionDigits' => 0,], '3.14'],
			[3.14, ['maximumFractionDigits' => 2,], '3.14'],
			[3.14, ['maximumFractionDigits' => 4,], '3.14'],
			[3.14, ['maximumFractionDigits' => 4,'minimumFractionDigits' => 3,], '3.140'],
			[3, ['maximumFractionDigits' => 2,], '3'],

		];
	}

}
