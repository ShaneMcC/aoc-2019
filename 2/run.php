#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function computer($input, $noun, $verb) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->useInterrupts(false);

		$vm->setData(1, $noun);
		$vm->setData(2, $verb);
		$vm->setDebug(isDebug());
		$vm->run();

		return $vm->getData(0);
	}

	$part1 = isTest() ? computer($input, 0, 0) : computer($input, 12, 2);
	echo 'Part 1: ', $part1, "\n";

	if (isTest()) { die(); }

	function runPart2($input, $target, $naive = false) {
		for ($n = 0; $n <= 99; $n++) {
			// If we're brute forcing, pretend we found something to start
			// testing verbs.
			$ans = $naive ? ($target + 1) : computer($input, $n, 0);

			if ($ans > $target) {
				// If we're not brute forcing, we actually want to look at the
				// previous Noun not this one.
				$testN = $naive ? $n : $n - 1;

				for ($v = 0; $v <= 99; $v++) {
					$ans = computer($input, $testN, $v);

					if ($ans == $target) {
						echo 'Part 2: 100 * ', $testN, ' + ', $v, ' = ', (100 * $testN + $v), "\n";
						die();
					}
				}
			}
		}
	}

	// Run part2 twice, once abusing the properties of provided inputs
	// (All values of N,<0-99> will be smaller than N+1,0).
	runPart2($input, 19690720);

	// If that fails, try the brute-force approach.
	runPart2($input, 19690720, true);
