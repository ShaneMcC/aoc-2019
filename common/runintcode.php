#!/usr/bin/php
<?php
	$__CLI['long'] = ['output', 'fast', 'ascii'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --output             Show all output at the end.';
	$__CLI['extrahelp'][] = '      --ascii              Assume output is ascii';
	$__CLI['extrahelp'][] = '      --fast               Remove sleep time on debug.';

	require_once(dirname(__FILE__) . '/common.php');
	require_once(dirname(__FILE__) . '/IntCodeVM.php');
	$input = getInputLine();

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	$vm->setDebug(true);
	if (isset($__CLIOPTS['fast'])) {
		$vm->setDebug(true, 0);
	}

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
	echo 'End.', "\n\n";

	if (isset($__CLIOPTS['output'])) {
		echo 'All output: ', "\n";
		if (isset($__CLIOPTS['ascii'])) {
			foreach ($vm->getAllOutput() as $o) { echo chr($o); }
		} else {
			echo implode(', ', $vm->getAllOutput());
		}

		echo "\n";
	}
