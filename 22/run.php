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
	// TLDR: Horrible maths. https://www.reddit.com/r/adventofcode/comments/ee0rqi/2019_day_22_solutions/fbnkaju/

	function shuffleDeckSmart($deckLen, $pattern) {
		$offset = 0;
		$increment = 1;

		foreach ($pattern as $p) {
			if (preg_match('#deal with increment ([0-9]+)#', $p, $m)) {
				$increment *= inv($m[1], $deckLen);
			} else if (preg_match('#cut ([-0-9]+)#', $p, $m)) {
				$offset += $increment * $m[1];
			} else if (preg_match('#deal into new stack#', $p, $m)) {
  				$increment *= -1;
  				$offset += $increment;
			}
		}

		return [$offset, $increment, $deckLen];
	}

	/* [$offset, $increment, $deckLen] = shuffleDeckSmart(10007, $input);
	for ($i = 0; $i <= $deckLen; $i++) {
		$val = ($offset + ($increment * $i)) % $deckLen;
		if ($val == 2019) {
			echo 'Part 1: ', $i, "\n";
			break;
		}
	} */

	// https://en.wikipedia.org/wiki/Modular_exponentiation#Right-to-left_binary_method
	// Doesn't seem to work at the scale we need, so using gmp for now.
	function powMod($base, $exponent, $modulus) {
		if (function_exists('gmp_powm')) {
			return gmp_powm($base, $exponent, $modulus);
		} else if (function_exists('bcpowmod')) {
			return bcpowmod($base, $exponent, $modulus);
		} else {
			if ($modulus == 1) { return 0; }

			$result = 1;
			$base = $base % $modulus;

			while ($exponent > 0) {
				if ($exponent % 2 == 1) {
					$result = ($result * $base) % $modulus;
				}
				$exponent = $exponent >> 1;
				$base = ($base * $base) % $modulus;
			}

			return $result;
		}
	}

	function inv($n, $len) {
		if (function_exists('gmp_invert')) {
			return gmp_invert($n, $len);
		} else {
			return powMod($n, $len - 2, $len);
		}
	}

	[$offset_diff, $increment_mul, $deckLen] = shuffleDeckSmart(119315717514047, $input);

	$increment = powMod($increment_mul, 101741582076661, $deckLen);
	$offset = $offset_diff * (1 - $increment) * inv(1 - $increment_mul, $deckLen);

	$val = ($offset + ($increment * 2020)) % $deckLen;
	echo 'Part 2: ', $val, "\n";
