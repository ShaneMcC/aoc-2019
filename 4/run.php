#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();
	[$start, $end] = explode('-', $input, 2);

	function vaildPassword($password) {
		$validPart1 = false;
		$validPart2 = false;
		$next = $password + 1;

		$skipAhead = false;
		if (!preg_match('#[0-9]{6}#', $password)) { return [false, false, false]; }

		$pw = str_split($password);
		for ($i = 0; $i < count($pw); $i++) {
			 // Ignore first because we can't look behind.
			if ($i == 0) { continue; }
			// Skip ahead if needed
			if ($skipAhead && $pw[$i] == $pw[$i - 1]) { continue; } else { $skipAhead = false; }
			// If we're lower than the previous, fail.
			if ($pw[$i] < $pw[$i - 1]) {
				// Skip ahead to the next possible password.
				$next = substr($password, 0, $i) . $pw[$i - 1] . str_repeat(0, strlen($password) - $i - 1);
				return [false, false, $next];
			}

			// Are we the same as previous?
			if ($pw[$i] == $pw[$i - 1]) {
				$validPart1 = true;
				if (isset($pw[$i + 1]) && $pw[$i + 1] == $pw[$i]) {
					// This doesn't count as a double for part 2 because there
					// are more than 2 in a row of the same number, so just
					// skip ahead past any of the same number and only count
					// as valid for part 1.
					$skipAhead = true;
				} else {
					$validPart2 = true;
				}
			}
		}

		return [$validPart1, $validPart2, $next];
	}

	$part1 = 0;
	$part2 = 0;

	for ($i = $start; $i <= $end; $i++) {
		[$validPart1, $validPart2, $next] = vaildPassword($i);
		if (!$validPart1 && !$validPart2) { $i = $next - 1; }

		if ($validPart1) { $part1++; }
		if ($validPart2) { $part2++; }
	}

	echo 'Part 1: ', $part1, "\n";
	echo 'Part 2: ', $part2, "\n";
