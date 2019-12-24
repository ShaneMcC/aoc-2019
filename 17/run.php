#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function getInitialView($input) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->useInterrupts(false);
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

	$inputIsIntCode = preg_match('#^[0-9-,]+$#', $input);
	if (!$inputIsIntCode) {
		$initialView = [];
		foreach (getInputLines() as $line) { if (!empty($line)) { $initialView[] = str_split($line); } }
	} else {
		$initialView = getInitialView($input);
	}
	$callibration = 0;

	foreach (cells($initialView) as [$x, $y, $cell]) {
		if ($cell == '#') {
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
		foreach (cells($map) as [$x, $y, $cell]) {
			if ($cell == '^') {
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

	function breakdownInstructions($instructions) {
		$insString = implode(',', $instructions) . ',';

		$regex = '#^(.{1,20},)\1*(.{1,20},)(?:\1|\2)*(.{1,20},)(?:\1|\2|\3)*$#';

		if (preg_match($regex, $insString, $m)) {
			[$all, $a, $b, $c] = $m;

			$all = rtrim($all, ',');
			$a = rtrim($a, ',');
			$b = rtrim($b, ',');
			$c = rtrim($c, ',');

			$all = str_replace([$a, $b, $c], ['A', 'B', 'C'], $all);
		}

		return [$all, $a, $b, $c];
	}

	// Figure out instructions.
	$instructions = getInstructions($initialView);
	$inputInstructions = breakdownInstructions($instructions);

	if (!$inputIsIntCode || isDebug()) {
		echo 'Instructions: ', implode(',', $instructions), "\n";
		echo 'VM Input: ', "\n";
		foreach ($inputInstructions as $i) { echo $i, "\n"; }
		if (!$inputIsIntCode) { die(); }
	}

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	$vm->useInterrupts(false);
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
