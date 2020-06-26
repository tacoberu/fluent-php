<?php
return [
	'extend' => 'en-GB',
	'formats' => [
		'default' => '{$month}/{$day}/{$year}',
		'yyyy-mmm-d' => '{$monthName} {$day}, {$year}', // February 2, 2012
		'yyyy-mmm-weekday-d' => '{$dayName}, {$monthName} {$day}, {$year}',// "Thursday, February 2, 2012"
		'yyyy-mm-d' => '{$monthShortName}/{$day}/{$year}',
		'yyyy-m-d' => '{$month}/{$day}/{$year}',
		'yyyy-m' => '{$month}/{$year}',
		'yy-m-d' => '{$month}/{$day}/{$year}',
		'yy-m' => '{$month}/{$year}',
		'm-d' => '{$day}/{$month}.',
		'mmm-d' => '{$day}. {$monthName}',
		'yyyy' => '{$year}',
		'yy' => '{$year}',
		'd' => '{$day}.',
	],
];
