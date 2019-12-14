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

		// Bits of our requirements that can be produced directly with ore.
		foreach ($reactions[$item]['required'] as $ritem => $rcount) {
			if (isset($reactions[$ritem]['required']['ORE'])) {
				// How many of this item do we need?
				$required = $rcount * $multiplier;

				debugOut(str_repeat("\t", $indent), "\t", 'Need: ', $required, ' of ', $ritem, "\n");

				// Take into account any spares we already have.
				if (isset($spare[$ritem])) {
					if ($spare[$ritem] >= $required) {
						$spare[$ritem] -= $required;
						debugOut(str_repeat("\t", $indent), "\t\t", 'Used: ', $required, ' spares leaving ', $spare[$ritem], "\n");
						continue;
					} else {
						debugOut(str_repeat("\t", $indent), "\t\t", 'Used all ', $spare[$ritem], ' spares leaving 0', "\n");
						$required -= $spare[$ritem];
						unset($spare[$ritem]);
					}
				}

				debugOut(str_repeat("\t", $indent), "\t\t", 'Still need: ', $required, ' of ', $ritem, "\n");

				// How many reactions to produce the amount required?
				$rmultiplier = ceil($required / $reactions[$ritem]['count']);

				// How many items are produced total?
				$rproduced = $rmultiplier * $reactions[$ritem]['count'];

				// How much ore did this use?
				$requiredOre += ($rmultiplier * $reactions[$ritem]['required']['ORE']);

				debugOut(str_repeat("\t", $indent), "\t\t", $rmultiplier, ' reactions will produce: ', $rproduced, ' using ', ($rmultiplier * $reactions[$ritem]['required']['ORE']), ' ore', "\n");

				// Store spares..
				if ($rproduced > $required) {
					if (!isset($spare[$ritem])) { $spare[$ritem] = 0; }
					$spare[$ritem] += ($rproduced - $required);
				}

				debugOut(str_repeat("\t", $indent), "\t\t", 'leaving: ', ($rproduced - $required), ' (now ', (isset($spare[$ritem]) ? $spare[$ritem] : 0), ') spare', "\n");
			}
		}

		// Now produce other items.
		// Items that can not be produced directly with ore.
		foreach ($reactions[$item]['required'] as $ritem => $rcount) {
			if (!isset($reactions[$ritem]['required']['ORE'])) {
				[$o, $spare] = requiredOre($reactions, $ritem, ($rcount * $multiplier), $spare, $indent + 1);
				$requiredOre += $o;
			}
		}

		return [(int)$requiredOre, $spare];
	}

	$react = requiredOre($reactions, 'FUEL');
	$part1 = $react[0];

	echo 'Part 1: ', $part1, "\n";
