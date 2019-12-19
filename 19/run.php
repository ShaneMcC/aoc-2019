#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function testXY($input, $x, $y) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->appendInput($x);
		$vm->appendInput($y);
		while (count($vm->getAllOutput()) == 0) { $vm->step(); }

		return $vm->getAllOutput()[0];
	}

	function drawMap($grid, $redraw = false) {
		$height = count($grid) + 2;
		$width = count($grid[0]);
		if ($redraw) { echo "\033[" . $height . "A"; }

		echo '┍', str_repeat('━', $width), '┑', "\n";
		foreach ($grid as $row) { echo '│', sprintf('%-' . $width . 's', implode('', $row)), '│', "\n"; }
		echo '┕', str_repeat('━', $width), '┙', "\n";
	}

	$map = [];
	$part1 = 0;
	foreach (yieldXY(0, 0, 49, 49) as $x => $y) {
		if (!isset($map[$y])) { $map[$y] = []; }

		$out = testXY($input, $x, $y);
		$part1 += $out;
		$map[$y][$x] = $out ? '#' : ' ';
	}

	drawMap($map);
	echo 'Part 1: ', $part1, "\n";

	$map = [];
	$y = 1500; // Reasonable starting guess?
	$found = false;
	$part2 = [0, 0];
	while (true) {
		$y++;

		$foundFirst = false;
		$startX = 0;

		// Look for a line at least 100 wide
		for ($x = $y;; $x++) {
			$out = testXY($input, $x, $y);

			if ($out == 1) {
				$startX = $x;

				$out2 = testXY($input, $x + 99, $y);
				if ($out2 != '1') {
					continue 2;
				} else {
					break;
				}
			}
		}
		// Look across the line to see if we get a valid 100x100 box.
		for ($x2 = $startX + 90 ;; $x2++) {
			$corners = testXY($input, $x2, $y);
			if ($corners == 0) { continue 2; }

			$corners += testXY($input, $x2 + 99, $y);
			$corners += testXY($input, $x2, $y + 99);
			$corners += testXY($input, $x2 + 99, $y + 99);
			if ($corners == 4) { $part2 = [$x2, $y]; break; }
		}

		// We have a box.
		break;
	}

	echo 'Part 2: ', ($part2[0] * 10000) + $part2[1], "\n";
