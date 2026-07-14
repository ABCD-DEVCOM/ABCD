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

if (!empty($dbPath)) {
    $targetBaseDir = rtrim($dbPath, '/\\') . '/odds';
    $basesDatFile  = rtrim($dbPath, '/\\') . '/bases.dat';
    $sourceTemplate = __DIR__ . '/install/odds';

    // 1. Auto-installation logic for the ISIS database files
    if (!is_dir($targetBaseDir) && is_dir($sourceTemplate)) {
        mkdir($targetBaseDir, 0775, true);
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceTemplate, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $targetBaseDir . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0775, true);
                }
            } else {
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
    $lang = $bridge->get('lang', 'en');
    // The link safely redirects the librarian to the standard cataloguing tool for the odds database
    $menuHtml .= '<a href="/central/settings/plugin_admin.php?plugin=odds" class="menuButton utilsButton">';
	$menuHtml .= '<span><strong><?php echo $msgstr["configure_ODDS"]. " ABCD"?></strong></span>';
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

