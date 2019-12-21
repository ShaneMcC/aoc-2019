#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/pathfinder.php');
	$input = getInputLines();

	$map = [];
	foreach (getInputLines() as $row) { $map[] = str_split($row); }

	function findPortals($map) {
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

							$teleports[$first[1]][$first[0]] = $second;
							$teleports[$second[1]][$second[0]] = $first;
						}
					}
				}
			}
		}

		return [$portals, $teleports];
	}

	function drawMap($grid, $redraw = false) {
		$height = count($grid) + 2;
		$width = count($grid[2]) + 2;
		if ($redraw) { echo "\033[" . $height . "A"; }

		echo '┍', str_repeat('━', $width), '┑', "\n";
		foreach ($grid as $row) { echo '│', sprintf('%-' . $width . 's', implode('', $row)), '│', "\n"; }
		echo '┕', str_repeat('━', $width), '┙', "\n";
	}

	function getSteps($map, $start, $end, $teleports = []) {
		$pf = new PathFinder($map, $start, $end);

		$pf->setHook('isAccessible', function($state, $x, $y) {
			return $state['grid'][$y][$x] == '.';
		});

		$pf->setHook('getPoints', function ($state) use ($teleports) {
			list($curX, $curY) = $state['current'];

			$points = [];
			$points[] = [$curX + 1, $curY];
			$points[] = [$curX, $curY + 1];
			$points[] = [$curX - 1, $curY];
			$points[] = [$curX, $curY - 1];
			if (isset($teleports[$curY][$curX])) { $points[] = $teleports[$curY][$curX]; }

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

	$steps = getSteps($map, $portals['AA'][0], $portals['ZZ'][0], $teleports);
	$part1 = $steps[0]['steps'];

	echo 'Part 1: ', $part1, "\n";

