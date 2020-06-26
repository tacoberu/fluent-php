<?php
return [
	'extend' => 'std',
	'names' => [
		'monthName' => ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'],
		'monthOfName' => ['ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'],
		'monthShortName' => ['led', 'úno', 'bře', 'dub', 'kvě', 'črv', 'čvn', 'srp', 'zář', 'říj', 'lis', 'pro'],
		'dayName' => ['pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle'],
		'dayMiddleName' => ['pon', 'úte', 'stř', 'čtv', 'pát', 'sob', 'ned'],
		'dayShortName' => ['po', 'út', 'st', 'čt', 'pá', 'so', 'ne'],
	],
	'formats' => [
		'default' => '{$day}. {$month}. {$year}',
		'yyyy-m-d' => '{$day}. {$month}. {$year}',
		'yyyy-mm-d' => '{$day}. {$monthOfName} {$year}',
		'yyyy-mmm-d' => '{$day}. {$monthOfName} {$year}',
		'yyyy-mmm-weekday-d' => '{$dayName} {$day}. {$monthOfName} {$year}', // "pátek 3. února 2012"
		'yyyy-m' => '{$month}. {$year}',
		'yy-m-d' => '{$day}. {$month}. {$year}',
		'yy-mm-d' => '{$day}. {$monthOfName} {$year}',
		'yy-mmm-d' => '{$day}. {$monthOfName} {$year}',
		'yy-m' => '{$month}. {$year}',
		'm-d' => '{$day}. {$month}.',
		'mm-d' => '{$day}. {$monthOfName}',
		'mmm-d' => '{$day}. {$monthOfName}',
		'd' => '{$day}.',
	],
];
