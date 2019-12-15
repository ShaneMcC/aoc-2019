#!/usr/bin/php
<?php
	$__CLI['long'] = ['draw1', 'draw2', 'draw'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --draw1              Draw visible map for part 1.';
	$__CLI['extrahelp'][] = '      --draw2              Draw visible map for part 2.';
	$__CLI['extrahelp'][] = '      --draw               Draw visible map for both parts.';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	require_once(dirname(__FILE__) . '/../common/pathfinder.php');
	$input = getInputLine();

	$directions = ['1' => ['movement' => ['x' => 0, 'y' => -1]],
	               '4' => ['movement' => ['x' => 1, 'y' => 0]],
	               '2' => ['movement' => ['x' => 0, 'y' => 1]],
	               '3' => ['movement' => ['x' => -1, 'y' => 0]],
	              ];

	function mapArea($input, $draw = false) {
		global $directions;

		$map = [];
		$map[0][0] = '1';

		$oxygen = [];

		$robot = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$robot->setMiscData('x', 0);
		$robot->setMiscData('y', 0);
		$robots = [$robot];

		if ($draw) { drawMap($map, false); }

		while (!empty($robots)) {
			foreach (array_keys($robots) as $key) {
				$robot = $robots[$key];
				$direction = $robot->getMiscData('direction');
				$x = $robot->getMiscData('x');
				$y = $robot->getMiscData('y');

				try {
					$robot->step();
					if ($robot->hasExited()) { unset($robots[$key]); continue; }

					if ($robot->getOutputLength() == 1) {
						$type = $robot->getOutput();

						$checkY = $y + $directions[$direction]['movement']['y'];
						$checkX = $x + $directions[$direction]['movement']['x'];

						$map[$checkY][$checkX] = $type;
						if ($type == 0) {
							// Kill bots that hit a wall
							unset($robots[$key]);
						} else {
							// Move the bot.
							$robot->setMiscData('x', $checkX);
							$robot->setMiscData('y', $checkY);
						}

						if ($type == 2) { $oxygen = [$checkX, $checkY]; }

						if ($draw) { drawMap($map, true); usleep(1000); }
					}

				} catch (Exception $ex) {
					// Robot wants input, kill it and spawn replacements.
					$state = $robot->saveState();

					// Robot current location.
					$x = $robot->getMiscData('x');
					$y = $robot->getMiscData('y');

					// Kill this robot.
					unset($robots[$key]);

					// Create Replacements.
					for ($i = 1; $i <= 4; $i++) {
						// Only create replacements that are finding new things.
						$checkY = $y + $directions[$i]['movement']['y'];
						$checkX = $x + $directions[$i]['movement']['x'];
						if (isset($map[$checkY][$checkX])) { continue; }

						$r = new IntCodeVM();
						$r->loadState($state);
						$r->appendInput($i);
						$r->setMiscData('direction', $i);
						$r->setMiscData('x', $x);
						$r->setMiscData('y', $y);
						$robots[] = $r;
					}
				}
			}
		}

		return [$map, $oxygen];
	}

	function typeToSymbol($type) {
		switch ($type) {
			case 0:
				return '█';
			case 1:
				return "\033[0;31m" . '█' . "\033[0m";
			case 2:
				return "\033[1;34m" . '█' . "\033[0m";
			default:
				return '?';
		}
	}

	function flattenMap($inputMap, $padding = 25) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($inputMap);

		// Makes drawing nicer.
		if ($padding > 0) {
			$minX = min(0 - $padding, $minX);
			$minY = min(0 - $padding, $minY);
			$maxX = max($padding, $maxX);
			$maxY = max($padding, $maxY);
		}

		$map = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = ' ';
			if (isset($inputMap[$y][$x])) {
				$map[$y][$x] = typeToSymbol($inputMap[$y][$x]);
			}
		}

		return $map;
	}

	function drawMap($inputMap, $redraw = false, $loc = null, $steps = []) {
		$map = flattenMap($inputMap);

		foreach ($steps as $s) {
			$map[$s[1]][$s[0]] = "\033[1;32m" . '█' . "\033[0m";
		}
		if ($loc != null) {
			$map[$loc[1]][$loc[0]] = "\033[1;33m" . '█' . "\033[0m";
		}

		$height = count($map) + 2;
		if ($redraw) { echo "\033[" . $height . "A"; }

		echo '┍', str_repeat('━', count($map[0])), '┑', "\n";
		foreach ($map as $row) { echo '│', implode('', $row), '│', "\n"; }
		echo '┕', str_repeat('━', count($map[0])), '┙', "\n";
	}

	function generateOxygen($map, $draw = false) {
		$minutes = 0;

		if ($draw) {
			drawMap($map);
			echo "\033[1A";
			echo "\033[2C";
			echo '[ Minutes: ', $minutes, ' ]', "\n";
			usleep(1000);
		}

		do {
			$minutes++;

			// Expand Oxygen.
			$oldMap = $map;
			foreach ($oldMap as $y => $row) {
				foreach ($row as $x => $type) {
					if ($oldMap[$y][$x] == 2) {
						foreach ([[$x + 1, $y], [$x - 1, $y], [$x, $y + 1], [$x, $y - 1]] as $adj) {
							if ($map[$adj[1]][$adj[0]] == 1) {
								$map[$adj[1]][$adj[0]] = 2;
							}
						}
					}
				}
			}

			if ($draw) {
				drawMap($map, true);
				echo "\033[1A";
				echo "\033[2C";
				echo '[ Minutes: ', $minutes, ' ]', "\n";
				usleep(1000);
			}

			$spaces = ['1' => 0, '2' => 0];
			foreach ($map as $row) {
				foreach (array_count_values($row) as $type => $count) {
					if (isset($spaces[$type])) { $spaces[$type] += $count; }
				}
			}

		} while ($spaces[1] > 0);

		return $minutes;
	}

	$draw1 = isset($__CLIOPTS['draw1']) || isset($__CLIOPTS['draw']);
	$draw2 = isset($__CLIOPTS['draw2']) || isset($__CLIOPTS['draw']);

	[$map, $oxygen] = mapArea($input, $draw1);

	$pf = new PathFinder($map, [0, 0], $oxygen);
	$pf->setHook('isAccessible', function($state, $x, $y) {
		return ($state['grid'][$y][$x] != '0');
	});
	$foo = $pf->solveMaze();

	if ($draw1) {
		drawMap($map, true, [0, 0], $foo[0]['previous']);
	}
	$part1 = $foo[0]['steps'];

	echo 'Part 1: ', $part1, "\n";

	$part2 = generateOxygen($map, $draw2);
	echo 'Part 2: ', $part2, "\n";
