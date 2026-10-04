<?php

/**
 * Name: AutoIncrementHelper.php
 * Description: Safely handles the generation and formatting of Auto Increment (AI) fields.
 * Ensures PHP 8.1+ strict typing compatibility and provides file locking for concurrent requests.
 * Handles empty or missing control_number.cn files gracefully.
 */

namespace ABCD\Common;

class AutoIncrementHelper
{
    /**
     * Retrieves the next auto-increment value and optionally formats it with leading zeros.
     * 
     * @param string $dbPath The base path to the databases.
     * @param string $base   The current database name.
     * @param int    $length The required string length (padding). Default is 0 (no padding).
     * @param bool   $commit If true, saves the new value to the control file. If false, only previews.
     * @return string The formatted next auto-increment value.
     */
    public static function getNextValue(string $dbPath, string $base, int $length = 0, bool $commit = false): string
    {
        $controlFile = rtrim($dbPath, '/\\') . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'control_number.cn';
        $currentValue = 0;

        // Ensure the data directory exists
        if (!is_dir(dirname($controlFile))) {
            mkdir(dirname($controlFile), 0755, true);
        }

        // Safely read the file, handling empty contents to prevent PHP 8.1+ TypeError
        if (file_exists($controlFile)) {
            $content = file_get_contents($controlFile);
            if ($content !== false && trim($content) !== '') {
                $currentValue = (int)trim($content);
            }
        }

        $nextValue = $currentValue + 1;

        // Reserve the number if requested
        if ($commit) {
            file_put_contents($controlFile, (string)$nextValue, LOCK_EX);
        }

        // Format with leading zeros based on FDT configuration (e.g. length 6 -> "000012")
        if ($length > 0) {
            return str_pad((string)$nextValue, $length, '0', STR_PAD_LEFT);
        }

        return (string)$nextValue;
    }
}
