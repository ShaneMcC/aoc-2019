#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/pathfinder.php');
	$input = getInputLines();

	$map = [];
	foreach (getInputLines() as $row) { $map[] = str_split($row); }

	function findPortals($map) {
		$mapOutsideX = [2, count($map[2]) - 1]; // First row of map doesn't have any portals.
		$mapOutsideY = [2, count($map) - 3]; // Last 2 rows are portal IDs.

		$portals = [];
		$teleports = [];
		foreach ($map as $y => $row) {
			foreach ($row as $x => $cell) {
				if (preg_match('#[A-Z]#', $cell)) {

					$portal = $portalCell = null;
					if (isset($map[$y + 1][$x]) && preg_match('#[A-Z]#', $map[$y + 1][$x])) {
						$portal = $cell . $map[$y + 1][$x];

						if (isset($map[$y + 2][$x]) && $map[$y + 2][$x] == '.') {
							$portalCell = [$x, $y + 2];
						} else if (isset($map[$y - 1][$x]) && $map[$y - 1][$x] == '.') {
							$portalCell = [$x, $y - 1];
						}
					} else if (isset($map[$y][$x + 1]) && preg_match('#[A-Z]#', $map[$y][$x + 1])) {
						$portal = $cell . $map[$y][$x + 1];

						if (isset($map[$y][$x + 2]) && $map[$y][$x + 2] == '.') {
							$portalCell = [$x + 2, $y];
						} else if (isset($map[$y][$x - 1]) && $map[$y][$x - 1] == '.') {
							$portalCell = [$x - 1, $y];
						}
					}

					if ($portal != null) {
						if (!isset($portals[$portal])) { $portals[$portal] = []; }
						$portals[$portal][] = $portalCell;

						if (count($portals[$portal]) == 2) {
							$first = $portals[$portal][0];
							$second = $portals[$portal][1];

							$firstLayer = (in_array($first[0], $mapOutsideX) || in_array($first[1], $mapOutsideY)) ? -1 : 1;
							$secondLayer = (in_array($second[0], $mapOutsideX) || in_array($second[1], $mapOutsideY)) ? -1 : 1;

							$teleports[$first[1]][$first[0]] = ['dest' => $second, 'portal' => $portal, 'layer' => $firstLayer];
							$teleports[$second[1]][$second[0]] = ['dest' => $first, 'portal' => $portal, 'layer' => $secondLayer];
						}
					}
				}
			}
		}

		return [$portals, $teleports];
	}

	function getSteps($map, $start, $end, $teleports = []) {
		$pf = new PathFinder($map, $start, $end);

		$pf->setHook('isAccessible', function($state, $x, $y) {
			return $state['grid'][$y][$x] == '.';
		});

		$pf->setHook('getPoints', function ($state) use ($teleports) {
			list($curX, $curY) = $state['current'];
			$layer = isset($state['current'][2]) ? $state['current'][2] : NULL;

			$points = [];
			$points[] = [$curX + 1, $curY];
			$points[] = [$curX, $curY + 1];
			$points[] = [$curX - 1, $curY];
			$points[] = [$curX, $curY - 1];

			// Add layers if we need to.
			if ($layer !== null) {
				$newPoints = [];
				foreach ($points as $p) { $p[2] = $layer;  $newPoints[] = $p; }
				$points = $newPoints;
			}

			// Check for teleports.
			if (isset($teleports[$curY][$curX])) {
				$t = $teleports[$curY][$curX];
				$p = $t['dest'];

				if ($layer === null) {
					// No layers, all portals work.
					$points[] = $p;
				} else {
					$p[2] = $layer + $t['layer'];

					// If we are on layer 0, only portals to layer 1 work.
					if (($layer !== 0 || $p[2] == 1) && $layer < 30) {
						$points[] = $p;
					}
				}
			}

			return $points;
		});

		$pf->setHook('isValidLocation', function ($state, $x, $y) {
			list($curX, $curY) = $state['current'];
			if (!isset($state['grid'][$y][$x])) { return FALSE; } // Ignore Invalid
			return TRUE;
		});

		return $pf->solveMaze();
	}

	[$portals, $teleports] = findPortals($map);

	$start = $portals['AA'][0];
	$end = $portals['ZZ'][0];

	$steps1 = getSteps($map, $start, $end, $teleports);
	$part1 = $steps1[0]['steps'];

	echo 'Part 1: ', $part1, "\n";

	// Set start layers.
	$start[2] = 0;
	$end[2] = 0;

	$steps2 = getSteps($map, $start, $end, $teleports);
	$part2 = $steps2[0]['steps'];

	echo 'Part 2: ', $part2, "\n";
