<?php

/**
 * Name: index.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Main entry point for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

// 1. Ensure ABCD core is loaded (this brings $msgstr, $lang, and $langManager)
// Adjust the relative path to config.php based on your actual setup
$config_path = realpath(__DIR__ . '/../../../central/config.php');
if (file_exists($config_path)) {
    require_once $config_path;
}

global $msgstr, $langManager, $lang;

// 2. Define plugin context
$plugin_dir = __DIR__;
$plugin_name = basename(__DIR__); // Should be 'odds'
$current_lang = $_SESSION['lang'] ?? $lang ?? 'en';

// 3. Load plugin-specific translations via LanguageManager
if (isset($langManager)) {
    // Load main interface messages (odds.tab uses key|value format)
    $plugin_msgs = $langManager->loadPluginTranslations($plugin_dir, $plugin_name, 'odds.tab', $current_lang);

    // Merge plugin messages into the global $msgstr array
    $msgstr = array_merge($msgstr ?? [], $plugin_msgs);
}

// 4. Proceed with your MVC routing
$action = $_REQUEST['action'] ?? 'form';

switch ($action) {
    case 'process':
        require_once __DIR__ . '/process.php';
        break;
    case 'success': 
        require_once __DIR__ . '/success.php';
        break;
    case 'ajax':
        require_once __DIR__ . '/ajax.php';
        break;
    case 'send_email':
        require_once __DIR__ . '/send_email.php';
        break;
    case 'form':
    default:
        require_once __DIR__ . '/form.php';
        break;
}
