#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

$required = 0;
foreach ($input as $mod) {
	$mass = $mod;
	while (true) {
		$mass = floor($mass / 3) - 2;
		if ($mass <= 0) { break; }
		$required += $mass;
	}
}

echo $required, "\n";
