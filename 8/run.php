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

	$encodedChars = ['011001001010010111101001010010' => 'A',
	                 '011001001010000100001001001100' => 'C',
	                 '111101000011100100001000011110' => 'E',
	                 '111101000011100100001000010000' => 'F',
	                 '011001001010000101101001001110' => 'G',
	                 '100101001011110100101001010010' => 'H',
	                 '001100001000010000101001001100' => 'J',
	                 '111001001010010111001000010000' => 'P',
	                 '111001001010010111001010010010' => 'R',
	                 '111100001000100010001000011110' => 'Z',
	                ];

	function decodeText($layers) {
		global $encodedChars;

		$text = '';
		$charCount = strlen($layers[0]) / 5;
		$chars = [];

		foreach ($layers as $layer) {
			for ($i = 0; $i < $charCount; $i++) {
				$chars[$i][] = substr($layer, ($i * 5), 5);
			}
		}

		foreach ($chars as $c) {
			$id = implode('', $c);
			if (isDebug() && !isset($encodedChars[$id])) { echo 'Unknown Letter: ', $id, "\n"; }
			$text .= isset($encodedChars[$id]) ? $encodedChars[$id] : '?';
		}

		return $text;
	}

	$layers = getLayers($input, $width, $height);

	[$bestLayer, $bestCount0, $bestMul12] = getPart1($layers);
	echo 'Part 1: Layer ', $bestLayer, ' has ', $bestCount0, ' 0s for a total of: ', $bestMul12, "\n";

	[$finalImage, $text] = flattenImage($layers);
	echo 'Part 2: ', $text, "\n";
	if (isDebug()) { echo $finalImage, "\n"; }
