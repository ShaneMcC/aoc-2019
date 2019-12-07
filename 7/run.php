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
				try { $vm->run(); } catch (Exception $ex) { /* Need new input, so we move on to the next one. */ }
				$hasFinished |= $vm->hasExited();
				$lastOutput = $vm->getOutput();
			}
		}

		return $lastOutput;
	}


	function test($input, $possibleSettings) {
		$answer = 0;
		$settings = [];

		foreach (getPermutations($possibleSettings) as $phaseSettings) {
			$output = runAmplifiers($input, $phaseSettings);

			if ($output > $answer) {
				$answer = $output;
				$settings = $phaseSettings;
			}
		}

		return [$answer, $settings];
	}

	[$part1, $part1Settings] = test($input, [0, 1, 2, 3, 4]);
	echo 'Part 1: ', $part1, ' with [', implode(', ', $part1Settings), ']', "\n";

	[$part2, $part2Settings] = test($input, [5, 6, 7, 8, 9]);
	echo 'Part 2: ', $part2, ' with [', implode(', ', $part2Settings), ']', "\n";
