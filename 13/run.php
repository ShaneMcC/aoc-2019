#!/usr/bin/php
<?php

	$__CLI['long'] = ['draw1'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --draw1              Draw visible points for part 1.';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function drawGame($input) {
		$grid = [];

		$game = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		while (!$game->hasExited()) {
			try {
				$game->step();

				if ($game->hasExited()) { break; }

				// Wait until we have 3 outputs.
				if ($game->getOutputLength() == 3) {
					if (isDebug()) { echo 'Game output: ', json_encode($game->getAllOutput()), "\n"; }

					$x = $game->getOutput();
					$y = $game->getOutput();
					$type = $game->getOutput();

					if (isDebug()) { echo 'Drawing: [', $x, ', ', $y, '] in ', $type, "\n"; }

					// Paint
					$grid[$y][$x] = $type;
				}
			} catch (Exception $ex) {
				echo 'Game wants input.', "\n";
			}
		}

		return [$grid];
	}

	function flattenMap($input) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($input);

		$grid = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$grid[$y][$x] = ' ';
			if (isset($input[$y][$x])) {
				$draw = ' ';
				switch ($input[$y][$x]) {
					case 0:
						$draw = ' ';
						break;
					case 1:
						$draw = '█';
						break;
					case 2:
						$draw = '#';
						break;
					case 3:
						$draw = '=';
						break;
					case 4:
						$draw = 'o';
						break;
				}
				$grid[$y][$x] = $draw;
			}
		}

		return $grid;
	}

	function drawMap($grid) {
		$map = flattenMap($grid);
		foreach ($map as $row) { echo implode('', $row), "\n"; }
	}

	$drawResult = drawGame($input)[0];

	$part1 = 0;
	foreach ($drawResult as $y => $row) { $acv = array_count_values($row); $part1 += isset($acv['2']) ? $acv['2'] : 0; }
	echo 'Part 1: ', $part1, "\n";
	if (isset($__CLIOPTS['draw1'])) {
		drawMap($drawResult);
	}

