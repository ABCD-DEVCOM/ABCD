<?php

/**
 * Name: plugin.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: ABCD Plugin Router (Front Controller)
 * This file handles the routing of requests to individual plugins.
 * 
 * * @package ABCD_Core_Settings
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

// 1. Load the ABCD core configuration and plugin loaders
require_once __DIR__ . '/central/config_inc_check.php';
require_once __DIR__ . '/central/config.php';

// 2. Retrieve and sanitize the plugin ID from the rewrite rule
$pluginId = $_GET['id'] ?? '';
$pluginId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $pluginId);

if (empty($pluginId)) {
    header("HTTP/1.1 400 Bad Request");
    die("<h1>400 - Bad Request</h1><p>Required parameter 'id' (Plugin ID) is missing.</p>");
}

// 3. Security: Check if the plugin is registered and active in the central registry
$registryPath = ABCD_CONTENT_PATH . '/plugins/plugins.json';
$isActive = false;

if (file_exists($registryPath)) {
    $plugins = json_decode(file_get_contents($registryPath), true);
    if (isset($plugins[$pluginId]) && ($plugins[$pluginId]['active'] === true)) {
        $isActive = true;
    }
}

if (!$isActive) {
    header("HTTP/1.1 404 Not Found");
    die("<h1>404 - Plugin Not Found</h1><p>The requested plugin '{$pluginId}' does not exist or is currently disabled.</p>");
}

// 4. Dispatch the request to the plugin's public entry point
$pluginFrontEntry = ABCD_CONTENT_PATH . "/plugins/{$pluginId}/index.php";

if (file_exists($pluginFrontEntry)) {
    require_once $pluginFrontEntry;
} else {
    header("HTTP/1.1 404 Not Found");
    die("<h1>404 - Interface Unavailable</h1><p>The plugin '{$pluginId}' is active but lacks a valid public index.php entry point.</p>");
}
