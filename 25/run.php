#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');

	$input = getInputLine();

	// There are a bunch of items in the game, we need to collect (some of)
	// them, then navigate to the weighing room holding the right set of items
	// to convince the gate that we are a droid, then we can walk past to the
	// room with the answer.
	//
	// Sounds easy enough, but first we need to find the items. Which are
	// different per input.

	function inputText($vm, $text) {
		foreach (str_split($text) as $t) {
			$vm->appendInput(ord($t));
		}
		$vm->appendInput(ord("\n"));
	}

	function getOutputText($vm) {
		$text = '';
		foreach ($vm->getAllOutput() as $out) { $text .= chr($out); }
		$vm->clearOutput();
		return $text;
	}

	// Map the ship, find the rooms and items
	function mapArea($input) {
		$baseVM = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$baseVM->useInputInterrupt(false);
		$baseVM->useOutputInterrupt(false);
		$baseVM->setMiscData('path', []);
		$vms = [$baseVM];

		$allRooms = [];
		$allItems = [];

		while (!empty($vms)) {
			foreach (array_keys($vms) as $key) {
				$vm = $vms[$key];
				// VMs only ever last 1 cycle.
				unset($vms[$key]);

				// Run the vm, find where we are, what is here,
				// what we can do.
				$vm->run();

				// Get the most recent output from the VM.
				$text = getOutputText($vm);

				// Current path to get here.
				$path = $vm->getMiscData('path');

				// Room name.
				if (!preg_match('#==(.*)==#', $text, $name)) {
					debugOut('==========', "\n");
					debugOut('Something bad happened: ', "\n");
					debugOut($text, "\n");
					debugOut('My path: ', implode(',', $path), "\n");
					debugOut('==========', "\n");
					break;
				}

				// var_dump($text);

				$name = trim($name[1]);
				// Room is known, abort.
				if (isset($allRooms[$name])) { continue; }

				// Find directions or items.
				preg_match_all('#^- (.+)$#im', $text, $options);
				$directions = [];
				$items = [];
				foreach ($options[1] as $opt) {
					if (in_array($opt, ['north', 'south', 'east', 'west'])) {
						$directions[] = $opt;
					} else {
						$items[] = $opt;
					}
				}

				// New Room.
				debugOut('Found new room: ', $name, ' with path: ', implode(', ', $path), "\n");
				debugOut("\t", 'Directions: ', implode(', ', $directions), "\n");
				debugOut("\t", 'Items: ', implode(', ', $items), "\n");

				$allRooms[$name] = [];
				$allRooms[$name]['name'] = $name;
				$allRooms[$name]['path'] = $path;
				$allRooms[$name]['directions'] = $directions;
				$allRooms[$name]['items'] = $items;

				foreach ($items as $item) {
					$allItems[$item] = ['room' => $name];
				}

				// If we're not at the 'Pressure-Sensitive Floor' then Spawn
				// new VMs that go in the available directions.
				if ($name != 'Pressure-Sensitive Floor') {
					foreach ($directions as $d) {
						$newVM = $vm->clone();
						$newVM->setMiscData('path', array_merge($path, [$d]));
						inputText($newVM, $d);
						$vms[] = $newVM;
					}
				}
			}
		}

		return [$baseVM, $allRooms, $allItems];
	}

	function getInverseDirection($direction) {
		if ($direction == 'north') { return 'south'; }
		else if ($direction == 'south') { return 'north'; }
		else if ($direction == 'east') { return 'west'; }
		else if ($direction == 'west') { return 'east'; }
		return '';
	}

	// Collect an item.
	// Run to room, take item, run back to start.
	function collectItem($vm, $allRooms, $allItems, $item) {
		if (!isset($allItems[$item]['room'])) { return; }
		$targetRoom = $allRooms[$allItems[$item]['room']];

		$reversePath = [];
		// Walk to room.
		foreach ($targetRoom['path'] as $path) {
			inputText($vm, $path);
			$reversePath[] = getInverseDirection($path);
		}

		// Take item
		inputText($vm, 'take ' . $item);

		// Go back to start.
		foreach (array_reverse($reversePath) as $path) { inputText($vm, $path); }
		$vm->run();
		$vm->clearOutput();
	}

	// Current inventory.
	function getInventory($vm) {
		$vm->clearOutput();
		inputText($vm, 'inv');
		$vm->run();
		$text = getOutputText($vm);
		preg_match_all('#^- (.+)$#im', $text, $options);
		return $options[1];
	}

	// Map the ship
	[$vm, $allRooms, $allItems] = mapArea($input);

	// Collect the items.
	debugOut('Collecting items.', "\n");
	foreach (array_keys($allItems) as $item) {
		// Don't collect known-bad items.
		if (in_array($item, ['photons', 'giant electromagnet', 'escape pod', 'infinite loop', 'molten lava'])) { continue; }
		debugOut("\t", 'Collecting: ', $item, "\n");
		collectItem($vm, $allRooms, $allItems, $item);
	}

	// Go to security room.
	debugOut('Going to Security Room.', "\n");
	$targetRoom = $allRooms['Security Checkpoint'];
	foreach ($targetRoom['path'] as $path) { inputText($vm, $path); }
	$vm->run();

	// How do we get to the Pressure-Sensitive Floor ?
	$floorDirection = array_pop($allRooms['Pressure-Sensitive Floor']['path']);

	$usefulItems = getInventory($vm);

	// Drop all our items.
	debugOut('Dropping items.', "\n");
	foreach ($usefulItems as $item) { inputText($vm, 'drop '  . $item); }
	$vm->run();
	$vm->clearOutput();

	debugOut('Trying all combinations.', "\n");
	foreach (getAllSets($usefulItems) as $combo) {
		$testVM = $vm->clone();

		debugOut("\t", 'Trying: ', implode(',', $combo), "\n");

		foreach ($combo as $item) { inputText($testVM, 'take '  . $item); }
		inputText($testVM, $floorDirection);
		$testVM->run();

		$text = getOutputText($testVM);
		if (preg_match('#get in by typing (.*) on the keypad#', $text, $m)) {
			echo 'Part 1: ', $m[1], "\n";
			break;
		}
	}
