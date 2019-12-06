#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$orbits = array();
	foreach ($input as $details) {
		preg_match('#(.*)\)(.*)#SADi', $details, $m);
		[$all, $orbited, $orbiter] = $m;

		if (!isset($orbits[$orbiter])) { $orbits[$orbiter] = []; }
		$orbits[$orbiter][] = $orbited;
	}

	function getOrbitCount($object) {
		global $orbits;
		if (!isset($orbits[$object])) { return 0; }

		$count = count($orbits[$object]);
		foreach ($orbits[$object] as $child) {
			$count += getOrbitCount($child);
		}

		return $count;
	}

	$part1 = 0;
	foreach (array_keys($orbits) as $object) { $part1 += getOrbitCount($object); }

	echo 'Part 1: ', $part1, "\n";
