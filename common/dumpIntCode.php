#!/usr/bin/php
<?php

	require_once(dirname(__FILE__) . '/common.php');
	require_once(dirname(__FILE__) . '/IntCodeVM.php');

	$input = IntCodeVM::parseInstrLines(getInputLine());
	$vm = new IntCodeVM();

	$instrs = [];

	$instrs['ADD'] = function($code, $a, $b, $c) { return sprintf('{%s} @%s = @%s + @%s', $code, $c, $a, $b); };
	$instrs['MUL'] = function($code, $a, $b, $c) { return sprintf('{%s} @%s = @%s * @%s', $code, $c, $a, $b); };
	$instrs['HALT'] = function($code) { return sprintf('{%s} HALT', $code); };
	$instrs['UNKNOWN'] = function($code) { return sprintf('{%s}', $code); };

	for ($i = 0; $i < count($input); $i++) {
		$next = $input[$i];
		try {
			[$name, $argCount, $ins] = $vm->getInstr($next);
		} catch (Exception $ex) {
			[$name, $argCount, $ins] = ['UNKNOWN', 0, function(){}];
		}

		$args = array_slice($input, ($i + 1), $argCount);

		$code = call_user_func_array($instrs[$name], array_merge([$next], $args));
		$inst = IntCodeVM::instrToString([$name . '{' . $next . '}', $args]);

		echo sprintf('(%3s - %-3s)   %-40s # %s', $i, ($i + $argCount), $code, $inst), "\n";

		$i += $argCount;
	}
