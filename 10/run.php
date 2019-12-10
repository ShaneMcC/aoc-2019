#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();
	$map = [];
	foreach ($input as $line) { $map[] = str_split($line); }

	// Check if 3 points are all in a line from each other.
	function inLine($fromX, $fromY, $destX, $destY, $x, $y) {
		$dx = $fromX - $destX;
		$dy = $fromY - $destY;
		$pDX = $fromX - $x;
		$pDY = $fromY - $y;

		return atan2($dx, $dy) == atan2($pDX, $pDY);
	}

	// Get all intermediate points between 2 points
	function getPointBetween($fromX, $fromY, $destX, $destY) {
		$points = [];

		foreach (yieldXY(min($fromX, $destX), min($fromY, $destY), max($fromX, $destX), max($fromY, $destY), true) as $x => $y) {
			if ($x == $fromX && $y == $fromY) { continue; }
			if ($x == $destX && $y == $destY) { continue; }

			if (inLine($fromX, $fromY, $destX, $destY, $x, $y)) {
				$points[] = [$x, $y];
			}
		}

		return $points;
	}

	function getVisibleAsteroids($map, $x, $y) {
		$visible = [];

		foreach (yieldXY(0, 0, count($map[0]), count($map), false) as $x2 => $y2) {
			if ($x2 == $x && $y2 == $y) { continue; }

			if ($map[$y2][$x2] != '.') {
				$isVisible = true;

				// Get all the points between these 2.
				$points = getPointBetween($x, $y, $x2, $y2);

				// Check if all of them are blank, if they are then we have
				// visibility between these 2.
				foreach ($points as $p) {
					$isVisible &= ($map[$p[1]][$p[0]] == '.');
				}

				if ($isVisible) {
					$visible[] = [$x2, $y2];
				}
			}
		}

		return $visible;
	}

	$bestX = $bestY = $bestVisible = 0;
	foreach (yieldXY(0, 0, count($map[0]), count($map), false) as $x => $y) {
		if ($map[$y][$x] == '.') { continue; }

		$visible = count(getVisibleAsteroids($map, $x, $y));
		if ($visible > $bestVisible) {
			$bestVisible = $visible;
			$bestX = $x;
			$bestY = $y;
		}
	}

	echo 'Part 1: Best position is [', $bestX, ', ', $bestY, '] with: ', $bestVisible, ' visible.', "\n";

	function getDestroyedPoint($map, $x, $y, $number) {
		$myMap = $map;

		while (true) {
			$points = [];
			// Get all visible asteroids
			$visible = getVisibleAsteroids($myMap, $x, $y);
			if (count($visible) == 0) { return FALSE; }

			foreach ($visible as $p) {
				// atan2 value of the dx/fy from our center point lets us then
				// sort these circularly. (Is that a word?)
				$points[] = [atan2($x - $p[0], $y - $p[1]), [$x - $p[0], $y - $p[1]], $p];
				$myMap[$p[1]][$p[0]] = '.'; // Destroy it.
			}

			if ($number > count($points)) {
				// Skip this loop as we just destroyed the first circle entirely.
				$number -= count($points);
				continue;
			}

			// Sort them into an order around us using the atan2 value from above.
			usort($points, function ($a, $b) {
		 		if ($a[0] == $b[0]) { return 0; }
	    		return ($a[0] > $b[0]) ? -1 : 1;
			});

			// Find the most upright point (atan2 == 0)
			foreach ($points as $k => $v) { if ($v[0] == 0) { break; } }

			$wanted = $points[($k + $number - 1) % count($points)];
			break;
		}

		return $wanted;
	}

	$wanted = getDestroyedPoint($map, $bestX, $bestY, 200);;
	echo 'Part 2: 200th asteroid destroyed is [', $wanted[2][0], ', ', $wanted[2][1], '] value: ', ($wanted[2][0] * 100 + $wanted[2][1]), '.', "\n";
