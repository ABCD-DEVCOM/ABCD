<?php
/**
 * Name: plugin-install.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: ABCD Plugin Installer
 * This file handles the installation of plugins by managing the upload, extraction, and validation of plugin ZIP files.
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

// Load languages
$lang = $_SESSION["lang"] ?? "en";
@include("../lang/admin.php");

$pluginsDir = ABCD_CONTENT_PATH . '/plugins';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip'])) {
    $zipFile = $_FILES['plugin_zip'];

    try {
        if ($zipFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($msgstr['plugin_err_upload'] ?? 'Error uploading file.');
        }

        $ext = strtolower(pathinfo($zipFile['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw new Exception($msgstr['plugin_err_invalid_zip'] ?? 'Only .zip files are allowed.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile['tmp_name']) === true) {
            
            // 1. Create a temporary extraction folder
            $tempDir = $pluginsDir . '/_temp_' . uniqid();
            if (!mkdir($tempDir, 0775, true)) {
                throw new Exception($msgstr['plugin_err_temp_dir'] ?? 'Failed to create temporary directory.');
            }

            // 2. Extract ZIP
            $zip->extractTo($tempDir);
            $zip->close();

            // 3. Smart Detection: Find where the plugin.json is
            // Handles both Zipped folders (e.g., odds/plugin.json) and Zipped contents (plugin.json at root)
            $manifestPath = '';
            $sourceDir = '';
            
            if (file_exists($tempDir . '/plugin.json')) {
                $manifestPath = $tempDir . '/plugin.json';
                $sourceDir = $tempDir;
            } else {
                // Check if it's inside a single subfolder
                $subDirs = array_filter(glob($tempDir . '/*'), 'is_dir');
                if (count($subDirs) === 1 && file_exists($subDirs[0] . '/plugin.json')) {
                    $manifestPath = $subDirs[0] . '/plugin.json';
                    $sourceDir = $subDirs[0];
                }
            }

            if (empty($manifestPath)) {
                // Cleanup temp dir
                abcd_delete_directory($tempDir);
                throw new Exception($msgstr['plugin_err_no_manifest_zip'] ?? 'Invalid plugin package: plugin.json not found.');
            }

            // 4. Read Manifest and get Slug
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $manifest['slug'] ?? '');

            if (empty($slug)) {
                abcd_delete_directory($tempDir);
                throw new Exception($msgstr['plugin_err_invalid_slug'] ?? 'Invalid manifest: missing or invalid plugin slug.');
            }

            // 5. Check if plugin already exists
            $targetDir = $pluginsDir . '/' . $slug;
            if (is_dir($targetDir)) {
                abcd_delete_directory($tempDir);
                throw new Exception(sprintf($msgstr['plugin_err_already_exists'] ?? 'Plugin %s already exists. Please delete it first before updating.', $slug));
            }

            // 6. Move to final destination
            if (!rename($sourceDir, $targetDir)) {
                abcd_delete_directory($tempDir);
                throw new Exception($msgstr['plugin_err_move'] ?? 'Failed to move plugin to its final destination.');
            }

            // 7. Cleanup remaining temp files (if any)
            if (is_dir($tempDir)) {
                abcd_delete_directory($tempDir);
            }

            header("Location: plugins_manager.php?msg=installed&slug=" . urlencode($slug));
            exit;
            
        } else {
            throw new Exception($msgstr['plugin_err_unzip'] ?? 'Failed to open ZIP file.');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/**
 * Helper function to recursively delete a directory
 */
function abcd_delete_directory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!abcd_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

// Render UI
include("../common/header.php");
?>
<body>
<?php include("../common/institutional_info.php"); ?>

<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr['plugin_management'] ?? 'Plugin Management'; ?> / 
        <?php echo $msgstr['plugin_install_new'] ?? 'Install New Plugin'; ?>
    </div>
    <div class="actions">
        <a href="plugins_manager.php" class="defaultButton backButton">
            <i class="fas fa-arrow-left"></i> <?php echo $msgstr['plugin_back_to_list'] ?? 'Back to Plugins'; ?>
        </a>
    </div>
    <div class="spacer">&#160;</div>
</div>

<div class="middle form">
    <div class="formContent">
        <h3><i class="fas fa-upload"></i> <?php echo $msgstr['plugin_upload_title'] ?? 'Upload Plugin (.zip)'; ?></h3>
        
        <?php if ($error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <strong><?php echo $msgstr['plugin_error'] ?? 'Error'; ?>:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="padding: 20px; border: 2px dashed #ccc; background-color: #f9f9f9; text-align: center; border-radius: 5px; margin-bottom: 20px;">
            <p><?php echo $msgstr['plugin_upload_instructions'] ?? 'If you have a plugin in a .zip format, you may install it by uploading it here.'; ?></p>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                <input type="file" name="plugin_zip" accept=".zip" required style="font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: white;">
                <br><br>
                <button type="submit" class="bt bt-blue">
                    <i class="fas fa-box-open"></i> <?php echo $msgstr['plugin_install_btn'] ?? 'Install Now'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<?php include("../common/footer.php"); ?>
</body>
</html>