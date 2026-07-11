<?php

/**
 * Name: inc_odds_info.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Information display for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

function load_info(string $lang): array
{
    global $msgstr;

    // Ensure $msgstr is an array to prevent PHP 8+ warnings
    if (!is_array($msgstr)) {
        $msgstr = [];
    }

    $helpfile = "odds_help_info.tab";
    $help_read = [];

    // 1. Primary path: Inside the plugin's selected language folder
    $plugin_lang_file = __DIR__ . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $helpfile;

    // 2. Fallback path: Inside the plugin's English folder
    $plugin_fallback_file = __DIR__ . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . "en" . DIRECTORY_SEPARATOR . $helpfile;

    $file_to_read = false;

    // Determine which file to read
    if (file_exists($plugin_lang_file)) {
        $file_to_read = $plugin_lang_file;
    } elseif (file_exists($plugin_fallback_file)) {
        $file_to_read = $plugin_fallback_file;
    }

    // Read and parse the file
    if ($file_to_read !== false) {
        $lines = file($file_to_read, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            $help_read = array_map('trim', $lines);
        }
    }

    // Handle error if file is completely missing or empty
    if (empty($help_read)) {
        $error_msg = $msgstr["odds_nohelp"] ?? "No valid help file found.";
        echo "<div style='color:red;font-weight: bolder'>{$error_msg} ({$helpfile})</div><br>";
    }

    return $help_read;
}
