#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$moons = [];
	foreach ($input as $line) {
		preg_match('#<x=([-0-9]+), y=([-0-9]+), z=([-0-9]+)>#i', $line, $m);
		[$all, $x, $y, $z] = $m;
		$moons[] = ['pos' => ['x' => (int)$x, 'y' => (int)$y, 'z' => (int)$z], 'vel' => ['x' => 0, 'y' => 0, 'z' => 0]];
	}

	function simulate($moons, $axis) {
		// Calculate Velocity
		for ($a = 0; $a < count($moons); $a++) {
			for ($b = $a + 1; $b < count($moons); $b++) {
				if ($moons[$a]['pos'][$axis] > $moons[$b]['pos'][$axis]) {
					$moons[$a]['vel'][$axis]--;
					$moons[$b]['vel'][$axis]++;
				} else if ($moons[$a]['pos'][$axis] < $moons[$b]['pos'][$axis]) {
					$moons[$a]['vel'][$axis]++;
					$moons[$b]['vel'][$axis]--;
				}
			}
		}

		// Move based on velocity
		for ($i = 0; $i < count($moons); $i++) {
			$moons[$i]['pos'][$axis] += $moons[$i]['vel'][$axis];
		}

		return $moons;
	}

	function dumpMoons($moons) {
		foreach ($moons as $moon) {
			echo sprintf('pos=<x=%-4d, y=%-4d, z=%-4d>, vel=<x=%-4d, y=%-4d, z=%-4d>', $moon['pos']['x'], $moon['pos']['y'], $moon['pos']['z'], $moon['vel']['x'], $moon['vel']['y'], $moon['vel']['z']), "\n";
		}
		echo 'Energy: ', getEnergy($moons), "\n";
	}

	function getEnergy($moons) {
		$energy = 0;

		foreach ($moons as $moon) {
			$potential = $kinetic = 0;
			foreach (['x', 'y', 'z'] as $c) {
				$potential += abs($moon['pos'][$c]);
				$kinetic += abs($moon['vel'][$c]);
			}
			$energy += ($potential * $kinetic);
		}

		return $energy;
	}

	function getVelocities($moons, $axis) {
		$state = [];
		foreach ($moons as $moon) {
			$state[] = $moon['vel'][$axis];
		}

		return $state;
	}

	$loopTime = [];
	$initialState = getVelocities($moons, 'x');
	for ($i = 1 ;; $i++) {
		foreach (['x', 'y', 'z'] as $axis) {
			$moons = simulate($moons, $axis);
			if (isset($loopTime[$axis])) { continue; }

			if (getVelocities($moons, $axis) == $initialState) {
				$loopTime[$axis] = $i * 2;
			}
		}
		if ($i > 1000 && count($loopTime) == 3) { break; }

		if ($i == 1000) {
			echo 'Part 1: ', getEnergy($moons), "\n";
		}
	}

	// LCM and GCD from https://stackoverflow.com/questions/147515/least-common-multiple-for-3-or-more-numbers
	function gcd($a, $b){
		$t = 0;
		while ($b != 0){
			$t = $b;
			$b = $a % $b;
			$a = $t;
		}

		return $a;
	}

	function lcm($a, $b){
		return ($a * $b / gcd($a, $b));
	}

	$part2 = lcm($loopTime['x'], lcm($loopTime['y'], $loopTime['z']));
	echo 'Part 2: ', $part2, "\n";
