#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function getInitialView($input) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->run();

		$output = '';
		foreach ($vm->getAllOutput() as $out) { $output .= chr($out); }
		$bits = explode("\n", $output);
		$output = [];
		foreach ($bits as $bit) { if (!empty($bit)) { $output[] = str_split($bit); } }

		return $output;
	}

	function drawMap($grid, $redraw = false) {
		$height = count($grid) + 2;
		$width = count($grid[0]);
		if ($redraw) { echo "\033[" . $height . "A"; }

		echo '┍', str_repeat('━', $width), '┑', "\n";
		foreach ($grid as $row) { echo '│', sprintf('%-' . $width . 's', implode('', $row)), '│', "\n"; }
		echo '┕', str_repeat('━', $width), '┙', "\n";
	}

	if (isTest()) {
		$initialView = [];
		foreach (getInputLines() as $line) { if (!empty($line)) { $initialView[] = str_split($line); } }
	} else {
		$initialView = getInitialView($input);
	}
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

	$directions = ['^' => ['rotations' => ['<', '>'], 'movement' => ['x' => 0, 'y' => -1]],
	               '>' => ['rotations' => ['^', 'v'], 'movement' => ['x' => 1, 'y' => 0]],
	               'v' => ['rotations' => ['>', '<'], 'movement' => ['x' => 0, 'y' => 1]],
	               '<' => ['rotations' => ['v', '^'], 'movement' => ['x' => -1, 'y' => 0]],
	              ];

	function getInstructions($map) {
		global $directions;

		$start = FALSE;
		foreach (yieldXY(0, 0, count($map[0]), count($map)) as $x => $y) {
			if (isset($map[$y][$x]) && $map[$y][$x] == '^') {
				$start = [$x, $y];
				break;
			}
		}
		if ($start == FALSE) { die('No Robot.'); }

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
	function breakdownInstructions($instructions) {
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

			// Are we part of a previously found string?
			foreach (array_keys($strings) as $knownString) {
				if ($knownString == $testString) {
					debugOut('Previous String ', $strings[$testString],': ', $testString, "\n");
					$finalProgram[] = $strings[$testString];
					$test = [];
					continue 2;
				} else if (strpos($knownString, $testString) === 0) {
					debugOut('Partial previous String ', $strings[$knownString],': ', $testString, "\n");
					continue 2;
				}
			}

			if (strlen($testString) > 20) {
				$count = 0;
			} else {
				$count = substr_count($insString, $testString . ',');
				if (!preg_match('@^[#,]*' . preg_quote($testString, '@') . '@', $insString)) {
					$count = 0;
				}
			}
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

	// Figure out instructions.
	$instructions = getInstructions($initialView);
	$inputInstructions = breakdownInstructions($instructions);

	if (isTest()) {
		echo 'Instructions: ', implode(',', $instructions), "\n";
		echo 'VM Input: ', "\n";
		foreach ($inputInstructions as $input) { echo $input, "\n"; }
		die();
	}

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	$vm->setData(0, 2);

	// Give the VM the instructions.
	foreach (str_split(implode("\n", $inputInstructions)) as $i) { $vm->appendInput(ord($i)); }
	$vm->appendInput(10); // New line after instructions
	$vm->appendInput(ord('n')); // No live feed
	$vm->appendInput(10); // Final new line to begin.

	$vm->run();

	$vmOut = $vm->getAllOutput();

	$lastOut = $vmOut[count($vmOut) - 1];
	if ($lastOut > 255) {
		echo 'Part 2: ', $lastOut, "\n";
	} else {
		echo 'Error in Part 2.', "\n";

		$output = '';
		foreach ($vmOut as $out) { $output .= chr($out); }
		$bits = explode("\n", $output);
		$output = [];
		foreach ($bits as $bit) { if (!empty($bit)) { $output[] = str_split($bit); } }
		drawMap($output);
	}
