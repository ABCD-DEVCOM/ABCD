<?php
/**
 * Name: plugin_admin.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: ABCD Plugin Admin Router
 * This file acts as a secure wrapper for plugin configuration pages, ensuring admin authentication, UI consistency, and core variable access. It validates the requested plugin, checks its activation status, and includes the plugin's settings page within a standardized ABCD interface.
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

// 1. Validate the requested plugin
$pluginSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['plugin'] ?? '');

if (empty($pluginSlug)) {
    die($msgstr['plugin_err_not_specified'] ?? "Error: Plugin not specified.");
}

// 2. Check if plugin is active and exists
$registryFile = ABCD_CONTENT_PATH . '/plugins/plugins.json';
$registry = file_exists($registryFile) ? json_decode(file_get_contents($registryFile), true) : [];

if (!isset($registry[$pluginSlug]) || $registry[$pluginSlug]['active'] !== true) {
    die($msgstr['plugin_err_not_active'] ?? "Error: Plugin is not active or does not exist.");
}

// 3. Read the manifest to find the settings page
$manifestPath = ABCD_CONTENT_PATH . "/plugins/{$pluginSlug}/plugin.json";
if (!file_exists($manifestPath)) {
    die($msgstr['plugin_err_no_manifest'] ?? "Error: Plugin manifest not found.");
}

$manifest = json_decode(file_get_contents($manifestPath), true);
$settingsScript = $manifest['settings_page'] ?? '';

if (empty($settingsScript)) {
    die($msgstr['plugin_err_no_settings'] ?? "Error: This plugin does not provide a settings page.");
}

$pluginSettingsFile = ABCD_CONTENT_PATH . "/plugins/{$pluginSlug}/" . $settingsScript;

if (!file_exists($pluginSettingsFile)) {
    // Uses sprintf to inject the filename into the translated string
    $errorMsg = $msgstr['plugin_err_no_settings_file'] ?? "Error: Settings file '%s' not found in the plugin directory.";
    die(sprintf($errorMsg, htmlspecialchars($settingsScript)));
}

// 4. Render the Core UI Wrapper
include("../common/header.php");
?>

<body>
    <?php include("../common/institutional_info.php"); ?>

    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr['plugin_management'] ?? 'Plugin Management'; ?> /
            <?php echo htmlspecialchars($manifest['name']); ?>
            <?php echo $msgstr['plugin_settings'] ?? 'Settings'; ?>
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
            <?php
            // We include the plugin's custom settings page here.
            // Because it is included inside this router, the plugin developer 
            // has native access to $msgstr, $db_path, and standard ABCD UI classes!
            require_once $pluginSettingsFile;
            ?>
        </div>
    </div>

    <?php include("../common/footer.php"); ?>
</body>

</html>