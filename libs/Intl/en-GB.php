<?php
return [
	'extend' => 'std',
	'formats' => [
		'default' => '{$0day}/{$0month}/{$year}',
		'yyyy-m-d' => '{$0day}/{$0month}/{$year}',
		'yyyy-m' => '{$0month}/{$year}',
		'yy-m-d' => '{$0day}/{$0month/{$year}',
		'yy-m' => '{$0month}/{$year}',
		'm-d' => '{$0day}/{$0month}',
	],
];
