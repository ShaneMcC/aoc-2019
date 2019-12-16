#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/common.php');
	require_once(dirname(__FILE__) . '/IntCodeVM.php');
	$input = getInputLine();

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	$vm->setDebug(true);

	while (!$vm->hasExited()) {
		try {
			$vm->step();
			if ($vm->hasExited()) { break; }
		} catch (Exception $ex) {
			// Take user input.
			while (true) {
				$in = readline('Input Number: ');
				if (is_numeric($in)) { break; }
			}
			$vm->appendInput($in);
		}
	}
