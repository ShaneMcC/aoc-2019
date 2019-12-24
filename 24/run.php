#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$map = [];
	foreach ($input as $row) { $map[] = str_split($row); }

	function simulate($map) {
		$newMap = $map;

		foreach ($map as $y => $row) {
			foreach ($row as $x => $cell) {
				$adjacentBugs = 0;

				if (isset($map[$y + 1][$x]) && $map[$y + 1][$x] == '#') { $adjacentBugs++; }
				if (isset($map[$y - 1][$x]) && $map[$y - 1][$x] == '#') { $adjacentBugs++; }
				if (isset($map[$y][$x + 1]) && $map[$y][$x + 1] == '#') { $adjacentBugs++; }
				if (isset($map[$y][$x - 1]) && $map[$y][$x - 1] == '#') { $adjacentBugs++; }

				if ($cell == '#' && $adjacentBugs == 1) {
					$newMap[$y][$x] = '#';
				} else if ($cell == '.' && ($adjacentBugs == 1 || $adjacentBugs == 2)) {
					$newMap[$y][$x] = '#';
				} else if ($cell == '#') {
					$newMap[$y][$x] = '.';
				}
			}
		}

		return $newMap;
	}

	function getScore($map) {
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

	function drawMap($map) {
		foreach ($map as $row) {
			echo implode('', $row), "\n";
		}
		echo "\n";
	}

	$layouts = [];
	$id = json_encode($map);
	$layouts[$id] = true;
	$count = 0;
	while (true) {
		$count++;
		$map = simulate($map);
		$id = json_encode($map);

		if (isset($layouts[$id])) {
			echo 'Part 1: ', getScore($map), "\n";
			die();
		}

		$layouts[$id] = true;
	}
