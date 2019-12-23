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
		$game->useInterrupts(true);

		if ($freePlay) { $game->setData(0, 2); }

		$score = 0;
		$hadInput = false;

		$ballX = -1;
		$ballMoved = false;
		$paddleX = -1;
		$paddleMoved = false;

		$drawResult = NULL;
		[$minX, $minY, $maxX, $maxY] = null;

		while (!$game->hasExited()) {
			try {
				$game->step();

				if ($game->hasExited()) { break; }
			} catch (OutputGivenInterrupt $ex) {
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

					if ($draw && $drawResult != null) {
						// Redraw bit of screen.
						echo "\033[s"; // Store current cursor position

						// Move to top left
						echo "\033[" . $drawResult . "A"; // Back to top of screen
						echo "\r"; // Start of line.

						// Update screen.
						if ($x == -1 && $y == 0) {
							echo sprintf('Score: %-10s', $score); // Score.
						} else {
							echo "\033[" . ($y + 2). "B"; // Down Y (+border) lines.
							echo "\033[" . ($x + 1) . "C"; // Right X (+border) lines.

							echo typeToSymbol($type);
						}

						echo "\033[u"; // Revert cursor position

						// Sleep for non-blanking display changes.
						if ($type != 0) { usleep(25000); }
					}
				}
			} catch (InputWantedException $ex) {
				if ($ballX < $paddleX) { $input = -1; }
				else if ($ballX > $paddleX) { $input = 1; }
				else { $input = 0; }

				$game->appendInput($input);

				if ($draw && !$hadInput) { $drawResult = drawMap($grid, $score, false); }
				$hadInput = true;
			}
		}

		return [$grid, $score];
	}

	function typeToSymbol($type) {
		switch ($type) {
			case 0:
				return ' ';
			case 1:
				return '█';
			case 2:
				return '#';
			case 3:
				return '=';
			case 4:
				return 'o';
			default:
				return '?';
		}
	}

	function flattenMap($input) {
		[$minX, $minY, $maxX, $maxY] = getBoundingBox($input);

		$grid = [];
		foreach (yieldXY($minX, $minY, $maxX, $maxY, true) as $x => $y) {
			$grid[$y][$x] = ' ';
			if (isset($input[$y][$x])) {
				$grid[$y][$x] = typeToSymbol($input[$y][$x]);
			}
		}

		return $grid;
	}

	function drawMap($grid, $score = 0, $redraw = false) {
		$map = flattenMap($grid);

		$height = count($map) + 3;

		if ($redraw) { echo "\033[" . $height . "A"; }

		echo 'Score: ', $score, "\n";
		echo '┍', str_repeat('━', count($grid[0])), '┑', "\n";
		foreach ($map as $row) {
			echo '│';
			echo implode('', $row);
			echo '│';
			echo "\n";
		}
		echo '┕', str_repeat('━', count($map[0])), '┙', "\n";
		return $height;
	}

	$drawResult = playGame($input)[0];

	$part1 = 0;
	foreach ($drawResult as $y => $row) { $acv = array_count_values($row); $part1 += isset($acv['2']) ? $acv['2'] : 0; }
	echo 'Part 1: ', $part1, "\n";
	if (isset($__CLIOPTS['draw1']) || isset($__CLIOPTS['draw'])) {
		drawMap($drawResult);
	}

	$draw = (isset($__CLIOPTS['draw2']) || isset($__CLIOPTS['draw']));
	$gameResult = playGame($input, true, $draw);

	echo 'Part 2: ', $gameResult[1], "\n";
