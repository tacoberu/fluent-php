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
