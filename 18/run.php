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
	function getPath($map, $objects, $from, $visited = [], $otherKeys = [], $count = 0, $max = PHP_INT_MAX) {
		global $__KNOWN;

		$visited[] = $from;

		$id = $visited;
		sort($id);
		$id = implode('', $id) . ',' . $from;
		if (isset($__KNOWN[$id]) && $__KNOWN[$id] < $count) { return [FALSE, FALSE]; }
		$__KNOWN[$id] = $count;

		debugOut(implode('', $visited), "\n");

		$reachable = getReachableObjects($map, $objects, $from, array_merge($visited, $otherKeys));

		foreach (array_keys($reachable) as $r) {
			if (in_array($r, $visited)) { unset($reachable[$r]); }
		}

		// No reachable objects.
		if (empty($reachable)) {
			return [$visited, $count];
		}

		// Try all, find the best.
		$bestCount = PHP_INT_MAX;
		$bestPath = '';
		foreach (array_keys($reachable) as $key) {
			$steps = $count + $objects[$from]['to'][$key]['steps'];
			if ($steps < $max) {
				[$p, $c] = getPath($map, $objects, $key, $visited, $otherKeys, $steps, $bestCount);
				if ($p != FALSE && $c < $bestCount) {
					$bestPath = $p;
					$bestCount = $c;
				}
			}
		}

		return [$bestPath, $bestCount];
	}

	$map = removeDeadEnds($map);

	$part1 = getPath($map, buildObjectPaths($map), '@');
	echo 'Part 1: ', implode('', $part1[0]), ' in ', $part1[1], "\n";

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

	// Split into 4 smaller sub-maps.
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

		// Find the shortest path each sub-map would take, on the assumption that
		// it had all the keys from the other maps (as it would do at some point)
		$mapPath = getPath($m, $o, '@', [], $other);
		echo 'Part 2 - Map ', $i, ': ', implode('', $mapPath[0]), ' in ', $mapPath[1], "\n";
		$part2 += $mapPath[1];
	}

	echo 'Part 2: ', $part2, "\n";
