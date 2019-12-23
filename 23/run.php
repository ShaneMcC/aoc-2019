#!/usr/bin/php
<?php
	$__CLI['long'] = ['step'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --step               Run each computer step-by-step rather than running until there is I/O (Slower)';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');
	$input = getInputLine();

	$computers = [];
	for ($i = 0; $i < 50; $i++) {
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->useInterrupts(true);
		$vm->appendInput($i);
		$computers[] = $vm;
	}

	$nat = null;
	$part1 = false;
	$lastNatY = null;
	$stepByStep = isset($__CLIOPTS['step']);

	$empty = [];
	while (true) {
		foreach ($computers as $cid => $c) {
			// Loop and step() rather than using run() becuase we will keep
			// going until we get 3 outputs (or 1 input) not just 1 output.
			// Slightly more efficient this way.
			while (true) {
				try {
					$c->step();
				} catch (OutputGivenInterrupt $e) {
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

						break;
					}
				} catch (InputWantedException $e) {
					$empty[$cid] = true;
					$c->appendInput(-1);
					break;
				}
			}
		}

		if (count($empty) == 50 && $nat != null) {
			$computers[0]->appendInput($nat[0]);
			$computers[0]->appendInput($nat[1]);

			if ($lastNatY == $nat[1]) {
				echo 'Part 2: ', $lastNatY, "\n";
				break;
			}

			unset($empty[0]);
			$lastNatY = $nat[1];
		}
	}
