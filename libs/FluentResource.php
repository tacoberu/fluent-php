<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;


class FluentResource
{
	private $src;

	function __construct($src)
	{
		$this->src = (new FluentParser())->parse($src);
	}



	function getType()
	{
		if (is_array($this->src)) {
			return 'Messages';
		}
		return $this->src->type;
	}



	function getMessages()
	{
		if ( ! is_array($this->src)) {
			return [];
		}
		return $this->src;
	}

}



class FluentFunctionStaticResource implements FluentFunctionResource
{
	private $locale;
	private $items = [];


	function __construct($lang)
	{
		$this->locale = $lang;
		$this->set('DATETIME', DateTimeIntl::createFromFile($lang, __dir__ . '/Intl'));
		$this->set('NUMBER', new NumberIntl($lang));
	}



	function offsetExists($offset)
	{
		return isset($this->items[$offset]);
	}



	function offsetGet($offset)
	{
		return $this->items[$offset];
	}



	function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}



	function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}



	private function set($offset, FuncIntl $value)
	{
		$this->items[$offset] = $value;
	}

}
