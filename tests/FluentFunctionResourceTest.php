<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use PHPUnit_Framework_TestCase;


class FluentFunctionResourceTest extends PHPUnit_Framework_TestCase
{


	function testOffsetGet()
	{
		$inst = new FluentFunctionStaticResource('abc');
		$this->assertInstanceOf(NumberIntl::class, $inst['NUMBER']);
		$this->assertInstanceOf(DateTimeIntl::class, $inst['DATETIME']);
	}


}
