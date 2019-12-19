#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function testXY($input, $x, $y) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->appendInput($x);
		$vm->appendInput($y);
		$vm->run();

		echo '[', $x, ', ', $y, ']', "\n";

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
		$map[$y][$x] = $out;
	}

	drawMap($map);
	echo 'Part 1: ', $part1, "\n";
