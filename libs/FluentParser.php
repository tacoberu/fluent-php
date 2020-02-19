<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;


class FluentParser
{

	function parse($src)
	{
		$items = [];
		// první naivní, jen rozdělíme podle řádek a "="
		foreach (explode("\n", $src) as $x) {
			if (empty($x)) {
				continue;
			}
			$chip = self::parseMessage($x);
			if ($chip->type === 'Junk') {
				return $chip;
			}
			else {
				$items[] = $chip;
			}
		}

		return $items;
	}



	private static function parseMessage($src)
	{
		if (strpos($src, '=') === False) {
			return (object) [
				"type" => "Junk",
				"content" => $src,
				"annotations" => [
					(object) [
						"type" => "Annotation",
						"message" => "Expected token: \"=\"",
						"code" => "E0003",
						"arguments" => ["="]
					],
				],
			];
		}
		list($id, $msg) = explode("=", $src, 2);

		// sjednocení { $symbol } -> {$symbol}
		$msg = preg_replace('~\{\s*(\$?[a-zA-Z-]+)\s*\}~si', '{$1}', trim($msg));

		return (object) [
			"type" => "Message",
			"id" => trim($id),
			"value" => (object) [
				"expression" => $msg,
				"args" => self::parseArgumentsFromMessage($msg),
			],
		];
	}



	private static function parseArgumentsFromMessage($src)
	{
		if (strpos($src, '{') === False) {
			return [];
		}
		if (preg_match_all('~\{(\$?[a-zA-Z-]+)\}~si', $src, $matches)) {
			return $matches[1];
		}
		return [];
	}

}
