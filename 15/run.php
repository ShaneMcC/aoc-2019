#!/usr/bin/php
<?php
	$__CLI['long'] = ['draw1'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --draw1              Draw visible points for part 1.';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	$directions = ['1' => ['movement' => ['x' => 0, 'y' => -1]],
	               '4' => ['movement' => ['x' => 1, 'y' => 0]],
	               '2' => ['movement' => ['x' => 0, 'y' => 1]],
	               '3' => ['movement' => ['x' => -1, 'y' => 0]],
	              ];

	function solve($input, $draw = false) {
		global $directions;

		$map = [];
		$x = $y = 0;
		$direction = null;

		$map[$y][$x] = '1';

		$robot = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		while (!$robot->hasExited()) {
			try {
				$robot->step();
				if ($robot->hasExited()) { break; }

				// Wait until we have an output.
				if ($robot->getOutputLength() == 1) {
					$type = $robot->getOutput();

					// Draw
					$checkY = $y + $details['movement']['y'];
					$checkX = $x + $details['movement']['x'];

					$map[$checkY][$checkX] = $type;
					if ($type != 0) {
						$y = $checkY;
						$x = $checkX;
					} else {
						$direction = null;
					}

					if ($draw) { drawMap($map, [$x, $y]); }

					if ($type == 2) {
						break;
					}
				}
			} catch (Exception $ex) {
				// Robot wants input.

				// If we don't have a direction to keep going, find something
				// new.
				if ($direction == null) {
					foreach ($directions as $n => $details) {
						$checkY = $y + $details['movement']['y'];
						$checkX = $x + $details['movement']['x'];

						if (!isset($map[$checkY][$checkX])) {
							$direction = $n;
							$moved = true;
							break;
						}
					}

					if ($direction == null) {
						foreach ($directions as $n => $details) {
							$checkY = $y + $details['movement']['y'];
							$checkX = $x + $details['movement']['x'];

							if (isset($map[$checkY][$checkX]) && $map[$checkY][$checkX] != '0') {
								$direction = $n;
								break;
							}
						}
					}
				}

				$robot->appendInput($direction);
			}
		}

		return $map;
	}

	function typeToSymbol($type) {
		switch ($type) {
			case 0:
				return '█';
			case 1:
				return '.';
			case 2:
				return 'O';
			default:
				return '?';
		}
	}

	function flattenMap($inputMap) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($inputMap);

		$map = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = ' ';
			if (isset($inputMap[$y][$x])) {
				$map[$y][$x] = typeToSymbol($inputMap[$y][$x]);
			}
		}

		return $map;
	}

	function drawMap($inputMap, $loc) {
		$map = flattenMap($inputMap);
		$map[$loc[1]][$loc[0]] = 'D';

		echo '┍', str_repeat('━', count($map[0])), '┑', "\n";
		foreach ($map as $row) { echo '│', implode('', $row), '│', "\n"; }
		echo '┕', str_repeat('━', count($map[0])), '┙', "\n";
		echo "\n\n";
	}

	$map = solve($input, isset($__CLIOPTS['draw1']));
	$part1 = 0;
	foreach ($map as $row) {
		$acv = array_count_values($row);
		$part1 += isset($acv[1]) ? $acv[1] : 0;
	}

	echo 'Part 1: ', $part1, "\n";
