#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();

	$start = str_split($input);
	$pattern = [0, 1, 0, -1];

	function calculate($input, $pattern) {
		$result = [];

		for ($i = 0; $i < count($input); $i++) {
			$val = 0;
			for ($j = $i; $j < count($input); $j++) {
				$patternLen = count($pattern) * ($i + 1);
				$patternPos = (($j + 1) % $patternLen);
				$patternBit = $pattern[floor($patternPos / ($patternLen / count($pattern)))];
				$val += ($input[$j] * $patternBit);
			}
			$result[] = (abs($val) % 10);
		}

		return $result;
	}


	$input = $start;
	for ($i = 0; $i < 100; $i++) {
		$input = calculate($input, $pattern, true);
	}
	echo 'Part 1: ', implode('', array_slice($input, 0, 8)), "\n";
