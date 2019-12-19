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
	function getPath($map, $objects, $fromList, $visited = [], $count = 0, $max = PHP_INT_MAX) {
		global $__KNOWN;

		if (!is_array($fromList)) { $fromList = [$fromList]; }
		$visited = array_merge($visited, $fromList);
		$visited = array_unique($visited);

		$id = $visited;
		sort($id);
		$iFrom = $fromList;
		sort($iFrom);
		$id = implode('', $id) . ',' . implode('', $iFrom);
		if (isset($__KNOWN[$id]) && $__KNOWN[$id] < $count) { return [FALSE, FALSE]; }
		$__KNOWN[$id] = $count;

		$reachable = [];
		$rcount = 0;

		foreach ($fromList as $f) {
			$r = getReachableObjects($map, $objects, $f, $visited);
			$reachable[] = [$f, $r];
			$rcount += count($r);
		}

		debugOut(implode('', $visited),' => ', implode('', $fromList), "\n");

		// No reachable objects.
		if ($rcount == 0) { return [$visited, $count]; }

		// Try all, find the best.
		$bestCount = PHP_INT_MAX;
		$bestPath = '';

		foreach ($reachable as $fk => $reach) {
			$from = $fromList;
			foreach (array_keys($reach[1]) as $key) {
				$from[$fk] = $key;
				$steps = $count + $objects[$reach[0]]['to'][$key]['steps'];
				if ($steps < $max) {
					[$p, $c] = getPath($map, $objects, $from, $visited, $steps, $bestCount);
					if ($p != FALSE && $c < $bestCount) {
						$bestPath = $p;
						$bestCount = $c;
					}
				}
			}
		}

		return [$bestPath, $bestCount];
	}

//	$part1 = getPath($map, buildObjectPaths($map), '@');
//	if (isDebug()) { var_dump($part1); }
//	echo 'Part 1: ', implode('', $part1[0]), ' in ', $part1[1], "\n";

	// Part 2

	# Fix map.
	$mid = getLocations($map)['@']['loc'];

	// Build Walls
	$map[$mid[1]][$mid[0]] = '#';
	$map[$mid[1]][$mid[0] - 1] = '#';
	$map[$mid[1]][$mid[0] + 1] = '#';
	$map[$mid[1] - 1][$mid[0]] = '#';
	$map[$mid[1] + 1][$mid[0]] = '#';

	// New Start Points
	$map[$mid[1] - 1][$mid[0] - 1] = '1';
	$map[$mid[1] - 1][$mid[0] + 1] = '2';
	$map[$mid[1] + 1][$mid[0] - 1] = '3';
	$map[$mid[1] + 1][$mid[0] + 1] = '4';

	$part2 = getPath($map, buildObjectPaths($map), ['1','2','3','4']);
	echo 'Part 2: ', implode('', $part2[0]), ' in ', $part2[1], "\n";
