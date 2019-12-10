#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();
	$asteroids = [];
	$y = 0;
	foreach ($input as $line) {
		$x = 0;
		foreach (str_split($line) as $cell) {
			if ($cell == '#') { $asteroids[$y][$x] = true; }
			$x++;
		}
		$y++;
	}

	function getAngle($fromX, $fromY, $destX, $destY) {
		$dx = $fromX - $destX;
		$dy = $fromY - $destY;

		return atan2($dx, $dy);
	}

	function getVisibleAsteroids($asteroids, $x, $y) {
		$visible = [];

		$angles = [];

		foreach ($asteroids as $y2 => $row) {
			foreach (array_keys($row) as $x2) {
				if ($y2 == $y && $x2 == $x) { continue; }
				$angle = ''.getAngle($x, $y, $x2, $y2);

				if (!isset($angles[$angle])) {
					$angles[$angle] = [$x2, $y2];
				} else {
					[$aX, $aY] = $angles[$angle];
					if (manhattan($x, $y, $x2, $y2) < manhattan($x, $y, $aX, $aY)) {
						$angles[$angle] = [$x2, $y2];
					}
				}
			}
		}

		return array_values($angles);
	}

	$bestX = $bestY = $bestVisible = 0;
	foreach ($asteroids as $y => $row) {
		foreach (array_keys($row) as $x) {

			$visible = count(getVisibleAsteroids($asteroids, $x, $y));
			if ($visible > $bestVisible) {
				$bestVisible = $visible;
				$bestX = $x;
				$bestY = $y;
			}
		}
	}

	echo 'Part 1: Best position is [', $bestX, ', ', $bestY, '] with: ', $bestVisible, ' visible.', "\n";

	function getDestroyedPoint($asteroids, $x, $y, $number) {
		$myAsteroids = $asteroids;

		while (true) {
			$points = [];
			// Get all visible asteroids
			$visible = getVisibleAsteroids($myAsteroids, $x, $y);
			if (count($visible) == 0) { return FALSE; }

			foreach ($visible as $p) {
				// atan2 value of the dx/fy from our center point lets us then
				// sort these circularly. (Is that a word?)
				$points[] = [getAngle($x, $y, $p[0], $p[1]), $p];
				unset($myAsteroids[$p[1]][$p[0]]);
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

	$wanted = getDestroyedPoint($asteroids, $bestX, $bestY, 200);
	echo 'Part 2: 200th asteroid destroyed is [', $wanted[1][0], ', ', $wanted[1][1], '] value: ', ($wanted[1][0] * 100 + $wanted[1][1]), '.', "\n";
