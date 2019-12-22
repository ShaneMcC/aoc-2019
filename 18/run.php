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

		if (empty($wanted)) { return 0; }

		$id = implode('', $wanted) . ','. $from;
		if (isset($__KNOWN[$id])) {
			$__KNOWN[$id]['hit']++;
			return $__KNOWN[$id]['result'];
		}

		$result = PHP_INT_MAX;

		$allKnown = array_merge($known, array_keys($objects));
		foreach ($wanted as $w) { $allKnown = array_diff($allKnown, [$w]); }

		$reachable = getReachableObjects($map, $objects, $from, $allKnown);

		foreach ($reachable as $key) {
			$d = $objects[$from]['to'][$key]['steps'] + distanceToCollectKeys($map, $objects, $key, $wanted, $known);
			$result = min($result, $d);
		}

		$__KNOWN[$id] = ['hit' => 0, 'result' => $result];
		return $result;
	}

	$map = removeDeadEnds($map);

	$part1Objects = buildObjectPaths($map);
	$part1 = distanceToCollectKeys($map, $part1Objects, '@', array_keys($part1Objects));
	echo 'Part 1: ', $part1, "\n";

	// Part 2

	// Split map into 4 smaller maps.
	$mid = getLocations($map)['@']['loc'];

	// Build Cross-of-Walls
	$map[$mid[1]][$mid[0]] = '#';
	$map[$mid[1]][$mid[0] - 1] = '#';
	$map[$mid[1]][$mid[0] + 1] = '#';
	$map[$mid[1] - 1][$mid[0]] = '#';
	$map[$mid[1] + 1][$mid[0]] = '#';

	// Allocate New Start Points
	$map[$mid[1] - 1][$mid[0] - 1] = '@';
	$map[$mid[1] - 1][$mid[0] + 1] = '@';
	$map[$mid[1] + 1][$mid[0] - 1] = '@';
	$map[$mid[1] + 1][$mid[0] + 1] = '@';

	function getSubMap($map, $minX, $minY, $maxX, $maxY) {
		$newMap = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$newMap[$y][$x] = $map[$y][$x];
		}

		return [$newMap, buildObjectPaths($newMap)];
	}

	$maxX = max(array_keys($map[0]));
	$maxY = max(array_keys($map));

	$maps = [];
	$maps[] = getSubMap($map, 0, 0, $mid[0], $mid[1]);
	$maps[] = getSubMap($map, $mid[0], 0, $maxX, $mid[1]);
	$maps[] = getSubMap($map, 0, $mid[1], $mid[0], $maxY);
	$maps[] = getSubMap($map, $mid[0], $mid[1], $maxX, $maxY);

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
		echo 'Part 2 - Map ', $i, ': ', $ans, "\n";
		$part2 += $ans;
	}

	echo 'Part 2: ', $part2, "\n";
