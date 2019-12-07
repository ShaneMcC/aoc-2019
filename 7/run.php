#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function runAmplifiers($input, $phaseSettings) {
		$lastOutput = 0;
		$amps = [];
		foreach (['A', 'B', 'C', 'D', 'E'] as $amp) {
			$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
			$vm->setMiscData('pid', $amp);
			$vm->appendInput(array_shift($phaseSettings));
			$vm->setDebug(isDebug());
			$amps[$amp] = $vm;
		}

		$hasFinished = false;
		while (!$hasFinished) {
			foreach ($amps as $amp => $vm) {
				$vm->appendInput($lastOutput);
				try {
					$vm->run();
					$hasFinished = true;
				} catch (Exception $ex) {
					/* Need new input, so we move on to the next one. */
				}
				$lastOutput = $vm->getOutput();
			}
		}

		return $lastOutput;
	}

	$part1 = 0;
	$part1Settings = [];
	foreach (getPermutations([0, 1, 2, 3, 4]) as $phaseSettings) {
		$output = runAmplifiers($input, $phaseSettings);

		if ($output > $part1) {
			$part1 = $output;
			$part1Settings = $phaseSettings;
		}

	}

	echo 'Part 1: ', $part1, ' with [', implode(', ', $part1Settings), ']', "\n";

	$part2 = 0;
	$part2Settings = [];
	foreach (getPermutations([5, 6, 7, 8, 9]) as $phaseSettings) {
		$output = runAmplifiers($input, $phaseSettings);

		if ($output > $part2) {
			$part2 = $output;
			$part2Settings = $phaseSettings;
		}

	}

	echo 'Part 2: ', $part2, ' with [', implode(', ', $part2Settings), ']', "\n";
