#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/pathfinder.php');

	$map = [];
	foreach (getInputLines() as $row) { $map[] = str_split($row); }

	function removeDeadEnds($map) {
		$maxX = max(array_keys($map[0]));
		$maxY = max(array_keys($map));

		$changed = 0;
		do {
			$changed = 0;

			foreach (yieldXY(0, 0, $maxX, $maxY, true) as $x => $y) {
				if ($map[$y][$x] != '.') { continue; }

				$walls = 0;

				if ($map[$y - 1][$x] == '#') { $walls++; }
				if ($map[$y + 1][$x] == '#') { $walls++; }
				if ($map[$y][$x - 1] == '#') { $walls++; }
				if ($map[$y][$x + 1] == '#') { $walls++; }

				if ($walls == 3) {
					$map[$y][$x] = '#';
					$changed++;
				}
			}

		} while ($changed > 0);

		return $map;
	}

	function getSteps($map, $start, $end) {
		$pf = new PathFinder($map, $start, $end);
		$pf->setHook('isAccessible', function($state, $x, $y) { return $state['grid'][$y][$x] != '#'; });
		$foo = $pf->solveMaze();

		return $foo;
	}

	function getLocations($map) {
		$objects = [];

		foreach ($map as $y => $row) {
			foreach ($row as $x => $cell) {
				if ($cell != '#' && $cell != '.') {
					$objects[$cell] = ['loc' => [$x, $y]];
				}
			}
		}

		asort($objects);
		return $objects;
	}

	function buildObjectPaths($map) {
		// Prepare
		$objects = getLocations($map);

		// Doors
		$doors = [];
		foreach (array_keys($objects) as $o) {
			if (preg_match('#[A-Z]#', $o)) {
				$doors[$o] = $objects[$o];
				unset($objects[$o]);
			}
		}

		foreach (array_keys($objects) as $a) {
			foreach (array_keys($objects) as $b) {
				if ($a == $b) { continue; }
				if (isset($objects[$a]['to'][$b])) { continue; }

				debugOut('Checking ', $a, ' <=> ', $b, "\n");

				$pf = getSteps($map, $objects[$a]['loc'], $objects[$b]['loc']);
				if ($pf[0] == FALSE) {
					$pathInfo = FALSE;
				} else {
					$wantedDoors = [];
					foreach ($pf[0]['previous'] as $step) {
						if (in_array($map[$step[1]][$step[0]], array_keys($doors))) {
							$wantedDoors[] = $map[$step[1]][$step[0]];
						}
					}

					$pathInfo = ['steps' => $pf[0]['steps'], 'doors' => $wantedDoors];
				}

				$objects[$a]['to'][$b] = $pathInfo;
				$objects[$b]['to'][$a] = $pathInfo;
			}
		}

		return $objects;
	}

	function getReachableObjects($map, $objects, $from, $keys) {
		$valid = [];
		foreach ($objects[$from]['to'] as $key => $obj) {
			if ($obj == FALSE) { continue; }
			if (in_array($key, $keys)) { continue; }

			$reachable = true;
			foreach ($obj['doors'] as $d) {
				if (!in_array(strtolower($d), $keys)) {
					$reachable = false;
					break;
				}
			}
			if ($reachable) {
				$valid[$key] = $key;
			}
		}

		return $valid;
	}

	$__KNOWN = [];
	function distanceToCollectKeys($map, $objects, $from, $wanted, $known = []) {
		global 	$__KNOWN;

		$wanted = array_diff($wanted, [$from]);

		if (empty($wanted)) { return [0, $from]; }

		$id = implode('', $wanted) . ','. $from;
		if (isset($__KNOWN[$id])) {
			$__KNOWN[$id]['hit']++;
			return $__KNOWN[$id]['result'];
		}

		$allKnown = array_merge($known, array_keys($objects));
		foreach ($wanted as $w) { $allKnown = array_diff($allKnown, [$w]); }

		$reachable = getReachableObjects($map, $objects, $from, $allKnown);

		$result = PHP_INT_MAX;
		$path = $from;

		$bestResult = PHP_INT_MAX;
		$bestPath = '';
		foreach ($reachable as $key) {
			$dTCK = distanceToCollectKeys($map, $objects, $key, $wanted, $known);
			$d = $objects[$from]['to'][$key]['steps'] + $dTCK[0];
			if ($d < $bestResult) {
				$bestResult = $d;
				$bestPath = $dTCK[1];
			}
		}

		$result = $bestResult;
		$path .= $bestPath;

		$__KNOWN[$id] = ['hit' => 0, 'result' => [$result, $path]];

		return $__KNOWN[$id]['result'];
	}

	$map = removeDeadEnds($map);

	$part1Objects = buildObjectPaths($map);
	$part1 = distanceToCollectKeys($map, $part1Objects, '@', array_keys($part1Objects));
	echo 'Part 1: Took path ', $part1[1], ' in ', $part1[0], ' steps.', "\n";

	// Part 2
	//
	// TODO: This code currently makes an assumption that the best path that
	//       collects all the keys in a sub-map, is the same best-path assuming
	//       you have all the keys from the other sub-maps.
	//
	//       This assumption won't hold true for certain arrangements of
	//       blocking keys, but seems to hold true for all the real inputs I've
	//       looked at so far. (It notably fails for the final test input, we
	//       try "dfeabg" whereas we should be doing "eabdfg" - this is because
	//       the top-right robot shortest path is to collect 'h' first which is
	//       gated behind 'E'. Top-Left absolute-shortest relies on collecting
	//       'e' en-route to/from 'f' instead (which is 2 shorter than doing it
	//       first.)
	//
	//       Other attempts that handle it better are so slow that I'm sticking
	//       with this for now.

	// Split map into 4 smaller maps.
	$mid = getLocations($map)['@']['loc'];

	// Build Cross-of-Walls
	$map[$mid[1]][$mid[0]] = '#';
	$map[$mid[1]][$mid[0] - 1] = '#';
	$map[$mid[1]][$mid[0] + 1] = '#';
	$map[$mid[1] - 1][$mid[0]] = '#';
	$map[$mid[1] + 1][$mid[0]] = '#';

	// Allocate New Start Points
	$startPoints = [];
	$startPoints[] = [$mid[0] - 1, $mid[1] - 1];
	$startPoints[] = [$mid[0] + 1, $mid[1] - 1];
	$startPoints[] = [$mid[0] - 1, $mid[1] + 1];
	$startPoints[] = [$mid[0] + 1, $mid[1] + 1];

	foreach ($startPoints as $sp) { $map[$sp[1]][$sp[0]] = '@'; }

	function floodMap($map, $start) {
		$queue = [$start];
		while (!empty($queue)) {
			[$x, $y] = array_shift($queue);
			$map[$y][$x] = '#';

			if ($map[$y - 1][$x] != '#') { $queue[] = [$x, $y - 1]; }
			if ($map[$y + 1][$x] != '#') { $queue[] = [$x, $y + 1]; }
			if ($map[$y][$x - 1] != '#') { $queue[] = [$x - 1, $y]; }
			if ($map[$y][$x + 1] != '#') { $queue[] = [$x + 1, $y]; }
		}
		return $map;
	}

	function getSubMap($map, $closed) {
		$newMap = $map;
		foreach ($closed as $point) {
			$newMap = floodMap($newMap, $point);
		}

		return [$newMap, buildObjectPaths($newMap)];
	}

	$maps = [];
	$maps[] = getSubMap($map, [$startPoints[1], $startPoints[2], $startPoints[3]]);
	$maps[] = getSubMap($map, [$startPoints[0], $startPoints[2], $startPoints[3]]);
	$maps[] = getSubMap($map, [$startPoints[0], $startPoints[1], $startPoints[3]]);
	$maps[] = getSubMap($map, [$startPoints[0], $startPoints[1], $startPoints[2]]);

	$part2 = 0;
	foreach ($maps as $i => $mapInfo) {
		[$m, $o] = $mapInfo;

		$other = [];
		foreach ($maps as $j => $otherMapInfo) {
			if ($i == $j) { continue; }
			$other = array_merge($other, array_keys($otherMapInfo[1]));
		}

		$__KNOWN = []; // Clear Cache.
		$ans = distanceToCollectKeys($m, $o, '@', array_keys($o), $other);
		echo 'Part 2 - Map ', $i, ': Took path ', $ans[1], ' in ', $ans[0], ' steps.', "\n";
		$part2 += $ans[0];
	}

	echo 'Part 2: ', $part2, "\n";
