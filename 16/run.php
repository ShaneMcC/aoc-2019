#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();

	$input = str_split($input);
	$pattern = [0, 1, 0, -1];

	function calculate($input, $pattern) {
		$result = [];

		for ($i = 0; $i < count($input); $i++) {

			// TOOD: Maths.
			$thisPattern = [];
			foreach ($pattern as $bit) { $thisPattern = array_merge($thisPattern, array_fill(0, $i + 1, $bit)); }

			$val = 0;
			for ($j = 0; $j < count($input); $j++) {
				// TODO: Maths.
				$patternBit = $thisPattern[($j + 1) % count($thisPattern)];
				$val += ($input[$j] * $patternBit);
			}
			$result[] = (abs($val) % 10);
		}

		return $result;
	}

	for ($i = 0; $i < 100; $i++) {
		$input = calculate($input, $pattern);
	}

	echo 'Part 1: ', implode('', array_slice($input, 0, 8)), "\n";
