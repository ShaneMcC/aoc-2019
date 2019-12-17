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

	// Something like:
	//  - Because "the inputs in the pattern are repeated <digit number> of times"
	//    everything before the current <digit number> will always be 0, so we
	//    only need to worry about digits after it.
	//
	//  - Because we are offset into the latter half of the puzzle, we end up
	//    only actually ever having [0, 0, 0, 0, ... 1, 1, ... 1, 1] for the
	//    pattern, so our values are essentially just a sum() operation on the
	//    remaining digits after us.
	//
	//  - Because of the % 10, it's quicker to do this backwards, the last digit
	//    never changes, and each digit prior to that is just the sum of the
	//    same digit in the previous step, plus the digit in front of us, % 10
	//
	function doPart2($start) {
		$input = str_split(str_repeat($start, 10000));
		$offset = (int)substr($start, 0, 7);

		$size = count($input);
		if ($offset < ($size / 2)) { die('Offset is too early.'); }

		// Loop through 100 times.
		for ($j = 0; $j < 100; $j++) {
			// Now loop through and calculate the new values.
			for ($i = $size - 2; $i != $offset-1; $i--) {
				$input[$i] = ($input[$i] + $input[$i + 1]) % 10;
			}
		}

		return implode('', array_slice($input, $offset, 8));
	}

	$part2 = doPart2($start);
	echo 'Part 2: ', $part2, "\n";
