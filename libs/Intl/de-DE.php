<?php
return [
	'extend' => 'std',
	'formats' => [
		'default' => '{$day}.{$month}.{$year}',
		'yyyy-m-d' => '{$day}.{$month}.{$year}',
		'yyyy-m' => '{$month}.{$year}',
		'yy-m-d' => '{$day}.{$month/{$year}',
		'yy-m' => '{$month}.{$year}',
		'm-d' => '{$day}.{$0month}',
	],
];
