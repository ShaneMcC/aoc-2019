#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$reactions = [];
	foreach ($input as $details) {
		preg_match('#(.*) => (.*)#SADi', $details, $m);
		[$all, $required, $produced] = $m;

		if (preg_match('#([0-9]+) ([A-Z]+)#', $produced, $m)) {
			[$all, $count, $item] = $m;

			$reactions[$item] = ['count' => $count, 'required' => []];
			foreach (explode(', ', $required) as $req) {
				if (preg_match('#([0-9]+) ([A-Z]+)#', $req, $m)) {
					[$all, $rcount, $ritem] = $m;
					$reactions[$item]['required'][$ritem] = $rcount;
				}
			}
		}
	}

	function requiredOre($reactions, $item, $count = 1, $spare = [], $indent = 0) {
		$requiredOre = 0;

		if ($item == 'ORE') {
			debugOut(str_repeat("\t", $indent), 'Using ', $count, ' ore', "\n");

			return [$count, $spare];
		}

		debugOut(str_repeat("\t", $indent), 'Need: ', $count, ' of ', $item, "\n");

		// Take into account any spares we already have.
		if (isset($spare[$item])) {
			if ($spare[$item] >= $count) {
				$spare[$item] -= $count;
				debugOut(str_repeat("\t", $indent), "\t", 'Used: ', $count, ' spares leaving ', $spare[$item], "\n");
				return [$requiredOre, $spare];
			} else {
				debugOut(str_repeat("\t", $indent), "\t", 'Used all ', $spare[$item], ' spares leaving 0', "\n");
				$count -= $spare[$item];
				unset($spare[$item]);
			}
		}

		debugOut(str_repeat("\t", $indent), "\t", 'Still need: ', $count, ' of ', $item, "\n");

		// How many reactions do we need to do to produce the amount we require?
		$multiplier = ceil($count / $reactions[$item]['count']);

		// Which will produce this many of the item.
		$produced = $multiplier * $reactions[$item]['count'];

		debugOut(str_repeat("\t", $indent), "\t", $multiplier, ' reactions will produce: ', $produced, "\n");

		// Store spares..
		if ($produced > $count) {
			if (!isset($spare[$item])) { $spare[$item] = 0; }
			$spare[$item] += ($produced - $count);
		}

		debugOut(str_repeat("\t", $indent), "\t", 'leaving: ', ($produced - $count), ' (now ', (isset($spare[$item]) ? $spare[$item] : 0), ') spare', "\n");

		// Do the child reactions required.
		foreach ($reactions[$item]['required'] as $ritem => $rcount) {
			[$o, $spare] = requiredOre($reactions, $ritem, ($rcount * $multiplier), $spare, $indent + 1);
			$requiredOre += $o;
		}

		return [(int)$requiredOre, $spare];
	}

	$react = requiredOre($reactions, 'FUEL');
	$part1 = $react[0];

	echo 'Part 1: ', $part1, "\n";

	function checkMaxFuel($reactions, $max = 1000000000000, $step = null, $start = 0) {
		if ($step == null) { $step = $max / 10; }

		for ($i = $start ;; $i += $step){
			$react = requiredOre($reactions, 'FUEL', $i);
			if ($react[0] > $max) {
				$previous = $i - $step;
				return ($step == 1) ? $previous : checkMaxFuel($reactions, $max, $step / 10, $previous);
			}
		}

		return 0;
	}

	$part2 = checkMaxFuel($reactions);
	echo 'Part 2: ', $part2, "\n";
