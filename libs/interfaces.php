<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;



interface FluentFunctionResource extends \ArrayAccess
{
}



interface FuncIntl
{

	function format($val, array $args);

}
