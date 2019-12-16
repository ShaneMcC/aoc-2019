#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$start = getInputLine();

	function calculate($input) {
		$result = [];
		$pattern = [0, 1, 0, -1];

		for ($i = 0; $i < count($input); $i++) {
			$val = 0;
			for ($j = $i; $j < count($input); $j++) {
				$patternBit = $pattern[(($j + 1) / ($i + 1)) % 4];
				$val += ($input[$j] * $patternBit);
			}
			$result[] = (abs($val) % 10);
		}

		return $result;
	}

	function doPart1($start) {
		$input = str_split($start);
		for ($i = 0; $i < 100; $i++) {
			$input = calculate($input);
		}

		return implode('', array_slice($input, 0, 8));
	}

	$part1 = doPart1($start);
	echo 'Part 1: ', $part1, "\n";

	// Based on: https://www.reddit.com/r/adventofcode/comments/ebai4g/2019_day_16_solutions/
	//
	// There is some magic here, fucked if I know what.
	function doPart2($start) {
		$input = str_split(str_repeat($start, 10000));
		$skip = (int)substr($start, 0, 7);

		for ($i = 0; $i < 100; $i++) {
		    $checksum = array_sum(array_slice($input, $skip));

		    $newInput = array_fill(0, $skip, 0);
			$newInput[] = (abs($checksum) % 10);

			for ($j = $skip + 1; $j < count($input); $j++) {
	        	$checksum -= $input[$j - 1];
	        	$newInput[] = (abs($checksum) % 10);
	    	}
    		$input = $newInput;
    	}

    	return implode('', array_slice($input, $skip, 8));
    }

	$part2 = doPart2($start);
	echo 'Part 2: ', $part2, "\n";
