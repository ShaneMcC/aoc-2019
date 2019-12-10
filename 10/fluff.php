<?php

	function drawMap($asteroids, $outline = false, $redraw = false) {
		global $width, $height;

		$map = [];
		foreach (yieldXY(0, 0, $width, $height, true) as $x => $y) {
			$map[$y][$x] = '.';
			if (isset($asteroids[$y][$x])) {
				if ($asteroids[$y][$x] === true) { $map[$y][$x] = '#'; }
				else if ($asteroids[$y][$x] !== false) { $map[$y][$x] = $asteroids[$y][$x]; }
			}
		}

		if ($redraw) { echo "\033[" . (count($map) + ($outline ? 2 : 0)) . "A"; }

		if ($outline) { echo '┍', str_repeat('━', count($map[0])), '┑', "\n"; }
		foreach ($map as $row) {
			if ($outline) { echo '│'; }
			echo implode('', $row);
			if ($outline) { echo '│'; }
			echo "\n";
		}
		if ($outline) { echo '┕', str_repeat('━', count($map[0])), '┙', "\n"; }
	}

	function drawLaserShow($asteroids, $bestX, $bestY, $destroyedPoints) {
		echo "\n\n";
		$remainingAsteroids = $asteroids;

		$remainingAsteroids[$bestY][$bestX] = "\033[0;32m" . '#' . "\033[0m";

		drawMap($remainingAsteroids, true);
		foreach ($destroyedPoints as $point) {
			[$x, $y] = $point[1];
			$remainingAsteroids[$y][$x] = "\033[1;31m" . '#' . "\033[0m";
			drawMap($remainingAsteroids, true, true);
			unset($remainingAsteroids[$y][$x]);

			usleep(20000);
			drawMap($remainingAsteroids, true, true);
			usleep(20000);
		}
	}

