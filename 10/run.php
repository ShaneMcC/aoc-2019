#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();
	$map = [];
	foreach ($input as $line) { $map[] = str_split($line); }

	// I have no idea if this is correct.
	// It does stuff and decides if a point is in line or not and seems to work
	// but I suck at maths and don't fully know what I've coded here. :D
	//
	// Something like if the differences between our source and dest points and
	// source and mid-points are the same, they're probably in line?
	function inLine($fromX, $fromY, $destX, $destY, $x, $y) {
		// Avoid div/0 errors.
		if ($fromX == $destX) { return ($fromX == $x); }
		if ($fromY == $destY) { return ($fromY == $y); }
		if ($fromX == $x) { return ($fromX == $destX); }
		if ($fromY == $y) { return ($fromY == $destY); }

		$dx = $fromX - $destX;
		$dy = $fromY - $destY;
		$pDX = $fromX - $x;
		$pDY = $fromY - $y;

		return (abs($dx/$dy) == abs($pDX/$pDY)) && (abs($dy/$dx) == abs($pDY/$pDX));
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
