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


	$inputInstructions = explode("\n", <<<INSTRUCTIONS
# If A is not ground, jump.
NOT A J

# If B is not ground, and A and C are then we can jump over it.
NOT B T
AND A T
AND C T
OR T J

# if C is not ground and D and H are, then it'll probably be safe to jump, then
# we are still safe to jump again even if E is not ground.
NOT C T
AND D T
AND H T
OR T J

# Only ever jump if our landing point D is safe.
AND D J

RUN
INSTRUCTIONS);

	$part2 = runBot($input, $inputInstructions);
	echo 'Part 2: ', $part2, "\n";
