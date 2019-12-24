#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$initialMap = [0 => []];
	foreach ($input as $row) { $initialMap[0][] = str_split($row); }

	function addLayer($map, $layerID) {
		foreach (yieldXY(0, 0, 4, 4, true) as $x => $y) {
			$map[$layerID][$y][$x] = '.';
		}
		$map[$layerID][2][2] = '?';
		return $map;
	}

	function hasBugs($mapLayer) {
		foreach ($mapLayer as $y => $row) {
			foreach ($row as $x => $cell) {
				if ($cell == '#') { return true; }
			}
		}

		return false;
	}

	function simulate($map) {
		$newMap = $map;

		$hasLayers = count($map) > 1;

		$outer = min(array_keys($map));
		$inner = max(array_keys($map));

		foreach ($map as $layer => $layerMap) {
			foreach ($layerMap as $y => $row) {
				foreach ($row as $x => $cell) {
					$adjacentBugs = 0;
					if ($cell == '?') { continue; }

					$checkCells = [];

					// Check immediately adjacent
					$checkCells[] = [$layer, $x, $y - 1]; // UP
					$checkCells[] = [$layer, $x, $y + 1]; // DOWN
					$checkCells[] = [$layer, $x - 1, $y]; // LEFT
					$checkCells[] = [$layer, $x + 1, $y]; // RIGHT

					if ($hasLayers) {
						// Check outer layer if appropriate
						if ($x == 0) {
							$checkCells[] = [$layer - 1, 1, 2];
						}
						if ($y == 0) {
							$checkCells[] = [$layer - 1, 2, 1];
						}
						if ($x == 4) {
							$checkCells[] = [$layer - 1, 3, 2];
						}
						if ($y == 4) {
							$checkCells[] = [$layer - 1, 2, 3];
						}

						// Check inner layer.
						if ($x == 2 && $y == 1) {
							for ($c = 0; $c < 5; $c++) {
								$checkCells[] = [$layer + 1, $c, 0];
							}
						}
						if ($x == 1 && $y == 2) {
							for ($c = 0; $c < 5; $c++) {
								$checkCells[] = [$layer + 1, 0, $c];
							}
						}
						if ($x == 2 && $y == 3) {
							for ($c = 0; $c < 5; $c++) {
								$checkCells[] = [$layer + 1, $c, 4];
							}
						}
						if ($x == 3 && $y == 2) {
							for ($c = 0; $c < 5; $c++) {
								$checkCells[] = [$layer + 1, 4, $c];
							}
						}
					}

					foreach ($checkCells as $c) {
						[$cL, $cX, $cY] = $c;
						if (isset($map[$cL][$cY][$cX]) && $map[$cL][$cY][$cX] == '#') { $adjacentBugs++; }
					}

					if ($cell == '#') {
						$newMap[$layer][$y][$x] = ($adjacentBugs == 1) ? '#' : '.';
					} else if ($cell == '.') {
						$newMap[$layer][$y][$x] = ($adjacentBugs == 1 || $adjacentBugs == 2) ? '#' : '.';
					}
				}
			}
		}

		if ($hasLayers) {
			if (hasBugs($newMap[$outer])) {
				$newMap = addLayer($newMap, $outer - 1);
			}
			if (hasBugs($newMap[$inner])) {
				$newMap = addLayer($newMap, $inner + 1);
			}
		}

		return $newMap;
	}

	function getBiodiversity($map) {
		$score = 0;

		$calc = 1;
		foreach ($map as $y => $row) {
			foreach ($row as $x => $cell) {
				if ($cell == '#') {
					$score += $calc;
				}

				$calc = $calc + $calc;
			}
		}

		return $score;
	}

	function countBugs($map) {
		$bugCount = 0;
		foreach ($map as $layer => $m) {
			foreach ($m as $row) {
				$acv = array_count_values($row);
				$bugCount += isset($acv['#']) ? $acv['#'] : 0;
			}
		}

		return $bugCount;
	}

	$map = $initialMap;

	$seenLayouts = [];
	$id = json_encode($map[0]);
	$layouts[$id] = true;
	while (true) {
		$map = simulate($map);
		$id = json_encode($map[0]);
		if (isset($layouts[$id])) {
			echo 'Part 1: ', getBiodiversity($map[0]), "\n";
			break;
		}
		$layouts[$id] = true;
	}

	// Uh Oh, Plutonians!
	$map = $initialMap;
	$map[0][2][2] = '?';
	$map = addLayer($map, 1);
	$map = addLayer($map, -1);

	for ($i = 0; $i < 200; $i++) {
		$map = simulate($map);
	}

	$bugCount = countBugs($map);

	echo 'Part 2: ', $bugCount, "\n";
