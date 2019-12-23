#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	$computers = [];
	for ($i = 0; $i < 50; $i++) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->appendInput($i);
		$computers[] = $vm;
	}

	while (true) {
		foreach ($computers as $c) {
			try {
				$c->step();

				if ($c->getOutputLength() == 3) {
					$addr = $c->getOutput();
					$x = $c->getOutput();
					$y = $c->getOutput();

					if ($addr == 255) {
						echo 'Part 1: ', $y, "\n";
						break 2;
					}

					$computers[$addr]->appendInput($x);
					$computers[$addr]->appendInput($y);
				}

			} catch (Exception $e) {
				$c->appendInput(-1);
			}
		}
	}
