#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();

	$width = isTest() ? 3 : 25;
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
