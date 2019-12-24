#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');

	$initialMap = [0 => getInputMap()];

	// Add a nice new empty layer to our map.
	function addLayer($map, $layerID) {
		foreach (yieldXY(0, 0, 4, 4, true) as $x => $y) {
			$map[$layerID][$y][$x] = '.';
		}
		$map[$layerID][2][2] = '?';
		return $map;
	}

	// Check if a given layer has any bugs.
	function hasBugs($map, $layer) {
		if (!isset($map[$layer][0][0])) { return FALSE; }

		foreach ($map[$layer] as $y => $row) {
			foreach ($row as $x => $cell) {
				if ($cell == '#') { return true; }
			}
		}

		return false;
	}

	function simulate($map) {
		$newMap = $map;

		$hasLayers = count($map) > 1;

		foreach ($map as $layer => $layerMap) {
			foreach (cells($layerMap) as [$x, $y, $cell]) {
				if ($cell == '?') { continue; } // Ignore magic inner space.

				// Cells to check for bugs.
				$checkCells = [];

				// Check immediately adjacent
				$checkCells[] = [$layer, $x, $y - 1]; // UP
				$checkCells[] = [$layer, $x, $y + 1]; // DOWN
				$checkCells[] = [$layer, $x - 1, $y]; // LEFT
				$checkCells[] = [$layer, $x + 1, $y]; // RIGHT

				if ($hasLayers) {
					// Check outer layer if appropriate
					if ($x == 0) { $checkCells[] = [$layer - 1, 1, 2]; } // OUTER LEFT-MIDDLE
					if ($y == 0) { $checkCells[] = [$layer - 1, 2, 1]; } // OUTER UP-MIDDLE
					if ($x == 4) { $checkCells[] = [$layer - 1, 3, 2]; } // OUTER RIGHT-MIDDLE
					if ($y == 4) { $checkCells[] = [$layer - 1, 2, 3]; } // OUTER DOWN-MIDDLE

					// Check inner layer.
					if ($x == 2 && $y == 1) {
						for ($c = 0; $c < 5; $c++) { $checkCells[] = [$layer + 1, $c, 0]; } // INNER TOP-ROW
					}
					if ($x == 1 && $y == 2) {
						for ($c = 0; $c < 5; $c++) { $checkCells[] = [$layer + 1, 0, $c]; } // INNER LEFT-COLUMN
					}
					if ($x == 2 && $y == 3) {
						for ($c = 0; $c < 5; $c++) { $checkCells[] = [$layer + 1, $c, 4]; }  // INNER BOTTOM-ROW
					}
					if ($x == 3 && $y == 2) {
						for ($c = 0; $c < 5; $c++) { $checkCells[] = [$layer + 1, 4, $c]; }  // INNER RIGHT-COLUMN
					}
				}

				// How many adjacent bugs do we have?
				$adjacentBugs = 0;
				foreach ($checkCells as $c) {
					[$cL, $cX, $cY] = $c;
					if (isset($map[$cL][$cY][$cX]) && $map[$cL][$cY][$cX] == '#') { $adjacentBugs++; }
				}

				if ($cell == '#') {
					// A bug dies (becoming an empty space) unless there
					// is exactly one bug adjacent to it.
					$newMap[$layer][$y][$x] = ($adjacentBugs == 1) ? '#' : '.';
				} else if ($cell == '.') {
					// An empty space becomes infested with a bug if
					// exactly one or two bugs are adjacent to it.
					$newMap[$layer][$y][$x] = ($adjacentBugs == 1 || $adjacentBugs == 2) ? '#' : '.';
				}
			}
		}

		if ($hasLayers) {
			// If our current known inner or outer layers have bugs on them,
			// then we should track some new outer/inner layers in case they
			// start to become infested.
			$outerLayer = min(array_keys($map));
			$innerLayer = max(array_keys($map));

			if (hasBugs($newMap, $outerLayer)) { $newMap = addLayer($newMap, $outerLayer - 1); }
			if (hasBugs($newMap, $innerLayer)) { $newMap = addLayer($newMap, $innerLayer + 1); }
		}

		return $newMap;
	}

	function getBiodiversity($map) {
		$score = 0;
		$calc = 1;
		foreach (cells($map) as [$x, $y, $cell]) {
			if ($cell == '#') {
				$score += $calc;
			}

			$calc = $calc + $calc;
		}

		return $score;
	}

	function countBugs($map) {
		$bugCount = 0;
		foreach ($map as $layer => $m) {
			foreach (cells($m) as [$x, $y, $cell]) {
				if ($cell == '#') { $bugCount++; }
			}
		}

		return $bugCount;
	}

	$map = $initialMap;

	$layouts = [getBiodiversity($map[0]) => true];
	while (true) {
		$map = simulate($map);
		$biodiversity = getBiodiversity($map[0]);
		if (isset($layouts[$biodiversity])) {
			echo 'Part 1: ', $biodiversity, "\n";
			break;
		}
		$layouts[$biodiversity] = true;
	}

	// Uh Oh, Plutonians!
	$map = $initialMap;
	$map[0][2][2] = '?';
	$map = addLayer($map, 1);
	$map = addLayer($map, -1);

	for ($i = 0; $i < 200; $i++) { $map = simulate($map); }
	$bugCount = countBugs($map);

	echo 'Part 2: ', $bugCount, "\n";
