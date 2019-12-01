#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$part1 = 0;
	$part2 = 0;

	function getFuelCost($mass) { return max(0, floor($mass / 3) - 2); }

	foreach ($input as $mod) {
		$part1 += getFuelCost($mod);

		$fuelCost = $mod;
		while (true) {
			$fuelCost = getFuelCost($fuelCost);
			if ($fuelCost <= 0) { break; }
			$part2 += $fuelCost;
		}
	}

	echo 'Part 1: ', $part1, "\n";
	echo 'Part 2: ', $part2, "\n";
