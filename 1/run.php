#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/../common/common.php');
	$input = getInputLines();

$required = 0;
foreach ($input as $mod) {
	$required += floor($mod / 3) - 2;
}

echo $required, "\n";
