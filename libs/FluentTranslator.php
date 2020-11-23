<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;


class FluentTranslator
{
	private $lang;
	private $functions;
	private $items = [];

	function __construct($lang, FluentFunctionResource $functionResource = Null)
	{
		$this->lang = $lang;
		$this->functions = $functionResource ?: new FluentFunctionStaticResource($lang);
	}



	function addResource(FluentResource $res)
	{
		foreach ($res->getMessages() as $x) {
			$this->items[$x->id] = $x;
		}
		return $this;
	}



	function getMessage($id)
	{
		if (isset($this->items[$id])) {
			return $this->items[$id];
		}
	}



	/**
	 * @return [string, [string]] First is formated msg, second is list of errors.
	 */
	function formatPattern(Expr $msgvalue, array $args)
	{
		$error = [];

		// seženeme všechny do hloubky
		$arguments = self::getAllArguments($msgvalue);
		if (empty($arguments)) {
			return [$msgvalue->expression, $error];
		}

		$map = $args;
		foreach ($arguments as $id => $formater) {
			switch (True) {
				case $id[0] !== '$':
					if ($msg = $this->getMessage($id)) {
						list($val, $err) = $this->formatPattern($msg->value, $args);
						$map[$id] = $val;
						$error = array_merge($error, $err);
					}
					else {
						$error[] = self::makeError('Missing', "Missing msg: {$id}");
					}
					break;
			}
		}
		try {
			return [$msgvalue->invoke($this->functions, $map), $error];
		}
		catch (InvokeException $e) {
			return [$msgvalue->expression, array_merge($error, [self::makeError('FluentReference', 'Unknown external: ' . implode(', ', $e->getMissingKeys()))])];
		}
	}



	private static function getAllArguments($msg)
	{
		$res = [];
		foreach ($msg->args as $k => $v) {
			if ($v instanceof Choice) {
				$res = array_merge($res, $v->getAllArguments());
			}
			if ($v instanceof Format) {
				$vals = [];
				foreach ($v->getArguments() as $k2) {
					$vals[$k2] = False;
				}
				$res = array_merge($res, $vals);
				continue;
			}
			$res[$k] = $v;
		}
		return $res;
	}



	private static function makeError($type, $label)
	{
		return (object) [
			'type' => $type . 'Error',
			'msg' => $label,
		];
	}



	private static function formatFluentReferenceError($id)
	{
		return (object) [
			'type' => 'FluentReferenceError',
			'msg' => "Unknown external: {$id}",
		];
	}
}
