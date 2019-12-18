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

			$wantedDoors = [];
			foreach ($pf[0]['previous'] as $step) {
				if (in_array($map[$step[1]][$step[0]], array_keys($doors))) {
					$wantedDoors[] = $map[$step[1]][$step[0]];
				}
			}

			$objects[$a]['to'][$b] = ['steps' => $pf[0]['steps'], 'doors' => $wantedDoors];
			$objects[$b]['to'][$a] = ['steps' => $pf[0]['steps'], 'doors' => $wantedDoors];
		}
	}

	function getReachableObjects($map, $objects, $from, $keys) {
		$valid = [];
		foreach ($objects[$from]['to'] as $key => $obj) {
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
	function getPath($map, $objects, $from, $visited = [], $count = 0, $max = PHP_INT_MAX) {
		global $__KNOWN;

		$visited[] = $from;

		$id = $visited;
		sort($id);
		$id = implode('', $id) . ',' . $from;
		if (isset($__KNOWN[$id]) && $__KNOWN[$id] < $count) { return [FALSE, FALSE]; }
		$__KNOWN[$id] = $count;

		debugOut(implode('', $visited), "\n");

		$reachable = getReachableObjects($map, $objects, $from, $visited);

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
				[$p, $c] = getPath($map, $objects, $key, $visited, $steps, $bestCount);
				if ($p != FALSE && $c < $bestCount) {
					$bestPath = $p;
					$bestCount = $c;
				}
			}
		}

		return [$bestPath, $bestCount];
	}

	$part1 = getPath($map, $objects, '@');

	if (isDebug()) {
		var_dump($part1);
	}

	echo 'Part 1: ', implode('', $part1[0]), ' in ', $part1[1], "\n";
