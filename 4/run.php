#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();
	[$start, $end] = explode('-', $input, 2);

	function vaildPassword($password, $part2 = false) {
		$hasDouble = false;
		$skip = false;
		if (!preg_match('#[0-9]{6}#', $password)) { return false; }

		$pw = str_split($password);
		for ($i = 0; $i < count($pw); $i++) {
			if ($i == 0) { continue; }
			if ($skip && $pw[$i] == $pw[$i - 1]) { continue; } else { $skip = false; }

			if ($pw[$i] < $pw[$i - 1]) { return false; }

			if ($pw[$i] == $pw[$i - 1]) {
				if ($part2) {
					if (isset($pw[$i + 1]) && $pw[$i + 1] == $pw[$i]) {
						// This doesn't count as a double, so skip ahead past
						// any of the same number.
						$skip = true;
					} else {
						$hasDouble = true;
					}
				} else {
					$hasDouble = true;
				}
			}
		}

		return $hasDouble;
	}

	$part1 = 0;
	$part2 = 0;

	for ($i = $start; $i <= $end; $i++) {
		if (vaildPassword($i)) { $part1++; }
		if (vaildPassword($i, true)) { $part2++; }
	}

	echo 'Part 1: ', $part1, "\n";
	echo 'Part 2: ', $part2, "\n";
