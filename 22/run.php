#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

	$deck = [];
	for ($i = 0; $i < (isTest() ? 10 : 10007); $i++) { $deck[] = $i; }

	function shuffleDeck($deck, $pattern) {
		$deckLen = count($deck);

		debugOut('Deck started as: ', implode(' ', $deck) . "\n");
		foreach ($pattern as $p) {
			debugOut("\t", $p, "\n");
			if (preg_match('#deal with increment ([0-9]+)#', $p, $m)) {
				$increment = $m[1];
				$newDeck = array_fill(0, $deckLen, '.');
				$pos = 0;
				foreach ($deck as $d) {
					$newDeck[$pos] = $d;
					$pos = ($pos + $increment) % $deckLen;
				}
			} else if (preg_match('#cut ([-0-9]+)#', $p, $m)) {
				$cutPos = $m[1];
				if ($cutPos > 0) {
					$newDeck = array_merge(array_splice($deck, $cutPos), array_splice($deck, 0, $cutPos));
				} else if ($cutPos < 0) {
					$newDeck = array_merge(array_splice($deck, $deckLen - abs($cutPos)), array_splice($deck, 0, $deckLen - abs($cutPos)));
				}
			} else if (preg_match('#deal into new stack#', $p, $m)) {
				$newDeck = array_reverse($deck);
			}

			debugOut("\t\t", 'deck is now: ', implode(' ', $newDeck) . "\n");
			$deck = $newDeck;
		}

		debugOut('Deck ended as: ', implode(' ', $deck) . "\n");
		return $deck;
	}

	$shuffled = shuffleDeck($deck, $input);
	debugOut("\n");

	if (isTest()) {
		echo 'Result: ', implode(' ', $shuffled), "\n";
	} else {
		echo 'Part 1: ', array_search('2019', $shuffled), "\n";
	}
