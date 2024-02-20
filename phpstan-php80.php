<?php declare(strict_types = 1);

return PHP_MINOR_VERSION > 0
	? []
	: [
		'parameters' => [
			'excludePaths' => [
				'analyseAndScan' => [
					__DIR__ . '/tests/src/NetteTest.php',
				],
			],
		],
	];
