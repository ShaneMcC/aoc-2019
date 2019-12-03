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

	$minX = $minY = $maxX = $maxY = 0;

	foreach ($wires as $wireid => $wire) {
		$x = $y = $steps = 0;
		foreach ($wire as $direction) {
			preg_match('#([ULRD])([0-9]+)#', $direction, $m);
			[$direction, $amount] = [$m[1], $m[2]];

			// HORRIBLE BEGINS
			if ($direction == 'U') {
				for ($y2 = $y; $y2 < $y + $amount; $y2++) {
					$minY = min($minY, $y2); $maxY = max($maxY, $y2);
					if (!isset($grid[$y2][$x])) { $grid[$y2][$x] = []; }
					$grid[$y2][$x][] = ['wire' => $wireid, 'direction' => '|', 'steps' => $steps++];
				}
				$y = $y2;
			} else if ($direction == 'D') {
				for ($y2 = $y; $y2 > $y - $amount; $y2--) {
					$minY = min($minY, $y2); $maxY = max($maxY, $y2);
					if (!isset($grid[$y2][$x])) { $grid[$y2][$x] = []; }
					$grid[$y2][$x][] = ['wire' => $wireid, 'direction' => '|', 'steps' => $steps++];
				}
				$y = $y2;
			} else if ($direction == 'R') {
				for ($x2 = $x; $x2 < $x + $amount; $x2++) {
					$minX = min($minX, $x2); $maxX = max($maxX, $x2);
					if (!isset($grid[$y][$x2])) { $grid[$y][$x2] = []; }
					$grid[$y][$x2][] = ['wire' => $wireid, 'direction' => '-', 'steps' => $steps++];
				}
				$x = $x2;
			} else if ($direction == 'L') {
				for ($x2 = $x; $x2 > $x - $amount; $x2--) {
					$minX = min($minX, $x2); $maxX = max($maxX, $x2);
					if (!isset($grid[$y][$x2])) { $grid[$y][$x2] = []; }
					$grid[$y][$x2][] = ['wire' => $wireid, 'direction' => '-', 'steps' => $steps++];
				}
				$x = $x2;
			}
		}
	}

	$part1 = $part2 = PHP_INT_MAX;
	function manhattan($x1, $y1, $x2, $y2) {
		return abs($x1 - $x2) + abs($y1 - $y2);
	}

	foreach ($grid as $y => $g2) {
		foreach ($g2 as $x => $location) {
			if ($x == 0 && $y == 0) { continue; }

			if (count($location) == 1) {
				continue;
			} else if (count($location) == 2) {
				if ($location[0]['direction'] == $location[1]['direction']) {
					continue;
				} else if ($location[0]['wire'] == $location[1]['wire']) {
					continue;
				} else {
					$part1 = min($part1, manhattan(0, 0, $x, $y));
					$stepCount = 0;
					foreach ($location as $w) { $stepCount += $w['steps']; }
					$part2 = min($part2, $stepCount);
				}
			} else {
				die('More than 2 wires crossed.');
			}
		}
	}

	echo 'Part 1: ', $part1, "\n";
	echo 'Part 2: ', $part2, "\n";
