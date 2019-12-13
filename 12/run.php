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

	function simulate($moons) {
		// Calculate Velocity
		for ($a = 0; $a < count($moons); $a++) {
			for ($b = $a + 1; $b < count($moons); $b++) {
				foreach (['x', 'y', 'z'] as $c) {
					if ($moons[$a]['pos'][$c] > $moons[$b]['pos'][$c]) {
						$moons[$a]['vel'][$c]--;
						$moons[$b]['vel'][$c]++;
					} else if ($moons[$a]['pos'][$c] < $moons[$b]['pos'][$c]) {
						$moons[$a]['vel'][$c]++;
						$moons[$b]['vel'][$c]--;
					}
				}
			}
		}

		// Move based on velocity
		for ($i = 0; $i < count($moons); $i++) {
			foreach (['x', 'y', 'z'] as $c) {
				$moons[$i]['pos'][$c] += $moons[$i]['vel'][$c];
			}
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


	function getStates($moons) {
		$states = [];
		foreach (['x', 'y', 'z'] as $c) {
			$state = [];
			foreach ($moons as $moon) {
				$state[] = [$moon['pos'][$c], $moon['vel'][$c]];
			}

			$states[$c] = $state;
		}

		return $states;
	}

	$initialState = getStates($moons);

	$i = 0;
	$loopTime = [];
	while (true) {
		$i++;
		$moons = simulate($moons);

		if ($i == 1000) {
			echo 'Part 1: ', getEnergy($moons), "\n";
		}

		$checkStates = getStates($moons);

		foreach ($checkStates as $c => $checkState) {
			if (isset($loopTime[$c])) { continue; }
			if ($checkState === $initialState[$c]) {
				$loopTime[$c] = $i;
			}
		}

		if ($i > 1000 && count($loopTime) == 3) { break; }
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
