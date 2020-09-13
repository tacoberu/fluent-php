<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;

use DateTime;


/**
 * https://www.projectfluent.org/fluent/guide/functions.html
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat
 * https://tc39.es/ecma402/#datetimeformat-objects
 */
class DateTimeIntl
{
	const HOUR12 = 'hour12';
	const WEEKDAY = 'weekday';
	const ERA = 'era';
	const YEAR = 'year';
	const MONTH = 'month';
	const DAY = 'day';
	const HOUR = 'hour';
	const MINUTE = 'minute';
	const SECOND = 'second';
	const TIMEZONENAME = 'timeZoneName';

	const NUMERIC = 'numeric';
	const SHORT = 'short';
	const TWO_DIGIT = '2-digit';
	const LONG = 'long';
	const NARROW = 'narrow';



	private $locale;
	private $formats;
    private $names;


	static function createFromFile($locale, $path)
	{
		$def = self::loadfile($path, $locale);
		return new static($locale, $def['formats'], $def['names']);
	}



	private static function loadfile($path, $name)
	{
		if (file_exists("$path/{$name}.php")) {
			$def = require "$path/{$name}.php";
		}
		else {
			$def = require "$path/std.php";
		}
		if (isset($def['extend'])) {
			$base = self::loadfile($path, $def['extend']);
			if ( ! isset($base['formats'])) {
				$base['formats'] = [];
			}
			if ( ! isset($base['names'])) {
				$base['names'] = [];
			}
			if ( ! isset($def['formats'])) {
				$def['formats'] = [];
			}
			if ( ! isset($def['names'])) {
				$def['names'] = [];
			}
			return [
				'formats' => array_merge($base['formats'], $def['formats']),
				'names' => array_merge($base['names'], $def['names']),
			];
		}
		else {
			return $def;
		}
	}



	function __construct($locale, array $formats, array $names)
	{
		$this->locale = $locale;
		$this->formats = $formats;
		$this->names = $names;
	}



	function format($val, array $args)
	{
		if ( ! ($date = self::validateDate($val))) {
			return (string) $val;
		}
		if (empty($args)) {
			$args = self::defaultOptions();
		}
		$formatVariant = [];
		$format = $defaultFormat = [
			'year' => $date->format('Y'),
			'month' => $date->format('n'),
			'0month' => $date->format('m'),
			'day' => $date->format('j'),
			'0day' => $date->format('d'),
			'hour' => $date->format('G'),
			'minute' => $date->format('i'),
			'second' => $date->format('s'),
		];
		foreach ($this->names as $name => $table) {
			switch (count($table)) {
				case 12:
					$format[$name] = $this->resolveMonth($date->format('n'), $name);
					break;
				case 7:
					$format[$name] = $this->resolveMonth($date->format('N'), $name);
					break;
			}
		}

		/*
		if (array_key_exists(self::ERA, $args)) {
			"narrow", "short", "long"
		}
		if (array_key_exists(self::TIMEZONENAME, $args)) {
			"short", "long"
		}
		*/

		if (array_key_exists(self::YEAR, $args)) {
			switch ($args[self::YEAR]) {
				case self::NUMERIC:
				case self::LONG: // invalid
					$format['year'] = $date->format('Y');
					$formatVariant[] = 'yyyy';
					break;
				case self::SHORT: // invalid
				case self::TWO_DIGIT:
					$format['year'] = $date->format('y');
					$formatVariant[] = 'yy';
					break;
			}
		}
		if (array_key_exists(self::MONTH, $args)) {
			switch ($args[self::MONTH]) {
				//~ case self::NARROW:
				case self::TWO_DIGIT:
				case self::NUMERIC:
					$formatVariant[] = 'm';
					break;
				case self::SHORT:
					$formatVariant[] = 'mm';
					break;
				case self::LONG:
					$formatVariant[] = 'mmm';
					break;
			}
		}
		if (array_key_exists(self::WEEKDAY, $args)) {
			switch ($args[self::WEEKDAY]) {
				//~ case self::NARROW:
				case self::SHORT:
					$formatVariant[] = 'wd';
					break;
				case self::LONG:
					$formatVariant[] = 'weekday';
					break;
			}
		}
		if (array_key_exists(self::DAY, $args)) {
			switch ($args[self::DAY]) {
				case self::TWO_DIGIT:
				case self::NUMERIC:
				default:
					$formatVariant[] = 'd';
					break;
			}
		}
		if (array_key_exists(self::HOUR, $args)) {
			switch ($args[self::HOUR]) {
				case self::TWO_DIGIT:
				case self::NUMERIC:
				default:
					if (array_key_exists(self::HOUR12, $args) && $args[self::HOUR12]) {
						$format['hour'] = $date->format('g');
						$formatVariant[] = 'h12';
					}
					else {
						$format['hour'] = $date->format('G');
						$formatVariant[] = 'h24';
					}
					break;
			}
		}
		if (array_key_exists(self::MINUTE, $args)) {
			switch ($args[self::MINUTE]) {
				case self::TWO_DIGIT:
				case self::NUMERIC:
				default:
					$formatVariant[] = 'minute';
					break;
			}
		}
		if (array_key_exists(self::SECOND, $args)) {
			switch ($args[self::SECOND]) {
				case self::TWO_DIGIT:
				case self::NUMERIC:
				default:
					$formatVariant[] = 'sec';
					break;
			}
		}

		$formatVariant = implode('-', $formatVariant);
		if (isset($this->formats[$formatVariant])) {
			$formatVariant = $this->formats[$formatVariant];
		}
		elseif (isset($this->formats['default'])) {
			//~ dump(['@' . __method__ . ':' . __line__, $formatVariant, $this->locale ]);
			$format = $defaultFormat;
			$formatVariant = $this->formats['default'];
		}
		else {
			$format = $defaultFormat;
			switch ($formatVariant) {
				case 'yyyy':
					$formatVariant = '{$year}';
					break;
				case 'm':
					$formatVariant = '{$month}';
					break;
				case 'd':
					$formatVariant = '{$day}';
					break;
				case 'h24':
				case 'h12':
					$formatVariant = '{$hour}';
					break;
				case 'minute':
					$formatVariant = '{$minute}';
					break;
				case 'sec':
					$formatVariant = '{$second}';
					break;
				case 'h24-minute-sec':
					$formatVariant = '{$hour}:{$minute}:{$second}';
					break;
				default:
					$formatVariant = '{$day}.{$month}.{$year}';
			}
		}

		$format = self::translateKeys($format);
		return trim(strtr($formatVariant, (array) $format));
	}



	private static function translateKeys(array $xs)
	{
		$ret = [];
		foreach ($xs as $k => $v) {
			$ret['{$' . $k . '}'] = $v;
		}
		return $ret;
	}



	private function resolveMonth($month, $variant)
	{
		$month = (int) $month - 1;
		if (isset($this->names[$variant])) {
			return $this->names[$variant][$month];
		}
		switch ($variant) {
			case 'M':
				return $this->names['monthShortName'][$month];
			case 'F':
			default:
				return $this->names['monthName'][$month];
		}
	}



	private static function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		if ($date instanceof DateTime) {
			return $date;
		}
		$d = DateTime::createFromFormat($format, $date);
		if ($d && $d->format($format) == $date) {
			return $d;
		}
		return False;
	}



	private static function defaultOptions()
	{
		return [
			self::YEAR => self::NUMERIC,
			self::MONTH => self::NUMERIC,
			self::DAY => self::NUMERIC,
		];
	}

}
