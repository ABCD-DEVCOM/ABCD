<?php

/**
 * Name: plugin-bootstrap.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: This file is part of the ODDS plugin for the ABCD application. It serves as the bootstrap file for the plugin, handling auto-installation of necessary ISIS database files, setting up dependencies, and registering core hooks to integrate the plugin seamlessly into the ABCD system. The script ensures that the ODDS database is created if it does not exist and adds a link to the ODDS management interface in the central navigation menu.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');

// Retrieves the language from the URL (if available) and saves it in the session for subsequent screens
if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang'])) {
    $_SESSION['lang'] = trim($_REQUEST['lang']);
}
$lang = $_SESSION['lang'] ?? 'en';

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$pluginPath = realpath(__DIR__);

// 3. Força o carregamento do dicionário correto do Plugin
global $msgstr;
if (!is_array($msgstr)) {
    $msgstr = [];
}

require_once $abcdPath . '/common/LanguageManager.php';
$langManager = new \ABCD\Common\LanguageManager($abcdPath, $abcdPath . '/../content');
$plugin_msgs = $langManager->loadPluginTranslations($pluginPath, 'odds', 'odds.tab', $lang);
$msgstr = array_merge($msgstr, $plugin_msgs);


if (!empty($dbPath)) {
    $targetBaseDir = rtrim($dbPath, '/\\') . '/odds';
    $basesDatFile  = rtrim($dbPath, '/\\') . '/bases.dat';
    $sourceTemplate = __DIR__ . '/install/odds';

    // 1. Auto-installation logic for the ISIS database files
    if (!is_dir($targetBaseDir) && is_dir($sourceTemplate)) {
        mkdir($targetBaseDir, 0775, true);

        $targetParDir = rtrim($dbPath, '/\\') . DIRECTORY_SEPARATOR . 'par';
        if (!is_dir($targetParDir)) {
            mkdir($targetParDir, 0775, true);
        }

        // OS Detection to select the correct ISIS binary data folder
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $validDataFolder = $isWindows ? 'data-win' : 'data-lin';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceTemplate, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            $pathParts = explode(DIRECTORY_SEPARATOR, $subPath);
            $topFolder = $pathParts[0];

            // Ignore the data folder belonging to the incompatible OS
            if (($topFolder === 'data-win' && !$isWindows) || ($topFolder === 'data-lin' && $isWindows)) {
                continue;
            }

            // Route files to their respective physical destinations
            if ($topFolder === $validDataFolder) {
                $pathParts[0] = 'data';
                $targetSubPath = implode(DIRECTORY_SEPARATOR, $pathParts);
                $targetPath = $targetBaseDir . DIRECTORY_SEPARATOR . $targetSubPath;
            } elseif ($topFolder === 'par') {
                // Route parameter files directly to the global bases/par/ folder
                array_shift($pathParts);
                if (empty($pathParts)) continue; // Skip the 'par' directory itself
                $targetPath = $targetParDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $pathParts);
            } else {
                // Standard folders (def, pfts, etc.) go to bases/odds/
                $targetPath = $targetBaseDir . DIRECTORY_SEPARATOR . $subPath;
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0775, true);
                }
            } else {
                $parentDir = dirname($targetPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0775, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }

        // 2. Safely register the database inside bases.dat
        if (file_exists($basesDatFile)) {
            $basesDatContent = file_get_contents($basesDatFile);
            if (strpos($basesDatContent, 'odds|') === false) {
                // Ensure correct line-ending handling for the flat file append
                $lineSeparator = (str_ends_with($basesDatContent, "\n") || str_ends_with($basesDatContent, "\r")) ? "" : PHP_EOL;
                $entry = $lineSeparator . "odds|ODDS - Online Document Delivery Service" . PHP_EOL;
                file_put_contents($basesDatFile, $entry, FILE_APPEND | LOCK_EX);
            }
        }
    }
}

// 3. Register Core Hooks
// Inject ODDS Management link into the central navigation menu
abcd_add_hook('central_menu', function(string $menuHtml) use ($bridge): string {
    global $msgstr;
    $lang = $bridge->get('lang', 'en');
    // The link safely redirects the librarian to the standard cataloguing tool for the odds database
    $menuHtml .= '<a href="/central/settings/plugin_admin.php?plugin=odds" class="menuButton utilsButton">';
	$menuHtml .= '<span><strong>'.$msgstr["configure_ODDS"]. " ABCD".'</strong></span>';
	$menuHtml .= '</a>';

    return $menuHtml;
});

/**
 * Register ODDS translation button in the main translation menu
 */
abcd_add_hook('abcd_translation_menu', function(string $menuHtml) use ($bridge): string {
    global $msgstr;
    $lang = $bridge->get('lang', 'en');
    $label = $msgstr["odds"] ?? 'ODDS';
    
    $menuHtml .= '<a href="../lang/translate.php?lang=' . urlencode($lang) . '&table=odds.tab&plugin=odds" class="menuButton moduleButton">';
    $menuHtml .= '<span><strong>' . htmlspecialchars($label) . '</strong></span>';
    $menuHtml .= '</a>';
    
    return $menuHtml;
});

/**
 * Register ODDS Help Information button in the main translation menu
 */
abcd_add_hook('abcd_translation_menu', function (string $menuHtml) use ($bridge): string {
    global $msgstr;
    $lang = $bridge->get('lang', 'en');
    $label = $msgstr["odds_help_info"] ?? 'ODDS Help Information';

    $menuHtml .= '<a href="../lang/translate.php?lang=' . urlencode($lang) . '&table=odds_help_info.tab&plugin=odds&type=textarea" class="menuButton moduleButton">';
    $menuHtml .= '<span><strong>' . htmlspecialchars($label) . '</strong></span>';
    $menuHtml .= '</a>';

    return $menuHtml;
});

/**
 * Register ODDS compare button in the translation menu
 */
abcd_add_hook('abcd_compare_translation_menu', function(string $menuHtml) use ($bridge): string {
    global $msgstr;
    $lang = $bridge->get('lang', 'en');
    $label = $msgstr["odds"] ?? 'ODDS';
    
    $menuHtml .= '<a href="../lang/compare_admin.php?lang=' . urlencode($lang) . '&table=odds.tab&plugin=odds" class="menuButton moduleButton">';
    $menuHtml .= '<span><strong>' . htmlspecialchars($label) . '</strong></span>';
    $menuHtml .= '</a>';
    
    return $menuHtml;
});

