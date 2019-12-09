#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	function runBOOST($input, $value) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->setDebug(isDebug());
		$vm->appendInput($value);
		$vm->run();
		$output = $vm->getAllOutput();
		return array_pop($output);
	}

	$part1 = runBOOST($input, 1);
	echo 'Part 1: ', $part1, "\n";

	$part2 = runBOOST($input, 2);
	echo 'Part 2: ', $part2, "\n";
