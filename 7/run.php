#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function test($input, $phaseSettings) {
		$lastOutput = 0;
		foreach (['A', 'B', 'C', 'D', 'E'] as $amp) {
			$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
			$vm->setMiscData('pid', $amp);
			$vm->appendInput(array_shift($phaseSettings));
			$vm->appendInput($lastOutput);
			$vm->run();

			$lastOutput = $vm->getOutput();
		}

		return $lastOutput;
	}

	$part1 = 0;
	$part1Settings = [];
	foreach (getPermutations(['0', '1', '2', '3', '4']) as $phaseSettings) {
		$output = test($input, $phaseSettings);

		if ($output > $part1) {
			$part1 = $output;
			$part1Settings = $phaseSettings;
		}

	}

	echo 'Part 1: ', $part1, ' with [', implode(', ', $part1Settings), ']', "\n";
