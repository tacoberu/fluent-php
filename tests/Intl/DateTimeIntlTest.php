<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;
use LogicException;


class DateTimeIntlTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($locale, $val, $opts, $expected)
	{
		$this->assertSame($expected, $this->getFormater($locale)->format($val, $opts));
	}



	function dataCorrect()
	{
		return [
			['cs-CZ', 3.14, [], "3.14"],
			// default
			['cs-CZ', '2012-02-28 12:32:54', [], '28. 2. 2012'],
			['en-US', '2012-02-28 12:32:54', [], '2/28/2012'],
			['en-US', '2012-02-08 02:02:54', [], '2/8/2012'],
			['en-GB', '2012-02-28 12:32:54', [], '28/02/2012'],
			['en-GB', '2012-02-08 02:02:54', [], '08/02/2012'],
			['en-AU', '2012-02-08 02:02:54', [], '08/02/2012'],
			['de-DE', '2012-02-08 02:02:54', [], '8.2.2012'],
			['nope',  '2012-02-08 02:02:54', [], '8/2/2012'],

			['cs-CZ', '2012-02-28 12:32:54', ['year' => 'numeric',], '2012'],
			['cs-CZ', '2012-02-28 12:32:54', ['year' => 'short',], '12'],
			['cs-CZ', '2012-02-28 12:32:54', ['year' => 'long',], '2012'],

			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'numeric',], '2'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'short',], 'úno'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'long',], 'únor'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'long', 'day' => 'numeric'], '28. února'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'long', 'day' => 'numeric', 'year' => 'numeric'], '28. února 2012'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'long', 'day' => 'numeric', 'year' => 'short'], '28. února 12'],
			['cs-CZ', '2012-02-28 12:32:54', ['month' => 'long', 'day' => 'numeric', 'year' => '2-digit'], '28. února 12'],

			['cs-CZ', '2012-02-28 12:32:54', ['day' => 'numeric',], '28.'],
			['cs-CZ', '2012-02-28 12:32:54', ['day' => 'short',], '28.'],
			['cs-CZ', '2012-02-28 12:32:54', ['day' => 'long',], '28.'],

			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'numeric',], '18'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'numeric', 'hour12' => 1], '6'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'numeric', 'hour12' => True], '6'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'numeric', 'hour12' => 0], '18'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'numeric', 'hour12' => False], '18'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'short',], '18'],
			['cs-CZ', '2012-02-28 18:32:54', ['hour' => 'long',], '18'],

			['cs-CZ', '2012-02-28 12:32:54', ['minute' => 'numeric',], '32'],
			['cs-CZ', '2012-02-28 12:32:54', ['minute' => 'short',], '32'],
			['cs-CZ', '2012-02-28 12:32:54', ['minute' => 'long',], '32'],

			['cs-CZ', '2012-02-28 12:32:54', ['second' => 'numeric',], '54'],
			['cs-CZ', '2012-02-28 12:32:54', ['second' => 'short',], '54'],
			['cs-CZ', '2012-02-28 12:32:54', ['second' => 'long',], '54'],

			['cs-CZ', '2012-02-28 12:32:54', ['hour' => 'numeric','minute' => 'numeric','second' => 'numeric',], '12:32:54'],
			['cs-CZ', '2012-02-28 12:32:54', ['weekday' => 'long', 'year' => 'numeric', 'month' => 'long', 'day' => 'numeric',], 'úterý 28. února 2012'],
			['en-US', '2012-02-28 12:32:54', ['hour' => 'numeric','minute' => 'numeric','second' => 'numeric',], '12:32:54'],
			['en-GB', '2012-02-28 12:32:54', ['weekday' => 'long', 'year' => 'numeric', 'month' => 'long', 'day' => 'numeric',], 'Monday, 28 February 2012'],
			['en-US', '2012-02-28 12:32:54', ['weekday' => 'long', 'year' => 'numeric', 'month' => 'long', 'day' => 'numeric',], 'Monday, February 28, 2012'],
		];
	}



	function _testFormatObject()
	{
		$val = new \DateTime('2020-01-02 11:12:55');
		$this->assertSame('2. 1. 2020', $this->getFormater()->format($val, []));
	}



	function _testDev()
	{

	}



	private function getFormater($locale = 'cs-CZ')
	{
		return DateTimeIntl::createFromFile($locale, __dir__ . '/../../libs/Intl');
	}
}
