<?php
/*
 * @program:   ABCD - ABCD-Central
 * @file:      autoincrement.php
 * @desc:      Calculate the next number to be assigned in the autoincrement field
 *             [Refactored with atomic flock() to prevent Race Conditions]
 */

$cn = "";
$archivo = $db_path . $arrHttp["base"] . "/data/control_number.cn";

// Ensures that the file exists.
if (!file_exists($archivo)) {
	file_put_contents($archivo, "0");
}

// Opens the file in c+ mode (Read and Write, preserving content, creating if it doesn't exist)
$fp = fopen($archivo, "c+");

// Tries to obtain an exclusive lock atomically
if ($fp && flock($fp, LOCK_EX)) {

	// Read the current issue
	$cn = stream_get_contents($fp);
	$cn = intval(trim($cn)) + 1;

	// Security backup
	copy($archivo, $db_path . $arrHttp["base"] . "/data/control_number.bak");

	// Clear the file, reset the pointer to the beginning, and write the new number
	ftruncate($fp, 0);
	rewind($fp);
	fwrite($fp, $cn);

	// Unlock
	flock($fp, LOCK_UN);
} else {
	// Failed to obtain the exclusive lock
	$cn = false;
}

if ($fp) {
	fclose($fp);
}

// Format with zeros on the left if the max_cn_length variable is defined
if ($cn !== false && isset($max_cn_length) && $max_cn_length > 0) {
	$cn = str_pad($cn, $max_cn_length, '0', STR_PAD_LEFT);
}
