#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/decodeText.php');
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

	function getPart1($layers) {
		$bestLayer = 0;
		$bestCount0 = PHP_INT_MAX;
		$bestMul12 = 0;

		foreach ($layers as $layerID => $layer) {
			$merged = implode('', $layer);
			$count0 = substr_count($merged, '0');
			$count1 = substr_count($merged, '1');
			$count2 = substr_count($merged, '2');

			if ($count0 < $bestCount0) {
				$bestCount0 = $count0;
				$bestMul12 = $count1 * $count2;
				$bestLayer = $layerID;
			}
		}

		return [$bestLayer, $bestCount0, $bestMul12];
	}

	function flattenImage($layers) {
		$width = strlen($layers[0][0]);
		$height = count($layers[0]);

		$finalImage = $layers[0];

		foreach ($layers as $layer) {
			foreach (yieldXY(0,0, $width, $height, false) as $x => $y) {
				if ($finalImage[$y][$x] == '2') {
					$finalImage[$y][$x] = $layer[$y][$x];
				}
			}
		}
		$text = decodeText($finalImage);

		$finalImage = implode("\n", $finalImage);
		$finalImage = str_replace('0', ' ', $finalImage);
		$finalImage = str_replace('1', '█', $finalImage);

		return [$finalImage, $text];
	}

	$layers = getLayers($input, $width, $height);

	[$bestLayer, $bestCount0, $bestMul12] = getPart1($layers);
	echo 'Part 1: Layer ', $bestLayer, ' has ', $bestCount0, ' 0s for a total of: ', $bestMul12, "\n";

	[$finalImage, $text] = flattenImage($layers);
	echo 'Part 2: ', $text, "\n";
	echo $finalImage, "\n";


//	foreach ($layers as $l) { echo '[', implode(', ', str_split(implode('', $l))), ']', "\n"; }
//	echo "\n", 'Final: [', implode(', ', str_split(str_replace(['█', ' ', "\n"], ['0', '1', ''], $finalImage))), ']', "\n";
