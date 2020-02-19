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
		return $this->items[$id];
	}



	/**
	 * @return [string, [string]] First is formated msg, second is list of errors.
	 */
	function formatPattern($msgvalue, array $args)
	{
		$map = [];
		$error = [];
		foreach ($args as $x => $v) {
			$map["{\$$x}"] = $v;
		}
		foreach ($msgvalue->args as $id) {
			switch (True) {
				case $id[0] === '$':
					if ( ! array_key_exists(substr($id, 1), $args)) {
						$error[] = self::formatFluentReferenceError(substr($id, 1));
					}
					break;
				case $id[0] === '-':
					$msg = $this->getMessage($id);
					list($val, $err) = $this->formatPattern($msg->value, []);
					$map['{' . $id . '}'] = $val;
					$error = array_merge($error, $err);
					break;
				default:
					dump($id);
					die('=====[' . __line__ . '] ' . __file__);
			}
		}
		return [
			strtr($msgvalue->expression, $map),
			$error
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
