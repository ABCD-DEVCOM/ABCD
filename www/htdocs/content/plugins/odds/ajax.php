<?php

/**
 * Name: ajax.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: AJAX handler for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

// Log in if the plugin's router hasn't already done so
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$bridge = PluginBridge::getInstance();

// Retrieves the language sent by JS (Step 1), with a safe fallback
$lang = $_REQUEST['lang'] ?? $_SESSION['lang'] ?? 'en';

// Ensures the global $db_path for backward compatibility with older databases
global $db_path;
$db_path = rtrim($bridge->get('db_path'), '/\\') . DIRECTORY_SEPARATOR;

require_once __DIR__ . '/inc_odds_show_controls.php';

$optionalInputs = "";
$variableFields = $_REQUEST;

$result = read_odds_show_controls($lang, $_GET['level'] ?? '', $variableFields, $optionalInputs);

if (!$result && empty($optionalInputs)) {
    echo "<div class='alert alert-danger'>Error loading dynamic fields configuration.</div>";
} else {
    echo $optionalInputs;
}
exit;