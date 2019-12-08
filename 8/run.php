#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();

	$width = isTest() ? 2 : 25;
	$height = isTest() ? 2 : 6;

	function getLayers($input, $width, $height) {
		$layers = [];
		$layer = [];
		$row = '';
		foreach (str_split($input) as $bit) {
			$row .= $bit;

			if (strlen($row) == $width) {
				$layer[] = $row;
				$row = '';
				if (count($layer) == $height) {
					$layers[] = $layer;
					$layer = [];
				}
			}
		}

		return $layers;
	}

	$layers = getLayers($input, $width, $height);

	$bestCount0 = PHP_INT_MAX;
	$bestMul12 = 0;
	foreach ($layers as $layer) {
		$merged = implode('', $layer);
		$count0 = substr_count($merged, '0');
		$count1 = substr_count($merged, '1');
		$count2 = substr_count($merged, '2');

		if ($count0 < $bestCount0) {
			$bestCount0 = $count0;
			$bestMul12 = $count1 * $count2;
		}
	}

	echo 'Part 1: ', $bestMul12, "\n";

	$finalImage = $layers[0];
	foreach ($layers as $layer) {
		foreach (yieldXY(0,0, $width, $height, false) as $x => $y) {
			if ($finalImage[$y][$x] == '2') {
				$finalImage[$y][$x] = $layer[$y][$x];
			}
		}
	}
	$finalImage = implode("\n", $finalImage);

	$finalImage = str_replace('0', ' ', $finalImage);
	$finalImage = str_replace('1', '#', $finalImage);

	echo 'Part 2: ', "\n", $finalImage, "\n";
