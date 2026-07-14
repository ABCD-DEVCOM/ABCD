<?php
/**
 * Universal Plugin Header for ODDS
 * 
 * Centralizes LanguageManager initialization and global variable setup.
 */

if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Define language
if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang'])) {
    $_SESSION['lang'] = trim($_REQUEST['lang']);
}
$lang = $_SESSION['lang'] ?? 'en';

$bridge = PluginBridge::getInstance();
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../../central'));
$contentPath = realpath($abcdPath . '/../content');
$pluginPath = realpath(__DIR__ . '/../');

// Initialize LanguageManager
require_once $abcdPath . '/common/LanguageManager.php';
$langManager = new \ABCD\Common\LanguageManager($abcdPath, $contentPath);

// Global msgstr array initialization
global $msgstr;
if (!is_array($msgstr)) $msgstr = [];

// Load and Merge Translations
// 1. Load general Admin messages (Core)
$admin_msgs = $langManager->loadTranslations('admin.tab', $lang);
$msgstr = array_merge($msgstr, $admin_msgs);

// 2. Load Plugin specific messages (odds.tab and odds_help_info.tab)
$plugin_msgs = $langManager->loadPluginTranslations($pluginPath, 'odds', 'odds.tab', $lang);
$help_msgs   = $langManager->loadPluginTranslations($pluginPath, 'odds', 'odds_help_info.tab', $lang);

$msgstr = array_merge($msgstr, $plugin_msgs, $help_msgs);
?>