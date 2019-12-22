#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/pathfinder.php');

	$map = [];
	foreach (getInputLines() as $row) { $map[] = str_split($row); }

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
	$map1 = [];
	$map2 = [];
	$map3 = [];
	$map4 = [];

	$maxX = max(array_keys($map[0]));
	$maxY = max(array_keys($map));

	foreach (yieldXY(0, 0, $mid[0], $mid[1], true) as $x => $y) { $map1[$y][$x] = $map[$y][$x]; }
	foreach (yieldXY($mid[0], 0, $maxX, $mid[1], true) as $x => $y) { $map2[$y][$x] = $map[$y][$x]; }
	foreach (yieldXY(0, $mid[1], $mid[0], $maxY, true) as $x => $y) { $map3[$y][$x] = $map[$y][$x]; }
	foreach (yieldXY($mid[0], $mid[1], $maxX, $maxY, true) as $x => $y) { $map4[$y][$x] = $map[$y][$x]; }

	// Find paths and objects for each of the smaller maps:
	$map1Objects = buildObjectPaths($map1);
	$map2Objects = buildObjectPaths($map2);
	$map3Objects = buildObjectPaths($map3);
	$map4Objects = buildObjectPaths($map4);

	// Find the shortest path each sub-map would take, on the assumption that
	// it had all the keys from the other maps (as it would do at some point)
	$map1Path = getPath($map1, $map1Objects, '@', [], array_merge(array_keys($map2Objects), array_keys($map3Objects), array_keys($map4Objects)));
	echo 'Map 1: ', implode('', $map1Path[0]), ' in ', $map1Path[1], "\n";

	$map2Path = getPath($map2, $map2Objects, '@', [], array_merge(array_keys($map1Objects), array_keys($map3Objects), array_keys($map4Objects)));
	echo 'Map 2: ', implode('', $map2Path[0]), ' in ', $map2Path[1], "\n";

	$map3Path = getPath($map3, $map3Objects, '@', [], array_merge(array_keys($map1Objects), array_keys($map2Objects), array_keys($map4Objects)));
	echo 'Map 3: ', implode('', $map3Path[0]), ' in ', $map3Path[1], "\n";

	$map4Path = getPath($map4, $map4Objects, '@', [], array_merge(array_keys($map1Objects), array_keys($map2Objects), array_keys($map3Objects)));
	echo 'Map 4: ', implode('', $map4Path[0]), ' in ', $map4Path[1], "\n";


	// Add them all together, and hope for the best...
	echo 'Part 2: ', ($map1Path[1] + $map2Path[1] + $map3Path[1] + $map4Path[1]), "\n";
