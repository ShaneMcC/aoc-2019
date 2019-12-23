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

	$nat = null;
	$part1 = false;
	$lastNatY = null;

	$empty = [];
	while (true) {
		foreach ($computers as $cid => $c) {
			try {
				$c->step();

				if ($c->getOutputLength() == 3) {
					$addr = $c->getOutput();
					$x = $c->getOutput();
					$y = $c->getOutput();

					if ($addr == 255) {
						if ($part1 == false) {
							echo 'Part 1: ', $y, "\n";
							$part1 = true;
						}

						$nat = [$x, $y];
					} else {
						unset($empty[$addr]);
						$computers[$addr]->appendInput($x);
						$computers[$addr]->appendInput($y);
					}
				}

			} catch (Exception $e) {
				$empty[$cid] = true;
				$c->appendInput(-1);
			}
		}

		if (count($empty) == 50 && $nat != null) {
			$computers[0]->appendInput($nat[0]);
			$computers[0]->appendInput($nat[1]);

			if ($lastNatY == $nat[1]) {
				echo 'Part 2: ', $lastNatY, "\n";
				break;
			}

			$lastNatY = $nat[1];
			$nat = null;
		}
	}
