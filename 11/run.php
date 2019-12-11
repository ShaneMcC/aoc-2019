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
					if ($colour == '0') {
						// Black
						$map[$y][$x] = '.';
					} else if ($colour == '1') {
						// White
						$map[$y][$x] = '#';
					}

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
					if ($map[$y][$x] == '.') {
						// Black
						$robot->appendInput(0);
						if (isDebug()) { echo 'Robot is above black.', "\n"; }
					} else if ($map[$y][$x] == '#') {
						// White
						$robot->appendInput(1);
						if (isDebug()) { echo 'Robot is above white.', "\n"; }
					}
				} else {
					// Default black
					$robot->appendInput(0);
					if (isDebug()) { echo 'Robot is above default black.', "\n"; }
				}
			}
		}

		return [$map];
	}

	function drawMap($painted) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($painted);

		$map = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = ' ';
			if (isset($painted[$y][$x])) {
				$map[$y][$x] = $painted[$y][$x] == '#' ? '█' : ' ';
			}
		}

		foreach ($map as $row) { echo implode('', $row), "\n"; }
	}

	$encodedChars = ['011001001010010111101001010010' => 'A',
	                 '111001001011100100101001011100' => 'B',
	                 '011001001010000100001001001100' => 'C',
	                 '' => 'D',
	                 '111101000011100100001000011110' => 'E',
	                 '111101000011100100001000010000' => 'F',
	                 '011001001010000101101001001110' => 'G',
	                 '100101001011110100101001010010' => 'H',
	                 '' => 'I',
	                 '001100001000010000101001001100' => 'J',
	                 '100101010011000101001010010010' => 'K',
	                 '100001000010000100001000011110' => 'L',
	                 '' => 'M',
	                 '' => 'N',
	                 '' => 'O',
	                 '111001001010010111001000010000' => 'P',
	                 '' => 'Q',
	                 '111001001010010111001010010010' => 'R',
	                 '' => 'S',
	                 '' => 'T',
	                 '100101001010010100101001001100' => 'U',
	                 '' => 'V',
	                 '' => 'W',
	                 '' => 'X',
	                 '100011000101010001000010000100' => 'Y',
	                 '111100001000100010001000011110' => 'Z',
	                ];

	function decodeText($painted) {
		global $encodedChars;

		[$minX, $minY, $maxX, $maxY] = getBoundingBox($painted);
		$map = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$map[$y][$x] = '0';
			if (isset($painted[$y][$x])) {
				$map[$y][$x] = $painted[$y][$x] == '#' ? '1' : '0';
			}
		}

		$text = '';
		$charCount = floor(count($map[0]) / 5);
		$chars = [];

		foreach ($map as $row) {
			array_shift($row);
			for ($i = 0; $i < $charCount; $i++) {
				$chars[$i][] = implode('', array_slice($row, ($i * 5), 5));
			}
		}

		foreach ($chars as $c) {
			$id = implode('', $c);
			if (isDebug() && !isset($encodedChars[$id])) { echo 'Unknown Letter: ', $id, "\n"; }
			$text .= isset($encodedChars[$id]) ? $encodedChars[$id] : '?';
		}

		return $text;
	}

	$paintResult = paintHull($input)[0];

	$part1 = 0;
	foreach ($paintResult as $y => $row) {
		$part1 += count($row);
	}
	echo 'Part 1: ', $part1, "\n";

	$paintResult = paintHull($input, '#')[0];
	echo 'Part 2: ', decodeText($paintResult), "\n";
	drawMap($paintResult);
