<?php

/**
 * Name: plugins_manager.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: ABCD Plugin Manager
 * This file handles the management of plugins, including activation, deactivation, and deletion.
 * 
 * * @package ABCD_Core_Settings
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 20260922 rogercgui Added plugin installation from remote repository with ZIP extraction and manifest detection.
 * 
 * */

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    exit;
}

require_once("../config.php");
require_once("../common/get_post.php");

// Load languages to populate $msgstr array
$lang = $_SESSION["lang"] ?? "en";
@include("../lang/admin.php");

// 1. Define Core Paths
$pluginsDir = ABCD_CONTENT_PATH . '/plugins';
$registryFile = $pluginsDir . '/plugins.json';

// Ensure plugins directory exists
if (!is_dir($pluginsDir)) {
    mkdir($pluginsDir, 0775, true);
}

// 2. Handle POST Actions (Activate, Deactivate, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['plugin_slug'])) {
    $action = $_POST['action'];
    $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['plugin_slug']);

    // Load current registry
    $registry = [];
    if (file_exists($registryFile)) {
        $registry = json_decode(file_get_contents($registryFile), true) ?? [];
    }

    if ($action === 'activate' || $action === 'deactivate') {
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

            // Suppresses the warnings and checks whether the extraction has failed.
            if (!@$zip->extractTo($tempExtractPath)) {
                $zip->close();
                @unlink($tempZip);
                header("Location: plugins_manager.php?msg=error&detail=" . urlencode($msgstr['plugin_err_extract'] ?? "Erro de permissão no servidor Linux: O PHP não tem direitos de escrita para extrair os arquivos."));
                exit;
            }

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

                header("Location: plugins_manager.php?msg=installed&slug=" . urlencode($slug));
                exit;

                // 5. Register the new plugin in the plugins.json file
                $registry = file_exists($registryFile) ? (json_decode(file_get_contents($registryFile), true) ?? []) : [];
                if (!isset($registry[$slug])) {
                    $registry[$slug] = ['active' => false];
                    file_put_contents($registryFile, json_encode($registry, JSON_PRETTY_PRINT));
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
} // Fim do bloco if ($_SERVER['REQUEST_METHOD'] === 'POST' ...


// 3. Scan for Installed Plugins (Discovery)
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

// 3.5 Obtain the Remote Catalog (abcd-community.org)
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

// Function to process simple strings or multilingual objects with charset correction
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
    // We use ENT_QUOTES to prevent HTML attribute breakage
    return htmlspecialchars($text, ENT_QUOTES, $target_charset);
}

// 4. Render UI (ABCD Standard Layout)
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
            <h3><?php echo $msgstr['plugin_installed'] ?? 'Installed Plugins'; ?></h3>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo $msgstr['plugin_success'] ?? 'Success'; ?>:</strong>
                    <?php echo ($msgstr['plugin_action_success'] ?? 'Action completed successfully for') . " '" . htmlspecialchars($_GET['slug']) . "'."; ?>
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
                    <?php echo ($msgstr['plugin_installed_success'] ?? 'Plugin installed successfully! You can now activate it.') ?>
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
                            ?>
                            <tr style="border-bottom: 1px solid #ddd; background-color: <?php echo $rowColor; ?>;">
                                <td style="padding: 10px;">
                                    <strong><?php echo $plugin['name'] ?? ($msgstr['plugin_unknown'] ?? 'Unknown'); ?></strong><br>
                                    <small style="color: #666;">(<?php echo $slug; ?>)</small>
                                </td>
                                <td style="padding: 10px;"><?php echo $plugin['description'] ?? ''; ?></td>
                                <td style="padding: 10px;"><?php echo $plugin['version'] ?? '1.0'; ?></td>
                                <td style="padding: 10px;">
                                    <?php if ($isActive): ?>
                                        <span style="color: green; font-weight: bold;"><?php echo $msgstr['plugin_active'] ?? 'Active'; ?></span>
                                    <?php else: ?>
                                        <span style="color: gray;"><?php echo $msgstr['plugin_inactive'] ?? 'Inactive'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if ($isActive && !empty($plugin['settings_page'])): ?>
                                        <a href="plugin_admin.php?plugin=<?php echo urlencode($slug); ?>" class="bt bt-default" style="color: #0984e3; margin-right: 5px;">
                                            <i class="fas fa-cog"></i> <?php echo $msgstr['plugin_settings'] ?? 'Settings'; ?>
                                        </a>
                                    <?php endif; ?>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(addslashes($msgstr['plugin_confirm_status'] ?? 'Are you sure you want to change the status?')); ?>');">
                                        <input type="hidden" name="plugin_slug" value="<?php echo $slug; ?>">
                                        <?php if ($isActive): ?>
                                            <button type="submit" name="action" value="deactivate" class="bt bt-default" style="color: orange;">
                                                <i class="fas fa-pause"></i> <?php echo $msgstr['plugin_deactivate'] ?? 'Deactivate'; ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="activate" class="bt bt-blue">
                                                <i class="fas fa-play"></i> <?php echo $msgstr['plugin_activate'] ?? 'Activate'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if (!$isActive): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo addslashes($msgstr['plugin_confirm_delete'] ?? 'WARNING: This will completely delete the plugin files. Proceed?'); ?>');">
                                            <input type="hidden" name="plugin_slug" value="<?php echo $slug; ?>">
                                            <button type="submit" name="action" value="delete" class="bt bt-red" style=" margin-left: 5px;">
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

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'error'): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo $msgstr['plugin_error'] ?? 'Error'; ?>:</strong>
                    <?php echo htmlspecialchars($_GET['detail']); ?>
                </div>
            <?php endif; ?>

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
                            ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;">
                                    <strong><?php echo get_plugin_text($remotePlugin['name'] ?? $remoteSlug, $userLang); ?></strong><br>
                                    <small style="color: #666;">
                                        <?php echo $msgstr['plugin_by'] ?? 'Por'; ?>
                                        <?php
                                        if (!empty($remotePlugin['author'])) {
                                            echo get_plugin_text($remotePlugin['author'], $userLang);
                                        } else {
                                            // Ensures that the community’s default string also passes through the charset filter
                                            echo get_plugin_text($msgstr['plugin_community'] ?? 'Comunidade', $userLang);
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
                                    <?php if ($isInstalled && !$canUpdate): ?>
                                        <br><span style="font-size: 11px; color: green;"><i class="fas fa-check-circle"></i> <?php echo $msgstr['plugin_updated'] ?? 'Updated'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if (!$isInstalled): ?>
                                        <form method="POST" style="display:inline;" onsubmit="
                                        var btn = this.querySelector('button'); btn.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> <?php echo addslashes($msgstr['plugin_installing'] ?? 'Installing...'); ?>'; btn.classList.add('bt-gray'); btn.classList.remove('bt-blue'); btn.style.cursor = 'wait'; setTimeout(() => { btn.disabled = true; }, 50);">
                                            <input type="hidden" name="action" value="install">
                                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($remoteSlug); ?>">
                                            <input type="hidden" name="download_url" value="<?php echo htmlspecialchars($remotePlugin['download_url']); ?>">
                                            <button type="submit" class="bt bt-blue" style="font-size: 12px;">
                                                <i class="fas fa-download"></i> <?php echo $msgstr['plugin_install_btn'] ?? 'Install'; ?>
                                            </button>
                                        </form>
                                    <?php elseif ($canUpdate): ?>
                                        <form method="POST" style="display:inline;" onsubmit="var btn = this.querySelector('button'); btn.innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> <?php echo addslashes($msgstr['plugin_updating'] ?? 'Updating...'); ?>'; btn.classList.add('bt-gray'); btn.classList.remove('bt-green'); btn.style.cursor = 'wait'; setTimeout(() => { btn.disabled = true; }, 50);">
                                            <input type="hidden" name="action" value="install">
                                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($remoteSlug); ?>">
                                            <input type="hidden" name="download_url" value="<?php echo htmlspecialchars($remotePlugin['download_url']); ?>">
                                            <button type="submit" class="bt bt-green" style="font-size: 12px;">
                                                <i class="fas fa-sync"></i> <?php echo ($msgstr['plugin_update_to'] ?? 'Update to') . ' ' . htmlspecialchars($remotePlugin['version']); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="bt bt-default" disabled style="opacity: 0.5; font-size: 12px;"><?php echo $msgstr['plugin_installed_btn'] ?? 'Installed'; ?></button>
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