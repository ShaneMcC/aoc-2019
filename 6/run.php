#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$orbits = array();
	foreach ($input as $details) {
		preg_match('#(.*)\)(.*)#SADi', $details, $m);
		[$all, $orbited, $orbiter] = $m;

		$orbits[$orbiter] = $orbited;
	}

	function getOrbitChain($object) {
		global $orbits;
		if (!isset($orbits[$object])) { return []; }

		$chain = [$orbits[$object]];
		$chain = array_merge($chain, getOrbitChain($orbits[$object]));

		return $chain;
	}

	function getCommonPath($orbit1, $orbit2) {
		$path = [];

		$i1 = count($orbit1) - 1;
		$i2 = count($orbit2) - 1;

		while (true) {
			if ($orbit1[$i1] == $orbit2[$i2]) {
				$path[] = $orbit1[$i1];
			} else {
				break;
			}
			$i1--;
			$i2--;
		}

		return $path;
	}

	$part1 = 0;
	foreach (array_keys($orbits) as $object) { $part1 += count(getOrbitChain($object)); }

	echo 'Part 1: ', $part1, "\n";

	$you = getOrbitChain('YOU');
	$san = getOrbitChain('SAN');
	$common = getCommonPath($you, $san);

	$part2 = (count($you) - count($common)) + (count($san) - count($common));

	echo 'Part 2: ', $part2, "\n";
