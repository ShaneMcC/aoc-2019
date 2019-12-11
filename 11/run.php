#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	$directions = ['^' => ['rotations' => ['<', '>'], 'movement' => ['x' => 0, 'y' => -1]],
	               '>' => ['rotations' => ['^', 'v'], 'movement' => ['x' => 1, 'y' => 0]],
	               'v' => ['rotations' => ['>', '<'], 'movement' => ['x' => 0, 'y' => 1]],
	               '<' => ['rotations' => ['v', '^'], 'movement' => ['x' => -1, 'y' => 0]],
	              ];


	function paintHull($input, $startColour = '.') {
		global $directions;

		$map = [];
		$x = $y = 0;
		$direction = '^';

		$map[$y][$x] = $startColour;

// $i = 0;

		$robot = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$robot->setDebug(isDebug());

		while (!$robot->hasExited()) {
			try {
				$robot->step();

				if ($robot->hasExited()) { break; }

				// Wait until we have 2 outputs.
				if ($robot->getOutputLength() == 2) {
// echo 'Robot output: ', json_encode($robot->getAllOutput()), "\n";

					$colour = $robot->getOutput();
					$rotation = $robot->getOutput();

// echo 'Painting: [', $x, ', ', $y, '] in ', $colour, "\n";

					// Paint
					if ($colour == '0') {
						// Black
						$map[$y][$x] = '.';
					} else if ($colour == '1') {
						// White
						$map[$y][$x] = '#';
					}

					// Handle movement.

// echo 'Rotating ', $rotation, ' from: [', $direction, ']', "\n";
					// Set our new direction:
					$direction = $directions[$direction]['rotations'][$rotation];

// echo 'Rotating to: [', $direction, ']', "\n";

					// Move one.
					$x += $directions[$direction]['movement']['x'];
					$y += $directions[$direction]['movement']['y'];

// if ($i++ == 1) { break; }
				}
			} catch (Exception $ex) {
				// Robot wants input.
				if (isset($map[$y][$x])) {
					if ($map[$y][$x] == '.') {
						// Black
						$robot->appendInput(0);
// echo 'Robot is above black.', "\n";
					} else if ($map[$y][$x] == '#') {
						// White
						$robot->appendInput(1);
// echo 'Robot is above white.', "\n";
					}
				} else {
					// Default black
					$robot->appendInput(0);
// echo 'Robot is above default black.', "\n";
				}
			}
		}

		return [$map];
	}

	function drawMap($painted) {
		$minX = $minY = $maxX = $maxY = 0;
		foreach ($painted as $y => $row) {
			$minY = min($minY, $y);
			$maxY = max($maxY, $y);
			foreach ($row as $x => $colour) {
				$minX = min($minX, $x);
				$maxX = max($maxX, $x);
			}
		}

		$map = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = ' ';
			if (isset($painted[$y][$x])) {
				$map[$y][$x] = $painted[$y][$x] == '#' ? '█' : ' ';
			}
		}

		foreach ($map as $row) { echo implode('', $row), "\n"; }
	}

	$paintResult = paintHull($input)[0];

	$part1 = 0;
	foreach ($paintResult as $y => $row) {
		$part1 += count($row);
	}
	echo 'Part 1: ', $part1, "\n";

	$paintResult = paintHull($input, '#')[0];
	echo 'Part 2: ', "\n";
	drawMap($paintResult);
