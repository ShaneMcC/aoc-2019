#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function runBot($input, $instructions) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));

		// Give the VM the instructions.
		// foreach (str_split(implode("\n", $instructions) . "\n") as $i) { $vm->appendInput(ord($i)); }
		foreach ($instructions as $i) {
			$i = trim($i);
			if (!empty($i) && $i[0] != '#') {
				echo $i, "\n";
				foreach (str_split($i) as $b) { $vm->appendInput(ord($b)); }
				$vm->appendInput(10);
			}
		}
		$vm->run();

		$vmOut = $vm->getAllOutput();
		$lastOut = $vmOut[count($vmOut) - 1];
		if ($lastOut > 255) {
			return $lastOut;
		} else {
			echo 'Error with robot.', "\n";

			$output = '';
			foreach ($vmOut as $out) { $output .= chr($out); }
			echo $output, "\n";
			return FALSE;
		}
	}

	$inputInstructions = explode("\n", <<<INSTRUCTIONS
# If A is not ground, jump.
NOT A J

# if D is ground and C is not, jump.
NOT C T
AND D T
OR T J

WALK
INSTRUCTIONS);

	$part1 = runBot($input, $inputInstructions);
	echo 'Part 1: ', $part1, "\n";
