#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLine();
	$input = explode(',', $input);

	function computer($input, $noun, $verb) {
		$input[1] = $noun;
		$input[2] = $verb;

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

		return $input[0];
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
