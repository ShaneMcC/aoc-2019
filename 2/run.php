#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();
	$input = explode(',', $input);

	$input[1] = 12;
	$input[2] = 2;

	for ($i = 0; $i < count($input); $i++) {
		$opcode = $input[$i];

		if ($opcode == 1) {
			$num1 = $input[$i+1];
			$num2 = $input[$i+2];
			$pos = $input[$i+3];

			$input[$pos] = $input[$num1] + $input[$num2];

			$i += 3;
		} else if ($opcode == 2) {
			$num1 = $input[$i+1];
			$num2 = $input[$i+2];
			$pos = $input[$i+3];

			$input[$pos] = $input[$num1] * $input[$num2];

			$i += 3;
		} else if ($opcode == 99) {
			break;
		}
	}

echo 'Part 1: ', $input[0], "\n";
