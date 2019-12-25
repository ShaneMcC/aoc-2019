#!/usr/bin/php
<?php
	$__CLI['long'] = ['manual'];
	$__CLI['extrahelp'] = [];
	$__CLI['extrahelp'][] = '      --manual             Run interactive mode to run manually.';

	require_once(dirname(__FILE__) . '/../common/common.php');
	require_once(dirname(__FILE__) . '/../common/IntCodeVM.php');

	// There are a bunch of items in the game, we need to collect (some of)
	// them, then navigate to the Pressure-Sensitive Floor holding the right
	// set of items to convince the gate that we are a droid, then we can walk
	//  past to the room with the answer.
	//
	// Sounds easy enough.
	//
	// We solve this by:
	//  - Mapping out the entire ship and locating which items are available
	//    and in which rooms. (These are different per input.)
	//  - Collect all the items that except the ones that cause you to die or
	//    be unable to continue.
	//  - Go to the Security Checkpoint
	//  - Drop all the items
	//  - Pick up each combination of items and enter the Pressure-Sensitive
	//    Floor
	//  - If we don't get ejected back to the Security Checkpoint, we have our
	//    answer.
	//
	// Alternatively, this can be played by hand by a human.

	$input = getInputLine();

	if (isset($__CLIOPTS['manual'])) {
		// Interactive mode.
		$vm = new IntCodeVM(IntCodeVM::parseInstrLines($input));
		$vm->useInterrupts(true);

		while (!$vm->hasExited()) {
			try {
				$vm->run();
			} catch (OutputGivenInterrupt $ex) {
				echo $vm->getOutputText();
			} catch (InputWantedException $ex) {
				$vm->inputText(strtolower(readline('Input: ')));
			}
		}

		die('Exited.');
	}

	function parseRoomInfo($text) {
		if (!preg_match('#==(.*)==#', $text, $name)) { return FALSE; }

		$name = trim($name[1]);

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

		return [$name, $directions, $items];
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
				$text = $vm->getOutputText();

				// Current path to get here.
				$path = $vm->getMiscData('path');

				$roomData = parseRoomInfo($text);

				// Room name.
				if ($roomData === FALSE) {
					debugOut('==========', "\n");
					debugOut('Something bad happened: ', "\n");
					debugOut($text, "\n");
					debugOut('My path: ', implode(',', $path), "\n");
					debugOut('==========', "\n");
					break;
				}

				[$name, $directions, $items] = $roomData;

				// Room is known, abort.
				if (isset($allRooms[$name])) { continue; }


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
						$newVM->inputText($d);
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

	function goToRoom($vm, $allRooms, $room) {
		if (!isset($allRooms[$room])) { return; }

		debugOut('Going to ', $room, "\n");
		$targetRoom = $allRooms[$room];

		$reversePath = [];
		// Walk to room.
		foreach ($targetRoom['path'] as $path) {
			$vm->inputText($path);
			$reversePath[] = getInverseDirection($path);
		}
		$vm->run();
	}

	function leaveRoom($vm, $allRooms, $room) {
		if (!isset($allRooms[$room])) { return; }

		debugOut('Leaving ', $room, "\n");
		$targetRoom = $allRooms[$room];

		// Get path back to start.
		$reversePath = [];
		foreach ($targetRoom['path'] as $path) { $reversePath[] = getInverseDirection($path); }
		foreach (array_reverse($reversePath) as $path) { $vm->inputText($path); }
	}

	// Collect an item.
	// Run to room, take item, run back to start.
	function collectItem($vm, $allRooms, $allItems, $item) {
		if (!isset($allItems[$item]['room'])) { return; }

		// Go to room.
		goToRoom($vm, $allRooms, $allItems[$item]['room']);

		// Take item
		$vm->inputText('take ' . $item);

		// Go back to start.
		leaveRoom($vm, $allRooms, $allItems[$item]['room']);

		$vm->run();
		$vm->clearOutput();
	}

	// Current inventory.
	function getInventory($vm) {
		$vm->clearOutput();
		$vm->inputText('inv');
		$vm->run();
		$text = $vm->getOutputText();
		preg_match_all('#^- (.+)$#im', $text, $options);
		return $options[1];
	}

	// Collect all items.
	function collectAllItems($vm, $allRooms, $allItems) {
		// Collect the items.
		debugOut('Collecting items.', "\n");
		foreach (array_keys($allItems) as $item) {
			// Don't collect known-bad items.
			if (in_array($item, ['photons', 'giant electromagnet', 'escape pod', 'infinite loop', 'molten lava'])) { continue; }
			debugOut("\t", 'Collecting: ', $item, "\n");
			collectItem($vm, $allRooms, $allItems, $item);
		}
	}

	// Drop all our current items.
	function dropAllItems($vm) {
		$currentItems = getInventory($vm);
		debugOut('Dropping all items.', "\n");
		foreach ($currentItems as $item) { $vm->inputText('drop '  . $item); }
		$vm->run();
		$vm->clearOutput();

		return $currentItems;
	}

	// Assuming we are in the Security Checkpoint, try and bypass the pressure
	// sensitive floor.
	function bypassPressureSensitiveFloor($vm, $allRooms) {
		// How do we get to the Pressure-Sensitive Floor?
		$floorDirection = array_pop($allRooms['Pressure-Sensitive Floor']['path']);

		// Try going into the room with nothing first to learn what we have
		// available to us on the floor.
		$vm->inputText($floorDirection);
		$vm->run();
		$usefulItems = parseRoomInfo($vm->getOutputText())[2];

		debugOut('Trying all combinations.', "\n");
		foreach (getAllSets($usefulItems) as $combo) {
			if (empty($combo)) { continue; }

			$testVM = $vm->clone();

			debugOut("\t", 'Trying: ', implode(',', $combo), "\n");

			foreach ($combo as $item) { $testVM->inputText('take '  . $item); }
			$testVM->inputText($floorDirection);
			$testVM->run();

			$text = $testVM->getOutputText();
			if (preg_match('#get in by typing (.*) on the keypad#', $text, $m)) {
				return [$testVM, $m[1]];
			}
		}

	}

	// Do Magic.
	[$vm, $allRooms, $allItems] = mapArea($input);
	collectAllItems($vm, $allRooms, $allItems);
	goToRoom($vm, $allRooms, 'Security Checkpoint');
	dropAllItems($vm);
	[$testVM, $part1] = bypassPressureSensitiveFloor($vm, $allRooms);

	echo 'Part 1: ', $part1, "\n";
