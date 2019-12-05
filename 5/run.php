#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));

	$vm->setDebug(isDebug());

	$vm->appendInput(1);
	$vm->run();
	$output = $vm->getAllOutput();
	$part1 = array_pop($output);

	echo 'Part 1: ', $part1, "\n";
