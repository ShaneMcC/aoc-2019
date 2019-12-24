#!/usr/bin/php
<?php
	$__CLI['long'] = ['output', 'fast', 'ascii', 'nodebug'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --output             Show all output at the end.';
	$__CLI['extrahelp'][] = '      --ascii              Assume output is ascii';
	$__CLI['extrahelp'][] = '      --fast               Remove sleep time on debug.';
	$__CLI['extrahelp'][] = '      --nodebug            No debug, only output.';

	require_once(dirname(__FILE__) . '/common.php');
	require_once(dirname(__FILE__) . '/IntCodeVM.php');
	$input = getInputLine();

	$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
	$vm->useInterrupts(true);
	if (!isset($__CLIOPTS['nodebug'])) {
		$vm->setDebug(true);
		if (isset($__CLIOPTS['fast'])) {
			$vm->setDebug(true, 0);
		}
	}

	$output = [];

	while (!$vm->hasExited()) {
		try {
			$vm->step();
			if ($vm->hasExited()) { break; }
		} catch (OutputGivenInterrupt $ex) {
			$out = $vm->getOutput();
			$output[] = $out;
			if (isset($__CLIOPTS['ascii'])) {
				echo chr($out);
			} else {
				echo 'Output: ', $out, "\n";
			}
		} catch (InputWantedException $ex) {
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
			foreach ($output as $o) { echo chr($o); }
		} else {
			echo implode(', ', $output);
		}

		echo "\n";
	}
