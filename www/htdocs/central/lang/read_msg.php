<?php

declare(strict_types=1);

/**
 * Centralized Message Reader for ABCD
 * Loads translation files using a cascading fallback system.
 * * Hierarchy Priority:
 * 1. Base Language (en) in Central (Fallback to prevent blank screens)
 * 2. Selected Language in Central (Factory default translations)
 * 3. Selected Language in Content (User custom overrides)
 */

global $msg_tab, $msgstr, $charset, $ABCD_scripts_path;

// Ensure $msgstr is initialized as an array
if (!isset($msgstr) || !is_array($msgstr)) {
    $msgstr = [];
}

// If no specific file is requested by the parent script, abort processing
if (!isset($msg_tab) || empty($msg_tab)) {
    return;
}

// Define paths (fallback to directory traversal if constants are missing)
$centralPath = defined('ABCD_CENTRAL_PATH') ? ABCD_CENTRAL_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
$contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;

$currentLang = $_SESSION['lang'] ?? 'en';
$baseLang = 'en'; // Changed from '00' to 'en' per architectural decision
$systemCharset = $charset ?? 'ISO-8859-1';

if (!function_exists('loadTranslationFile')) {
    /**
     * Reads a .tab file and safely merges its contents into the target array.
     */
    function loadTranslationFile(string $filePath, array &$targetArray, string $targetCharset): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        // Read file ignoring newlines and empty lines for speed
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and invalid lines
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            // Split on the first '=' to allow equal signs in the translation text
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Legacy HTML entities encoding ported from the original script
            $value = str_replace('"', '&quot;', $value);
            $value = str_replace("'", '&apos;', $value);

            // Character set normalization
            if ($targetCharset === 'UTF-8' && !mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            }

            // Merge key into the global array. 
            // If the key already exists, it is intentionally overwritten (Cascade effect)
            $targetArray[$key] = $value;
        }
    }
}

// 1. BASE LOAD: Always load English first to guarantee 100% term coverage
loadTranslationFile($centralPath . "lang/{$baseLang}/{$msg_tab}", $msgstr, $systemCharset);

// 2. CORE LOAD: Load the selected factory language, overwriting English terms
if ($currentLang !== $baseLang) {
    loadTranslationFile($centralPath . "lang/{$currentLang}/{$msg_tab}", $msgstr, $systemCharset);
}

// 3. CUSTOM LOAD: Load user modifications from the 'content' directory, overwriting Core
loadTranslationFile($contentPath . "lang/{$currentLang}/{$msg_tab}", $msgstr, $systemCharset);
