<?php

/**
 * Name: plugins_manager.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: ABCD Plugin Manager
 * This file handles the management of plugins, including activation, deactivation, and deletion.
 *
 * @package ABCD_Core_Settings
 * @requires PHP 8.3+
 *
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 20260922 rogercgui Added plugin installation from remote repository with ZIP extraction and manifest detection.
 * 20261107 rogercgui Added ABCD core version compatibility checker and untested version warning.
 */

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    exit;
}

require_once("../config.php");
require_once("../common/get_post.php");

/**
 * Normalizes a version string (e.g., "v4.0.2" or ">= 4.1.0" becomes "4.0.2" / "4.1.0")
 */
function normalize_abcd_version(?string $version): string
{
    if ($version === null || trim($version) === '') {
        return '';
    }
    return preg_replace('/[^0-9\.]/', '', $version);
}

// 1. Load ABCD Version from root htdocs/version.php
$htdocsRoot = dirname(rtrim(ABCD_CONTENT_PATH, '/\\'));
$versionFile = $htdocsRoot . DIRECTORY_SEPARATOR . 'version.php';
if (file_exists($versionFile)) {
    require_once($versionFile);
}
$rawAbcdVersion = defined('ABCD_VERSION') ? ABCD_VERSION : '4.0.0';
$currentAbcdVersion = normalize_abcd_version($rawAbcdVersion) ?: '4.0.0';

// Load languages to populate $msgstr array
$lang = $_SESSION["lang"] ?? "en";
@include("../lang/admin.php");

// 1.5 Define Core Paths
$pluginsDir = rtrim(ABCD_CONTENT_PATH, '/\\') . '/plugins';
$registryFile = $pluginsDir . '/plugins.json';

// Ensure plugins directory exists
if (!is_dir($pluginsDir)) {
    mkdir($pluginsDir, 0775, true);
}

// 2. Scan for Installed Plugins (Discovery) - Loaded before POST to validate activation compatibility
$installedPlugins = [];
if (is_dir($pluginsDir)) {
    $scannedDirs = array_diff(scandir($pluginsDir), ['.', '..']);
    foreach ($scannedDirs as $dir) {
        $manifestPath = $pluginsDir . '/' . $dir . '/plugin.json';
        if (is_dir($pluginsDir . '/' . $dir) && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest && isset($manifest['slug'])) {
                $installedPlugins[$manifest['slug']] = $manifest;
            }
        }
    }
}

// 3. Handle POST Actions (Activate, Deactivate, Install, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['plugin_slug'])) {
    $action = $_POST['action'];
    $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['plugin_slug']);

    // Load current registry
    $registry = [];
    if (file_exists($registryFile)) {
        $registry = json_decode(file_get_contents($registryFile), true) ?? [];
    }

    if ($action === 'activate' || $action === 'deactivate') {
        // Server-side version check before activating
        if ($action === 'activate' && isset($installedPlugins[$slug])) {
            $reqVer = normalize_abcd_version($installedPlugins[$slug]['requires'] ?? '');
            if ($reqVer !== '' && version_compare($currentAbcdVersion, $reqVer, '<')) {
                $errDetail = ($msgstr['plugin_err_incompatible'] ?? 'This plugin is incompatible with your ABCD version. Required:') . ' ' . $reqVer . '+';
                header("Location: plugins_manager.php?msg=error&detail=" . urlencode($errDetail));
                exit;
            }
        }

        if (!isset($registry[$slug])) {
            $registry[$slug] = [];
        }
        $registry[$slug]['active'] = ($action === 'activate');

        $saved = @file_put_contents($registryFile, json_encode($registry, JSON_PRETTY_PRINT));

        if ($saved === false) {
            header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_permission'] ?? "Permission error: PHP could not write to the plugins.json file."));
            exit;
        }

        header("Location: plugins_manager.php?msg=success&slug=" . urlencode($slug));
        exit;
    } elseif ($action === 'install' && isset($_POST['download_url'])) {
        // Optional server-side check if required_version was passed via POST
        $postReqVer = normalize_abcd_version($_POST['requires_version'] ?? '');
        if ($postReqVer !== '' && version_compare($currentAbcdVersion, $postReqVer, '<')) {
            $errDetail = ($msgstr['plugin_err_incompatible'] ?? 'This plugin is incompatible with your ABCD version. Required:') . ' ' . $postReqVer . '+';
            header("Location: plugins_manager.php?msg=error&detail=" . urlencode($errDetail));
            exit;
        }

        $downloadUrl = $_POST['download_url'];
        $tempZip = sys_get_temp_dir() . '/' . $slug . '_' . time() . '.zip';

        // 1. Download the ZIP file
        $context = stream_context_create(['http' => ['header' => 'User-Agent: ABCD-Plugin-Manager/1.0']]);
        $zipData = @file_get_contents($downloadUrl, false, $context);

        if ($zipData === false) {
            header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_download'] ?? "Download failed. Check your internet connection or the URL."));
            exit;
        }
        file_put_contents($tempZip, $zipData);

        // 2. Extract to a temporary environment
        $zip = new ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            $tempExtractPath = $pluginsDir . '/_temp_' . $slug . '_' . time();
            mkdir($tempExtractPath, 0775, true);

            // Try forcing permission on the temporary folder before extracting
            @chmod($tempExtractPath, 0777);

            // Suppresses the warnings and checks whether the extraction has failed
            if (!@$zip->extractTo($tempExtractPath)) {
                $zip->close();
                @unlink($tempZip);
                header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_extract'] ?? "Permission error on server: PHP does not have write permissions to extract the files."));
                exit;
            }
            $zip->close();
            @unlink($tempZip);

            // 3. Treasure Hunt: Find the actual folder containing plugin.json
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempExtractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $manifestDir = null;
            foreach ($iterator as $file) {
                if (strtolower($file->getFilename()) === 'plugin.json') {
                    $manifestDir = $file->getPath();
                    break;
                }
            }

            if ($manifestDir) {
                // Verify version compatibility inside the extracted plugin.json before moving
                $extractedManifest = json_decode(file_get_contents($manifestDir . '/plugin.json'), true);
                $extractedReqVer = normalize_abcd_version($extractedManifest['requires'] ?? '');

                if ($extractedReqVer !== '' && version_compare($currentAbcdVersion, $extractedReqVer, '<')) {
                    $cleaner = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tempExtractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($cleaner as $file) {
                        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
                    }
                    @rmdir($tempExtractPath);

                    $errDetail = ($msgstr['plugin_err_incompatible'] ?? 'This plugin is incompatible with your ABCD version. Required:') . ' ' . $extractedReqVer . '+';
                    header("Location: plugins_manager.php?msg=error&detail=" . urlencode($errDetail));
                    exit;
                }

                $pluginPath = $pluginsDir . '/' . $slug;
                if (!is_dir($pluginPath)) {
                    mkdir($pluginPath, 0775, true);
                }

                // Move all files from the directory where the manifest was to the final folder
                $items = array_diff(scandir($manifestDir), ['.', '..']);
                foreach ($items as $item) {
                    rename($manifestDir . '/' . $item, $pluginPath . '/' . $item);
                }

                // 4. Temporary folder cleanup
                $cleaner = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempExtractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($cleaner as $file) {
                    $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
                }
                @rmdir($tempExtractPath);

                // 5. Register the new plugin in the plugins.json file
                $registry = file_exists($registryFile) ? (json_decode(file_get_contents($registryFile), true) ?? []) : [];
                if (!isset($registry[$slug])) {
                    $registry[$slug] = ['active' => false];
                    @file_put_contents($registryFile, json_encode($registry, JSON_PRETTY_PRINT));
                }

                header("Location: plugins_manager.php?msg=installed&slug=" . urlencode($slug));
                exit;
            } else {
                // Clear extraction and abort if manifest was not found
                $cleaner = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempExtractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($cleaner as $file) {
                    $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
                }
                @rmdir($tempExtractPath);

                header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_invalid_zip'] ?? "Invalid structure: plugin.json not found in the ZIP package."));
                exit;
            }
        } else {
            @unlink($tempZip);
            header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_extract'] ?? "Failed to extract the ZIP."));
            exit;
        }
    } elseif ($action === 'delete') {
        // Remove from the register
        unset($registry[$slug]);

        $pluginPath = $pluginsDir . '/' . $slug;
        $deletionError = false;

        if (is_dir($pluginPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                $path = $file->getPathname();
                if ($file->isDir()) {
                    @rmdir($path);
                } else {
                    // Attempts to force write permission (useful on Windows) before deleting
                    @chmod($path, 0777);
                    if (!@unlink($path)) {
                        $deletionError = true;
                    }
                }
            }

            if (!@rmdir($pluginPath)) {
                $deletionError = true;
            }
        }

        // Save updated registry
        file_put_contents($registryFile, json_encode($registry, JSON_PRETTY_PRINT));

        if ($deletionError) {
            header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_delete'] ?? "Some files could not be deleted. Please check if they are open in Windows."));
            exit;
        } else {
            header("Location: plugins_manager.php?msg=success&slug=" . urlencode($slug));
            exit;
        }
    }
}

// 4. Obtain the Remote Catalog (abcd-community.org)
$remoteCatalogUrl = 'https://abcd-community.org/api/plugins.json';
$remotePlugins = [];

// Use a short timeout (3 seconds) so that ABCD doesn't get stuck loading if the site is offline
$ctx = stream_context_create(['http' => ['timeout' => 3]]);
$catalogJson = @file_get_contents($remoteCatalogUrl, false, $ctx);

if ($catalogJson) {
    $catalogData = json_decode($catalogJson, true);
    if (isset($catalogData['plugins']) && is_array($catalogData['plugins'])) {
        $remotePlugins = $catalogData['plugins'];
    }
}

// Load Registry to check active status
$registry = file_exists($registryFile) ? json_decode(file_get_contents($registryFile), true) : [];

// Define the current language based on the ABCD session
$userLang = $_SESSION['lang'] ?? 'en';

/**
 * Function to process simple strings or multilingual objects with charset correction
 */
function get_plugin_text($field, $lang)
{
    global $charset, $msgstr;

    if (empty($field)) return $msgstr['plugin_no_desc'] ?? 'No description available.';

    $text = '';

    // 1. Extract the text
    if (is_string($field)) {
        $text = $field;
    } elseif (is_array($field)) {
        // Tries user language, falls back to English, or gets the first available
        $text = $field[$lang] ?? $field['en'] ?? reset($field);
    }

    if ($text === '') return '';

    // 2. Handle encoding
    // ABCD defines $charset (e.g., 'UTF-8' or 'ISO-8859-1'). If it doesn't exist, assume UTF-8.
    $target_charset = (isset($charset) && !empty($charset)) ? strtoupper(trim($charset)) : 'UTF-8';

    // If ABCD is running in ISO-8859-1 (or another), converts the UTF-8 text from JSON
    if ($target_charset !== 'UTF-8' && function_exists('mb_convert_encoding')) {
        $text = mb_convert_encoding($text, $target_charset, 'UTF-8');
    }

    // 3. Return safely escaped, informing htmlspecialchars of the charset
    return htmlspecialchars($text, ENT_QUOTES, $target_charset);
}

// 5. Render UI (ABCD Standard Layout)
include("../common/header.php");
?>

<body>
    <?php include("../common/institutional_info.php"); ?>

    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr['plugin_management'] ?? 'Plugin Management'; ?>
        </div>
        <div class="actions">
            <a href="conf_abcd.php" class="defaultButton backButton">
                <i class="fas fa-arrow-left"></i> <?php echo $msgstr['plugin_back'] ?? 'Back'; ?>
            </a>
        </div>
        <div class="spacer">&#160;</div>
    </div>

    <?php include "../common/inc_div-helper.php"; ?>

    <div class="middle form">
        <div class="formContent">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;"><?php echo $msgstr['plugin_installed'] ?? 'Installed Plugins'; ?></h3>
                <span style="font-size: 12px; color: #555; background: #e9ecef; padding: 4px 10px; border-radius: 4px;">
                    <strong>ABCD:</strong> v<?php echo htmlspecialchars($currentAbcdVersion); ?>
                </span>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo $msgstr['plugin_success'] ?? 'Success'; ?>:</strong>
                    <?php echo ($msgstr['plugin_action_success'] ?? 'Action completed successfully for') . " '" . htmlspecialchars($_GET['slug'] ?? '') . "'."; ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 15px; text-align: right;">
                <a href="plugin_install.php" class="bt bt-blue" style="font-size: 14px;">
                    <i class="fas fa-plus"></i> <?php echo $msgstr['plugin_add_new'] ?? 'Add New Plugin'; ?>
                </a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'installed'): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo $msgstr['plugin_success'] ?? 'Success'; ?>:</strong>
                    <?php echo ($msgstr['plugin_installed_success'] ?? 'Plugin installed successfully! You can now activate it.'); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'error'): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo $msgstr['plugin_error'] ?? 'Error'; ?>:</strong>
                    <?php echo htmlspecialchars($_GET['detail'] ?? ''); ?>
                </div>
            <?php endif; ?>

            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f1f1f1; border-bottom: 2px solid #ccc;">
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_name'] ?? 'Plugin Name'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_desc'] ?? 'Description'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_version'] ?? 'Version'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_status'] ?? 'Status'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_actions'] ?? 'Actions'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($installedPlugins)): ?>
                        <tr>
                            <td colspan="5" style="padding: 15px; text-align: center;"><?php echo $msgstr['plugin_no_installed'] ?? 'No plugins installed.'; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($installedPlugins as $slug => $plugin): ?>
                            <?php
                            $isActive = isset($registry[$slug]['active']) && $registry[$slug]['active'] === true;
                            $rowColor = $isActive ? '#f0fdf4' : '#ffffff';

                            $hasRequiresField = isset($plugin['requires']) && trim((string)$plugin['requires']) !== '';
                            $requiresVersion = $hasRequiresField ? normalize_abcd_version((string)$plugin['requires']) : '';
                            $isCompatible = !$hasRequiresField || version_compare($currentAbcdVersion, $requiresVersion, '>=');
                            ?>
                            <tr style="border-bottom: 1px solid #ddd; background-color: <?php echo $rowColor; ?>;">
                                <td style="padding: 10px;">
                                    <strong><?php echo get_plugin_text($plugin['name'] ?? ($msgstr['plugin_unknown'] ?? 'Unknown'), $userLang); ?></strong><br>
                                    <small style="color: #666;">(<?php echo htmlspecialchars($slug); ?>)</small>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo get_plugin_text($plugin['description'] ?? '', $userLang); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($plugin['version'] ?? '1.0'); ?>
                                    <?php if ($hasRequiresField): ?>
                                        <br><small style="color: #666;"><?php echo ($msgstr['plugin_req_version'] ?? 'Requires ABCD') . ' ' . htmlspecialchars($requiresVersion) . '+'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if ($isActive): ?>
                                        <span style="color: green; font-weight: bold;"><?php echo $msgstr['plugin_active'] ?? 'Active'; ?></span>
                                    <?php else: ?>
                                        <span style="color: gray;"><?php echo $msgstr['plugin_inactive'] ?? 'Inactive'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if (!$isCompatible): ?>
                                        <div style="color: #e74c3c; font-size: 11px; margin-bottom: 5px; font-weight: bold;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo ($msgstr['plugin_req_version'] ?? 'Requires ABCD') . ' ' . htmlspecialchars($requiresVersion) . '+'; ?>
                                        </div>
                                    <?php elseif (!$hasRequiresField): ?>
                                        <div style="color: #b7791f; font-size: 11px; margin-bottom: 5px;">
                                            <i class="fas fa-info-circle"></i>
                                            <?php echo $msgstr['plugin_untested_version'] ?? 'Untested with your version of ABCD'; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isActive && !empty($plugin['settings_page'])): ?>
                                        <a href="plugin_admin.php?plugin=<?php echo urlencode($slug); ?>" class="bt bt-default" style="color: #0984e3; margin-right: 5px;">
                                            <i class="fas fa-cog"></i> <?php echo $msgstr['plugin_settings'] ?? 'Settings'; ?>
                                        </a>
                                    <?php endif; ?>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(addslashes($msgstr['plugin_confirm_status'] ?? 'Are you sure you want to change the status?')); ?>');">
                                        <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                        <?php if ($isActive): ?>
                                            <button type="submit" name="action" value="deactivate" class="bt bt-default" style="color: orange;">
                                                <i class="fas fa-pause"></i> <?php echo $msgstr['plugin_deactivate'] ?? 'Deactivate'; ?>
                                            </button>
                                        <?php elseif ($isCompatible): ?>
                                            <button type="submit" name="action" value="activate" class="bt bt-blue">
                                                <i class="fas fa-play"></i> <?php echo $msgstr['plugin_activate'] ?? 'Activate'; ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="bt bt-default" disabled style="opacity: 0.5; cursor: not-allowed;">
                                                <i class="fas fa-ban"></i> <?php echo $msgstr['plugin_incompatible'] ?? 'Incompatible'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if (!$isActive): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(addslashes($msgstr['plugin_confirm_delete'] ?? 'WARNING: This will completely delete the plugin files. Proceed?')); ?>');">
                                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                            <button type="submit" name="action" value="delete" class="bt bt-red" style="margin-left: 5px;">
                                                <i class="fas fa-trash"></i> <?php echo $msgstr['plugin_delete'] ?? 'Delete'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 style="margin-top: 40px;"><?php echo $msgstr['plugin_repository'] ?? 'ABCD Official Repository'; ?></h3>

            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #ccc;">
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_name'] ?? 'Plugin Name'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_desc'] ?? 'Description'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_available_version'] ?? 'Available Version'; ?></th>
                        <th style="padding: 10px;"><?php echo $msgstr['plugin_actions'] ?? 'Actions'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($remotePlugins)): ?>
                        <tr>
                            <td colspan="4" style="padding: 15px; text-align: center;"><?php echo $msgstr['plugin_repo_error'] ?? 'We were unable to connect to the repository, or there are no plugins available.'; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($remotePlugins as $remoteSlug => $remotePlugin): ?>
                            <?php
                            $isInstalled = array_key_exists($remoteSlug, $installedPlugins);
                            $localVersion = $isInstalled ? ($installedPlugins[$remoteSlug]['version'] ?? '0.0.0') : null;
                            $canUpdate = $isInstalled && version_compare($remotePlugin['version'], $localVersion, '>');

                            $hasRemoteRequires = isset($remotePlugin['requires']) && trim((string)$remotePlugin['requires']) !== '';
                            $remoteRequiresVersion = $hasRemoteRequires ? normalize_abcd_version((string)$remotePlugin['requires']) : '';
                            $isRemoteCompatible = !$hasRemoteRequires || version_compare($currentAbcdVersion, $remoteRequiresVersion, '>=');
                            ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;">
                                    <strong><?php echo get_plugin_text($remotePlugin['name'] ?? $remoteSlug, $userLang); ?></strong><br>
                                    <small style="color: #666;">
                                        <?php echo $msgstr['plugin_by'] ?? 'By'; ?>
                                        <?php
                                        if (!empty($remotePlugin['author'])) {
                                            echo get_plugin_text($remotePlugin['author'], $userLang);
                                        } else {
                                            echo get_plugin_text($msgstr['plugin_community'] ?? 'Community', $userLang);
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo get_plugin_text($remotePlugin['description'] ?? null, $userLang); ?>

                                    <?php if (!empty($remotePlugin['url'])): ?>
                                        <div style="margin-top: 8px;">
                                            <a href="<?php echo htmlspecialchars($remotePlugin['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="font-size: 11px; color: #0984e3; text-decoration: none; font-weight: 500;">
                                                <i class="fas fa-external-link-alt"></i> <?php echo $msgstr['plugin_more_info'] ?? 'More info'; ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($remotePlugin['version']); ?>
                                    <?php if ($hasRemoteRequires): ?>
                                        <br><small style="color: #666;"><?php echo ($msgstr['plugin_req_version'] ?? 'Requires ABCD') . ' ' . htmlspecialchars($remoteRequiresVersion) . '+'; ?></small>
                                    <?php endif; ?>
                                    <?php if ($isInstalled && !$canUpdate): ?>
                                        <br><span style="font-size: 11px; color: green;"><i class="fas fa-check-circle"></i> <?php echo $msgstr['plugin_updated'] ?? 'Updated'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if (!$isRemoteCompatible): ?>
                                        <div style="color: #e74c3c; font-size: 11px; margin-bottom: 5px; font-weight: bold;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo ($msgstr['plugin_req_version'] ?? 'Requires ABCD') . ' ' . htmlspecialchars($remoteRequiresVersion) . '+'; ?>
                                        </div>
                                        <button type="button" class="bt bt-default" disabled style="opacity: 0.5; font-size: 12px; cursor: not-allowed;">
                                            <i class="fas fa-ban"></i> <?php echo $msgstr['plugin_incompatible'] ?? 'Incompatible'; ?>
                                        </button>
                                    <?php else: ?>
                                        <?php if (!$hasRemoteRequires): ?>
                                            <div style="color: #b7791f; font-size: 11px; margin-bottom: 5px;">
                                                <i class="fas fa-info-circle"></i>
                                                <?php echo $msgstr['plugin_untested_version'] ?? 'Untested with your version of ABCD'; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!$isInstalled): ?>
                                            <form method="POST" style="display:inline;" onsubmit="var btn = this.querySelector('button'); btn.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> <?php echo addslashes($msgstr['plugin_installing'] ?? 'Installing...'); ?>'; btn.classList.add('bt-gray'); btn.classList.remove('bt-blue'); btn.style.cursor = 'wait'; setTimeout(() => { btn.disabled = true; }, 50);">
                                                <input type="hidden" name="action" value="install">
                                                <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($remoteSlug); ?>">
                                                <input type="hidden" name="download_url" value="<?php echo htmlspecialchars($remotePlugin['download_url']); ?>">
                                                <input type="hidden" name="requires_version" value="<?php echo htmlspecialchars($remoteRequiresVersion); ?>">
                                                <button type="submit" class="bt bt-blue" style="font-size: 12px;">
                                                    <i class="fas fa-download"></i> <?php echo $msgstr['plugin_install_btn'] ?? 'Install'; ?>
                                                </button>
                                            </form>
                                        <?php elseif ($canUpdate): ?>
                                            <form method="POST" style="display:inline;" onsubmit="var btn = this.querySelector('button'); btn.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> <?php echo addslashes($msgstr['plugin_updating'] ?? 'Updating...'); ?>'; btn.classList.add('bt-gray'); btn.classList.remove('bt-green'); btn.style.cursor = 'wait'; setTimeout(() => { btn.disabled = true; }, 50);">
                                                <input type="hidden" name="action" value="install">
                                                <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($remoteSlug); ?>">
                                                <input type="hidden" name="download_url" value="<?php echo htmlspecialchars($remotePlugin['download_url']); ?>">
                                                <input type="hidden" name="requires_version" value="<?php echo htmlspecialchars($remoteRequiresVersion); ?>">
                                                <button type="submit" class="bt bt-green" style="font-size: 12px;">
                                                    <i class="fas fa-sync"></i> <?php echo ($msgstr['plugin_update_to'] ?? 'Update to') . ' ' . htmlspecialchars($remotePlugin['version']); ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="bt bt-default" disabled style="opacity: 0.5; font-size: 12px;"><?php echo $msgstr['plugin_installed_btn'] ?? 'Installed'; ?></button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

    <?php include("../common/footer.php"); ?>