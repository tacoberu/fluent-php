<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\FluentIntl;


/**
 * https://www.projectfluent.org/fluent/guide/functions.html
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/NumberFormat
 * https://tc39.es/ecma402/#numberformat-objects
 */
class NumberIntl implements FuncIntl
{
	const CURRENCY_DISPLAY = 'currencyDisplay';
	const MINIMUM_INTEGER_DIGITS = 'minimumIntegerDigits';
	const MINIMUM_FRACTION_DIGITS = 'minimumFractionDigits';
	const MAXIMUM_FRACTION_DIGITS = 'maximumFractionDigits';
	const MINIMUM_SIGNIFICANT_DIGITS = 'minimumSignificantDigits';
	const MAXIMUM_SIGNIFICANT_DIGITS = 'maximumSignificantDigits';


	private $locale;


	function __construct($locale)
	{
		$this->locale = $locale;
	}



	function format($val, array $args)
	{
		$val = sprintf('%01.F', $val);
		$val = rtrim($val, '0');
		list($base, $dec) = explode('.', $val);
		if (array_key_exists(self::MAXIMUM_FRACTION_DIGITS, $args)) {
			if (strlen($dec) < $args[self::MAXIMUM_FRACTION_DIGITS]) {
				if (array_key_exists(self::MINIMUM_FRACTION_DIGITS, $args)) {
					if ($args[self::MINIMUM_FRACTION_DIGITS] > $args[self::MAXIMUM_FRACTION_DIGITS]) {
						$args[self::MINIMUM_FRACTION_DIGITS] = $args[self::MAXIMUM_FRACTION_DIGITS];
					}
				}
			}
			else {
				unset($args[self::MINIMUM_FRACTION_DIGITS]);
			}
		}
		if (array_key_exists(self::MINIMUM_FRACTION_DIGITS, $args)) {
			$dec = str_pad($dec, $args[self::MINIMUM_FRACTION_DIGITS], '0');
		}

		return rtrim("{$base}.{$dec}", '.');
	}

}
