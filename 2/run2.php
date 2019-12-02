#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function computer($input, $noun, $verb) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		$vm->setData(1, $noun);
		$vm->setData(2, $verb);
		$vm->setDebug(isDebug());
		$vm->run();

		return $vm->getData(0);
	}

	$part1 = isTest() ? computer($input, 0, 0) : computer($input, 12, 2);
	echo 'Part 1: ', $part1, "\n";

	if (isTest()) { die(); }

	for ($n = 0; $n <= 99; $n++) {
		for ($v = 0; $v <= 99; $v++) {
			$ans = computer($input, $n, $v);

			if ($ans == 19690720) {
				echo 'Part 2: 100 * ', $n, ' + ', $v, ' = ', (100 * $n + $v), "\n";
				die();
			}
		}
	}
