#!/usr/bin/php
<?php

	$__CLI['long'] = ['draw1', 'draw2', 'draw'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --draw1              Draw output for part 1.';
	$__CLI['extrahelp'][] = '      --draw2              Draw game for part 2.';
	$__CLI['extrahelp'][] = '      --draw               Draw both parts.';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function playGame($input, $freePlay = false, $draw = false) {
		$grid = [];

		$game = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		if ($freePlay) { $game->setData(0, 2); }

		$score = 0;
		$hadInput = false;

		$ballX = -1;
		$paddleX = -1;

		while (!$game->hasExited()) {
			try {
				$game->step();

				if ($game->hasExited()) { break; }

				// Wait until we have 3 outputs.
				if ($game->getOutputLength() == 3) {
					$x = $game->getOutput();
					$y = $game->getOutput();
					$type = $game->getOutput();

					if ($x == -1 && $y == 0) {
						$score = $type;
					} else {
						$grid[$y][$x] = $type;
					}

					if ($type == 3) { $paddleX = $x; }
					if ($type == 4) { $ballX = $x; }
				}
			} catch (Exception $ex) {
				if ($ballX < $paddleX) { $input = -1; }
				else if ($ballX > $paddleX) { $input = 1; }
				else { $input = 0; }

				$game->appendInput($input);

				if ($draw) { drawMap($grid, $score, $hadInput); }
				$hadInput = true;
			}

			if ($draw && $hadInput) {
				drawMap($grid, $score, true);
			}
		}

		return [$grid, $score];
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

	function drawMap($grid, $score = 0, $redraw = false) {
		$map = flattenMap($grid);

		if ($redraw) { echo "\033[" . (count($map) + 3) . "A"; }

		echo 'Score: ', $score, "\n";
		echo '┍', str_repeat('━', count($grid[0])), '┑', "\n";
		foreach ($map as $row) {
			echo '│';
			echo implode('', $row);
			echo '│';
			echo "\n";
		}
		echo '┕', str_repeat('━', count($map[0])), '┙', "\n";
	}

	$drawResult = playGame($input)[0];

	$part1 = 0;
	foreach ($drawResult as $y => $row) { $acv = array_count_values($row); $part1 += isset($acv['2']) ? $acv['2'] : 0; }
	echo 'Part 1: ', $part1, "\n";
	if (isset($__CLIOPTS['draw1']) || isset($__CLIOPTS['draw'])) {
		drawMap($drawResult);
	}

	$draw = (isset($__CLIOPTS['draw1']) || isset($__CLIOPTS['draw']));
	$gameResult = playGame($input, true, $draw);

	echo 'Part 2: ', $gameResult[1], "\n";
