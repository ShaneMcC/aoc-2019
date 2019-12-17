#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function getInitialView($input) {
		$output = '';
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		while (!$vm->hasExited()) {
			$vm->step();

			if ($vm->hasExited()) { break; }

			// Wait until we have outputs
			if ($vm->getOutputLength() == 1) {
				$out = $vm->getOutput();
				$output .= chr($out);
			}
		}

		$bits = explode("\n", $output);
		$output = [];
		foreach ($bits as $bit) { if (!empty($bit)) { $output[] = str_split($bit); } }

		return $output;
	}

	function drawMap($grid, $redraw = false) {
		$height = count($grid) + 2;
		if ($redraw) { echo "\033[" . $height . "A"; }

		echo '┍', str_repeat('━', count($grid[0])), '┑', "\n";
		foreach ($grid as $row) { echo '│', implode('', $row), '│', "\n"; }
		echo '┕', str_repeat('━', count($grid[0])), '┙', "\n";
	}

	$initialView = getInitialView($input);
	$callibration = 0;

	foreach (yieldXY(0, 0, count($initialView[0]), count($initialView)) as $x => $y) {
		if (isset($initialView[$y][$x]) && $initialView[$y][$x] == '#') {
			if ((isset($initialView[$y+1][$x]) && $initialView[$y+1][$x] == '#') &&
				(isset($initialView[$y-1][$x]) && $initialView[$y-1][$x] == '#') &&
				(isset($initialView[$y][$x-1]) && $initialView[$y][$x-1] == '#') &&
				(isset($initialView[$y][$x+1]) && $initialView[$y][$x+1] == '#')) {
				$callibration += ($x * $y);
			}
		}
	}
	echo 'Part 1: ', $callibration, "\n";

	function findRobot($map) {
		foreach (yieldXY(0, 0, count($map[0]), count($map)) as $x => $y) {
			if (isset($map[$y][$x]) && $map[$y][$x] == '^') {
				return [$x, $y];
			}
		}

		return FALSE;
	}

	$directions = ['^' => ['rotations' => ['<', '>'], 'movement' => ['x' => 0, 'y' => -1]],
	               '>' => ['rotations' => ['^', 'v'], 'movement' => ['x' => 1, 'y' => 0]],
	               'v' => ['rotations' => ['>', '<'], 'movement' => ['x' => 0, 'y' => 1]],
	               '<' => ['rotations' => ['v', '^'], 'movement' => ['x' => -1, 'y' => 0]],
	              ];

	function getInstructions($map) {
		global $directions;

		$start = findRobot($map);

		$instructions = [];

		$dir = '^';
		[$x, $y] = $start;

		if (isset($map[$y][$x - 1]) && $map[$y][$x - 1] == '#') {
			// Start Left.
			$instructions[] = 'L';
			$dir = '<';
		} else {
			// Start Right.
			$instructions[] = 'R';
			$dir = '>';
		}
		$x += $directions[$dir]['movement']['x'];
		$y += $directions[$dir]['movement']['y'];

		$counter = 1;
		while (true) {
			$checkX = $x + $directions[$dir]['movement']['x'];
			$checkY = $y + $directions[$dir]['movement']['y'];

			$next = isset($map[$checkY][$checkX]) ? $map[$checkY][$checkX] : '.';

			if ($next == '#') {
				$x = $checkX;
				$y = $checkY;
				$counter++;
				continue;
			} else {
				$instructions[] = $counter;

				// Where now?
				$left = $directions[$dir]['rotations'][0];
				$leftX = $x + $directions[$left]['movement']['x'];
				$leftY = $y + $directions[$left]['movement']['y'];

				$right = $directions[$dir]['rotations'][1];
				$rightX = $x + $directions[$right]['movement']['x'];
				$rightY = $y + $directions[$right]['movement']['y'];

				if (isset($map[$leftY][$leftX]) && $map[$leftY][$leftX] == '#') {
					$instructions[] = 'L';
					$dir = $left;
					$counter = 0;
				} else if (isset($map[$rightY][$rightX]) && $map[$rightY][$rightX] == '#') {
					$instructions[] = 'R';
					$dir = $right;
					$counter = 0;
				} else {
					break;
				}
			}
		}

		return $instructions;
	}

	// Terrible attempt at figuring out common strings of instructions.
	// I'm sure there is a nicer way, but this works for now.
	function getMovementInstructions($instructions) {
		$insString = implode(',', $instructions) . ',';

		$stringID = 65;
		$strings = [];
		$finalProgram = [];

		$test = [];
		$i = 0;
		$previous = 0;
		while (isset($instructions[$i])) {
			$test[] = $instructions[$i++];
			$test[] = $instructions[$i++];

			$testString = implode(',', $test);

			if (isset($strings[$testString])) {
				debugOut('String ', $strings[$testString],': ', $testString, "\n");
				$finalProgram[] = $strings[$testString];
				$test = [];
				continue;
			}

			$count = substr_count($insString, $testString . ',');

			debugOut('Test: ', $count, ' => ', $testString, "\n");

			if ($count < 2 && $previous > 1) {
				$past = array_splice($test, 0, count($test) - 2);
				$test = array_splice($test, count($test) - 2);

				$pastString = implode(',', $past);
				if (!isset($strings[$pastString])) {
					$strings[$pastString] = chr($stringID++);
					$insString = str_replace($pastString, '#', $insString);
				}

				$finalProgram[] = $strings[$pastString];

				debugOut('String ', $strings[$pastString],': ', $pastString, "\n");
			}

			$previous = $count;
		}

		if (!empty($test)) {
			$pastString = implode(',', $test);
			$finalProgram[] = $strings[$pastString];
			debugOut('FINAL String ', $strings[$pastString],': ', $pastString, "\n");
		}

		return array_merge([implode(',', $finalProgram)], array_keys($strings));
	}

	$instructions = getInstructions($initialView);

	$inputInstructions = getMovementInstructions($instructions);
	$inputInstructions[] = 'n';
	$inputInstructions[] = '';

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	foreach (str_split(implode("\n", $inputInstructions)) as $i) { $vm->appendInput(ord($i)); }
	$vm->setData(0, 2);
	$vm->run();

	if ($vm->hasExited()) {
		$vmOut = $vm->getAllOutput();

		$out = $vmOut[count($vmOut) - 1];
		if ($out > 255) {
			echo 'Part 2: ', $out, "\n";
		} else {
			echo 'Error in Part 2.', "\n";

			$vmOut = '';
			foreach ($vmOut as $out) { $output .= chr($out); }
			$bits = explode("\n", $output);
			$output = [];
			foreach ($bits as $bit) { if (!empty($bit)) { $output[] = str_split($bit); } }
			drawMap($output);
		}
	}
