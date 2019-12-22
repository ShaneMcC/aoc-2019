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
		die();
	} else {
		echo 'Part 1: ', array_search('2019', $shuffled), "\n";
	}


	// Sigh, part 2 is another case of "what you did already is useless, you need
	// some special magic sauce instead."
	//
	// TLDR: Horrible maths. Reimplemented https://github.com/clgroft/advent-of-code-2019/blob/master/solutions/day22.rb

	function shuffleDeckSmart($deckLen, $pattern) {
		$a = 1;
		$b = 0;

		foreach ($pattern as $p) {
			if (preg_match('#deal with increment ([0-9]+)#', $p, $m)) {
				$inv = modinv($m[1], $deckLen);
        		$a = ($a * $inv) % $deckLen;
			} else if (preg_match('#cut ([-0-9]+)#', $p, $m)) {
				$b = ((($a * $m[1]) % $deckLen) + $b) % $deckLen;
			} else if (preg_match('#deal into new stack#', $p, $m)) {
				$b = ($b - $a % $deckLen);
        		$a = (0 - $a) % $deckLen;
			}
		}

		return [$a, $b, $deckLen];
	}

	// TODO: Don't use gmp.
	function modinv($a, $b) {
		return gmp_invert($a, $b);
	}

	/* [$a, $b, $deckLen] = shuffleDeckSmart(10007, $input);
	for ($i = 0; $i <= $deckLen; $i++) {
		$get = ($a * $i + $b) % $deckLen;
		if ($get == 2019) {
			echo 'Part 1: ', $i, "\n";
			break;
		}
	} */

	[$a, $b, $deckLen] = shuffleDeckSmart(119315717514047, $input);

	function deckPow($a, $b, $deckLen, $p) {
		$result = [$a, $b, $deckLen];

		$sq = [$a, $b, $deckLen];
	    while ($p > 0) {
	      if ($p % 2 == 1) {
	      	$result = compose($result, $sq);
	      }
	      $p = floor($p / 2);
	      $sq = compose($sq, $sq);
	    }

	    return $result;
	}

	function compose($deck, $other) {
		$newDeck = $deck;

		$newDeck[0] = $deck[0] * $other[0] % $deck[2];
		$newDeck[1] = ((($deck[0] * $other[1]) % $deck[2]) + $deck[1]) % $deck[2];

		return $newDeck;
	}

	[$a, $b, $deckLen] = deckPow($a, $b, $deckLen, 101741582076661 - 1);
	$get = ($a * 2020 + $b) % $deckLen;
	echo 'Part 2: ', $get, "\n";

