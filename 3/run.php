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
		$x = $y = 0;
		foreach ($wire as $direction) {
			preg_match('#([ULRD])([0-9]+)#', $direction, $m);
			[$direction, $amount] = [$m[1], $m[2]];

			// HORRIBLE BEGINS
			if ($direction == 'U') {
				for ($y2 = $y; $y2 < $y + $amount; $y2++) {
					$minY = min($minY, $y2); $maxY = max($maxY, $y2);
					if (!isset($grid[$y2][$x])) { $grid[$y2][$x] = []; }
					$grid[$y2][$x][] = ['wire' => $wireid, 'direction' => '|'];
				}
				$y = $y2;
			} else if ($direction == 'D') {
				for ($y2 = $y; $y2 > $y - $amount; $y2--) {
					$minY = min($minY, $y2); $maxY = max($maxY, $y2);
					if (!isset($grid[$y2][$x])) { $grid[$y2][$x] = []; }
					$grid[$y2][$x][] = ['wire' => $wireid, 'direction' => '|'];
				}
				$y = $y2;
			} else if ($direction == 'R') {
				for ($x2 = $x; $x2 < $x + $amount; $x2++) {
					$minX = min($minX, $x2); $maxX = max($maxX, $x2);
					if (!isset($grid[$y][$x2])) { $grid[$y][$x2] = []; }
					$grid[$y][$x2][] = ['wire' => $wireid, 'direction' => '-'];
				}
				$x = $x2;
			} else if ($direction == 'L') {
				for ($x2 = $x; $x2 > $x - $amount; $x2--) {
					$minX = min($minX, $x2); $maxX = max($maxX, $x2);
					if (!isset($grid[$y][$x2])) { $grid[$y][$x2] = []; }
					$grid[$y][$x2][] = ['wire' => $wireid, 'direction' => '-'];
				}
				$x = $x2;
			}
		}
	}

	// Border
	$maxY++; $minY--; $maxX++; $minX--;

	$manhattan = PHP_INT_MAX;
	function manhattan($x1, $y1, $x2, $y2) {
		return abs($x1 - $x2) + abs($y1 - $y2);
	}

	for ($y = $maxY; $y > $minY; $y--) {
		for ($x = $minX; $x < $maxX; $x++) {
			if ($y == 0 && $x == 0) {
				continue;
			} else if (!isset($grid[$y][$x])) {
				continue;
			} else {
				if (count($grid[$y][$x]) == 1) {
					continue;
				} else if (count($grid[$y][$x]) == 2) {
					if ($grid[$y][$x][0]['direction'] == $grid[$y][$x][1]['direction']) {
						continue;
					} else {
						$manhattan = min($manhattan, manhattan(0, 0, $x, $y));
					}
				} else {
					die('bah');
				}
			}
		}
	}

	echo 'Part 1: ', $manhattan, "\n";
