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

$bridge = PluginBridge::getInstance();
$lang   = $_SESSION['lang'] ?? 'en';

// Importante: passamos o $lang para o gerador de controles
require_once __DIR__ . '/inc_odds_show_controls.php';

$optionalInputs = "";
$variableFields = $_REQUEST;

// Certifique-se de que read_odds_show_controls receba o $lang atual
$result = read_odds_show_controls($lang, $_GET['level'] ?? '', $variableFields, $optionalInputs);

if (!$result && empty($optionalInputs)) {
    echo "<div class='alert alert-danger'>Error loading configuration.</div>";
} else {
    echo $optionalInputs;
}
exit;
