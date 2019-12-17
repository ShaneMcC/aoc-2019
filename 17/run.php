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
		foreach ($grid as $row) {
			echo '│';
			echo implode('', $row);
			echo '│';
			echo "\n";
		}
		echo '┕', str_repeat('━', count($grid[0])), '┙', "\n";
	}

	$initialView = getInitialView($input);
	drawMap($initialView);

	$newView = $initialView;
	$newViewCount = 0;

	foreach (yieldXY(0, 0, count($initialView[0]), count($initialView)) as $x => $y) {
		if ($initialView[$y][$x] == '#') {
			if ($initialView[$y+1][$x] == '#' &&
				$initialView[$y-1][$x] == '#' &&
				$initialView[$y][$x-1] == '#' &&
				$initialView[$y][$x+1] == '#') {
				$newView[$y][$x] = 'O';

				$newViewCount += ($x * $y);
			}
		}
	}

	drawMap($newView);

	echo 'Part 1: ', $newViewCount, "\n";
