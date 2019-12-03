#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();
	$wires = [];
	foreach ($input as $wire) {
		$bits = explode(',', $wire);
		$wires[] = $bits;
	}

	$grid = [];

	$generators = ['U' => function($startX, $startY, $amount) { for ($y = $startY; $y <= $startY + $amount; $y++) { yield [$startX, $y, '|']; } },
	               'D' => function($startX, $startY, $amount) { for ($y = $startY; $y >= $startY - $amount; $y--) { yield [$startX, $y, '|']; } },
	               'R' => function($startX, $startY, $amount) { for ($x = $startX; $x <= $startX + $amount; $x++) { yield [$x, $startY, '-']; } },
	               'L' => function($startX, $startY, $amount) { for ($x = $startX; $x >= $startX - $amount; $x--) { yield [$x, $startY, '-']; } },
	              ];

	foreach ($wires as $wireid => $wire) {
		$x = $y = 0;
		$steps = 1; // Count 0, 0
		foreach ($wire as $direction) {
			preg_match('#([ULRD])([0-9]+)#', $direction, $m);
			[$direction, $amount] = [$m[1], $m[2]];

			$first = true;
			foreach ($generators[$direction]($x, $y, $amount) as $value) {
				if ($first) { $first = false; continue; }
				[$x, $y, $d] = $value;

				if (!isset($grid[$y][$x])) { $grid[$y][$x] = []; }
				$grid[$y][$x][] = ['wire' => $wireid, 'direction' => $d, 'steps' => $steps++];
			}
		}
	}

	$part1 = $part2 = PHP_INT_MAX;

	foreach ($grid as $y => $g2) {
		foreach ($g2 as $x => $location) {
			if (count($location) == 2) {
				if ($location[0]['direction'] == $location[1]['direction'] || $location[0]['wire'] == $location[1]['wire']) {
					continue;
				} else {
					$part1 = min($part1, manhattan(0, 0, $x, $y));
					$stepCount = 0;
					foreach ($location as $w) { $stepCount += $w['steps']; }
					$part2 = min($part2, $stepCount);
				}
			} else if (count($location) > 2) {
				die('More than 2 wires crossed.');
			}
		}
	}

	echo 'Part 1: ', $part1, "\n";
	echo 'Part 2: ', $part2, "\n";
