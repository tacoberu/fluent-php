<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;


class FluentTranslator
{
	private $lang;
	private $items = [];

	function __construct($lang)
	{
		$this->lang = $lang;
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
	function formatPattern($msgvalue, array $args)
	{
		$error = [];

		$arguments = self::getAllArguments($msgvalue);
		$expression = $msgvalue->expression;

		$globally = [];
		foreach ($arguments as $id => $formater) {
			switch (True) {
				case $id[0] === '-':
					if ($msg = $this->getMessage($id)) {
						list($val, $err) = $this->formatPattern($msg->value, []);
						$globally['{' . $id . '}'] = $val;
						$error = array_merge($error, $err);
					}
					else {
						$error[] = self::makeError('Missing', "Missing msg: {$id}");
					}
					break;
			}
		}

		$map = $globally;
		foreach ($arguments as $id => $formater) {
			switch (True) {
				case $id[0] === '$' && $formater:
					$map["{{$id}}"] = $formater->invoke($args[substr($id, 1)], $args);
					break;
			}
		}
		$expression = strtr($expression, $map);

		$map = $globally;
		foreach ($arguments as $id => $formater) {
			switch (True) {
				case $id[0] === '-':
				case $id[0] === '$' && $formater:
					break;
				case $id[0] === '$' && empty($formater):
					if ( ! array_key_exists(substr($id, 1), $args)) {
						$error[] = self::formatFluentReferenceError(substr($id, 1));
					}
					else {
						$map["{{$id}}"] = $args[substr($id, 1)];
					}
					break;
				default:
					dump($id);
					die('=====[' . __line__ . '] ' . __file__);
			}
		}

		return [
			strtr($expression, $map),
			$error
		];
	}



	private static function getAllArguments($msg)
	{
		$res = [];
		foreach ($msg->args as $k => $v) {
			if ($v instanceof Choice) {
				$res = array_merge($res, $v->getAllArguments());
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
