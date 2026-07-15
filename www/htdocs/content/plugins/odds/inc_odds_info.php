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

    // Use PluginBridge to get correct absolute paths
    if (class_exists('PluginBridge')) {
        $bridge = PluginBridge::getInstance();
        $centralPath = rtrim($bridge->get('abcd_path', realpath(__DIR__ . '/../../../central')), '/\\');
    } else {
        // Fallback if PluginBridge is somehow not available
        $centralPath = realpath(__DIR__ . '/../../../central');
    }

    $contentPath = realpath($centralPath . '/../content');
    $pluginSlug = 'odds';

    // 1. Primary path: Content Vault (User Overrides)
    $cofreFile = "{$contentPath}/lang/{$lang}/{$helpfile}";

    // 2. Secondary path: Core Plugin translations (Factory default)
    $motorFile = "{$contentPath}/plugins/{$pluginSlug}/lang/{$lang}/{$helpfile}";

    // 3. Fallback path: Core Plugin English (Factory fallback)
    $motorFallback = "{$contentPath}/plugins/{$pluginSlug}/lang/en/{$helpfile}";

    $file_to_read = false;

    // Determine which file to read based on priority
    if (file_exists($cofreFile)) {
        $file_to_read = $cofreFile;
    } elseif (file_exists($motorFile)) {
        $file_to_read = $motorFile;
    } elseif (file_exists($motorFallback)) {
        $file_to_read = $motorFallback;
    }

    // Read and parse the file
    if ($file_to_read !== false) {
        $lines = file($file_to_read, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            // Remove BOM if present on the first line before trimming
            if (isset($lines[0])) {
                $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
            }
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