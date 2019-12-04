#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();
	[$start, $end] = explode('-', $input, 2);

	function vaildPassword($password) {
		$hasDouble = false;
		if (!preg_match('#[0-9]{6}#', $password)) { return false; }

		$last = -1;
		foreach (str_split($password) as $i) {
			if ($i < $last) { return false; }
			if ($last == $i) { $hasDouble = true; }

			$last = $i;
		}

		return $hasDouble;
	}

	$part1 = 0;

	for ($i = $start; $i <= $end; $i++) {
		if (vaildPassword($i)) {
			$part1++;
		}
	}

	echo 'Part 1: ', $part1, "\n";
