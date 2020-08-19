<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl\BNF;

use Taco\BNF\Token;
use Taco\BNF\Utils;
use Taco\BNF\Ref;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;
use Taco\BNF\Combinators\Whitechars;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Match;
use Taco\BNF\Combinators\Indent;
use Taco\BNF\Combinators\OneOf;


class FluentSelectExpression implements Combinator
{

	use BaseCombinator;


	function __construct($name, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
	}



	function getExpectedNames()
	{
		if (empty($this->name)) {
			return [];
		}
		return [$this->name];
	}



	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		$sub = substr($src, $offset);
		list($token, $expected) = $this->subscan($sub, $bank);
		if (empty($token)) {
			return [$token, $expected];
		}
		return [self::reposition($token, $offset), $expected];
	}



	private function subscan($sub, array $bank)
	{
		static $valuePattern = '[a-zA-Z0-9\-]*';
		static $skipIndent = [['{', '}']];

		// Zpracovat hlavičku obsahující výraz
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~[\r\n]+~',], False);
		$identifier = (new Ref('Identifier'))->requireFrom($bank);
		$beginPart = new Sequence(Null, [
			new Pattern('select-start', ['~\{\s*~'], False),
			$identifier,
			$sep,
			new Match('assign', ['->'], False),
			(new Pattern(Null, ['~[ \t]+~'], False))->setOptional(),
			$nl,
		]);
		list($token, $expected) = $beginPart->scan($sub, 0, $bank);
		if ( ! $token) {
			return [False, $expected];
		}

		$selectOption = new Sequence('SelectOption', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('OptionIdentifier', ['~\[\s*' . $valuePattern . '\s*\]~']),
			new Indent('Pattern', (new Ref('Pattern'))->requireFrom($bank), $skipIndent),
		]);
		$endlist = new OneOf(Null, [
			$nl,
			new Pattern(Null, ['~\s*\}~']),
		]);

		$indent = self::calculateIndent($sub, $token->end);
		$res = [];
		$beginPartToken = $token;
		$plusoffset = $beginPartToken->end;
		do {
			$newoffset = $token->end;
			if ( ! ($block = self::sliceBlock($indent, $sub, $newoffset))) {
				break;
			}

			list($token, ) = $selectOption->scan($block, 0, $bank);
			if (empty($token)) {
				break;
			}

			$nt = self::reposition($token, $plusoffset);
			$res[] = $nt;
			$plusoffset = $nt->end;
			$newoffset = $newoffset + strlen($block);
			list($token, ) = $nl->scan($sub, $newoffset, $bank);
		} while($token);

		list($token, ) = (new Pattern(Null, ['~\s*\}~']))->scan($sub, $newoffset, $bank);
		if ($token) {
			$newoffset = $token->end;
		}

		$res = Utils::flatting($res);

		return [
			new Token($this,
				array_merge($beginPartToken->content, [
					new Token($selectOption, $res, 0, $plusoffset)
				]),
				0,
				$newoffset
				),
			[],
		];
	}



	private static function reposition(Token $orig, $offset)
	{
		if (empty($offset)) {
			return $orig;
		}
		return new Token($orig->type, $orig->content, $orig->start + $offset, $orig->end + $offset);
	}



	private static function calculateIndent($src, $offset)
	{
		if ( ! preg_match('~(\s+)[\s\*]~', $src, $out, 0, $offset)) {
			return False;
		}
		return $out[1];
	}



	private static function sliceBlock($indent, $src, $offset)
	{
		$endIndex = self::lookupEndOfBlock($indent, $src, $offset);
		// @TODO Mám tu zadrátováno bloky { a }.
		list($startBlockIndex, $endBlockIndex) = Utils::lookupBlock('{', '}', $src, $offset);
		if ($startBlockIndex !== False && $startBlockIndex < $endIndex) {
			$endIndex = self::lookupEndOfBlock($indent, $src, $endBlockIndex);
		}

		if ($endIndex !== False) {
			return rtrim(substr($src, $offset, ($endIndex - $offset)), "\n");
		}
		// vše až do konce
		return rtrim(substr($src, $offset), "\n");
	}



	private static function lookupEndOfBlock($indent, $src, $offset)
	{
		// začátek řádku
		if (preg_match('~\n[^\s]~s', $src, $out1, PREG_OFFSET_CAPTURE, $offset)) {
			$out1 = reset($out1);
		}
		// odskočení zpět
		if (preg_match('~\n' . $indent . '[\s\*][^\s]~s', $src, $out2, PREG_OFFSET_CAPTURE, $offset)) {
			$out2 = reset($out2);
		}

		if (count($out1) && count($out2)) {
			if ($out1[1] < $out2[1]) {
				$out = $out1;
			}
			else {
				$out = $out2;
			}
			return $out[1];
		}
		elseif (count($out1)) {
			return $out1[1];
		}
		elseif (count($out2)) {
			return $out2[1];
		}
		return strpos($src, "\n", $offset); // return False;
	}

}
