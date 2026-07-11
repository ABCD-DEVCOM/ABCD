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

    if ($action === 'activate') {
        $registry[$slug]['active'] = true;
    } elseif ($action === 'deactivate') {
        $registry[$slug]['active'] = false;
    } elseif ($action === 'delete') {
        unset($registry[$slug]);
        // Safely delete the plugin directory
        $pluginPath = $pluginsDir . '/' . $slug;
        if (is_dir($pluginPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($pluginPath);
        }
    }

    // Save updated registry
    file_put_contents($registryFile, json_encode($registry, JSON_PRETTY_PRINT));

    // Redirect with safe query parameters instead of hardcoded English text
    header("Location: plugins_manager.php?msg=success&slug=" . urlencode($slug));
    exit;
}

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

// Load Registry to check active status
$registry = file_exists($registryFile) ? json_decode(file_get_contents($registryFile), true) : [];

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
                                            <button type="submit" name="action" value="activate" class="bt bt-blue" style="color: green;">
                                                <i class="fas fa-play"></i> <?php echo $msgstr['plugin_activate'] ?? 'Activate'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if (!$isActive): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo addslashes($msgstr['plugin_confirm_delete'] ?? 'WARNING: This will completely delete the plugin files. Proceed?'); ?>');">
                                            <input type="hidden" name="plugin_slug" value="<?php echo $slug; ?>">
                                            <button type="submit" name="action" value="delete" class="bt bt-red" style="color: red; margin-left: 5px;">
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
        </div>
    </div>

    <?php include("../common/footer.php"); ?>
</body>

</html>