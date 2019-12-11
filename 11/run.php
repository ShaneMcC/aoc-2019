#!/usr/bin/php
<?php
	$__CLI['long'] = ['draw1'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --draw1              Draw visible points for part 1.';


	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	require_once(dirname(__FILE__) . '/../common/decodeText.php');
	$input = getInputLine();

	$directions = ['^' => ['rotations' => ['<', '>'], 'movement' => ['x' => 0, 'y' => -1]],
	               '>' => ['rotations' => ['^', 'v'], 'movement' => ['x' => 1, 'y' => 0]],
	               'v' => ['rotations' => ['>', '<'], 'movement' => ['x' => 0, 'y' => 1]],
	               '<' => ['rotations' => ['v', '^'], 'movement' => ['x' => -1, 'y' => 0]],
	              ];


	function paintHull($input, $startColour = 0) {
		global $directions;

		$map = [];
		$x = $y = 0;
		$direction = '^';

		$map[$y][$x] = $startColour;

		$robot = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		while (!$robot->hasExited()) {
			try {
				$robot->step();

				if ($robot->hasExited()) { break; }

				// Wait until we have 2 outputs.
				if ($robot->getOutputLength() == 2) {
					if (isDebug()) { echo 'Robot output: ', json_encode($robot->getAllOutput()), "\n"; }

					$colour = $robot->getOutput();
					$rotation = $robot->getOutput();

					if (isDebug()) { echo 'Painting: [', $x, ', ', $y, '] in ', $colour, "\n"; }

					// Paint
					$map[$y][$x] = $colour;

					// Handle movement.
					if (isDebug()) { echo 'Rotating ', $rotation, ' from: [', $direction, ']', "\n"; }
					// Set our new direction:
					$direction = $directions[$direction]['rotations'][$rotation];

					if (isDebug()) { echo 'Rotating to: [', $direction, ']', "\n"; }

					// Move one.
					$x += $directions[$direction]['movement']['x'];
					$y += $directions[$direction]['movement']['y'];
				}
			} catch (Exception $ex) {
				// Robot wants input.
				if (isset($map[$y][$x])) {
					$robot->appendInput($map[$y][$x]);
					if (isDebug()) { echo 'Robot is above ', $map[$y][$x], "\n"; }
				} else {
					// Default black
					$robot->appendInput(0);
					if (isDebug()) { echo 'Robot is above default 0.', "\n"; }
				}
			}
		}

		return [$map];
	}

	function flattenMap($painted, $white = '█', $black = ' ', $offsetY = 0, $offsetX = 0) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($painted);

		$map = [];
		foreach (yieldXY($minX + $offsetX, $minY + $offsetY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = $black;
			if (isset($painted[$y][$x])) {
				$map[$y][$x] = $painted[$y][$x] == 1 ? $white : $black;
			}
		}

		return $map;
	}

	function drawMap($painted) {
		$map = flattenMap($painted);
		foreach ($map as $row) { echo implode('', $row), "\n"; }
	}

	$paintResult = paintHull($input)[0];

	$part1 = 0;
	foreach ($paintResult as $y => $row) { $part1 += count($row); }
	echo 'Part 1: ', $part1, "\n";
	if (isset($__CLIOPTS['draw1'])) {
		drawMap($paintResult);
	}

	$paintResult = paintHull($input, 1)[0];
	echo 'Part 2: ', decodeText(flattenMap($paintResult, '1', '0', 0, 1)), "\n";
	drawMap($paintResult);
