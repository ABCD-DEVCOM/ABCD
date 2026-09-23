<?php
/*
** ABCD Migration Script
** Executed automatically by update_manager.php v4.2+
** 2025-12-08 fho4abcd Added error checks
*/

if (!defined('ABCD_UPDATE_MODE')) die("Direct access not allowed.");

// Ensure that the variable $dest exists, otherwise stop before giving a fatal error.
if (!isset($dest) || !is_array($dest)) {
    writeLog("ERROR: Variable \$dest not found in migration script.","error");
    checkLastError();
    return;
}

// Auxiliary function for log (if available)
function migrationLog($msg)
{
    if (function_exists('writeLog')) writeLog("MIGRATION: " . $msg);
}

migrationLog("Checking for structural changes...");

// ==================================================================
// NEW FOLDERS
// ==================================================================
// If this version contains new folders that the old Update Manager does not recognise,
// they will be added to its processing list here.
// ------------------------------------------------------------------

global $PARTIAL_UPDATE_SOURCES; // Important: access the global variable of the main script

// Add the NO ZIP paths of the new folders here
$new_sources = [
    'www/htdocs/admin',
    'www/htdocs/login.php'
];

foreach ($new_sources as $src) {
    // Adds to the array so that Update Manager copies the files immediately afterwards.
    $PARTIAL_UPDATE_SOURCES[] = $src;
    migrationLog("Injecting new source folder into update queue: $src");
}


// ==================================================================
// DELETING OBSOLETE FILES AND FOLDERS
// ==================================================================

// A) Delete specific files (unlink)

$files_to_delete = [
    $dest['htdocs'] . '/info.php'
];

foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        unlink($file);
	checkLastError();
        migrationLog("Deleted obsolete file: " . basename($file));
    }
}

// B) Delete entire FOLDERS (recursiveDelete)
$folders_to_delete = [
    $dest['htdocs'] . '/mysite',
    $dest['htdocs'] . '/isisws',
    $dest['htdocs'] . '/images'
];

foreach ($folders_to_delete as $folder) {
    if (is_dir($folder)) {
        // recursiveDelete is a native function of update_manager.php.
        recursiveDelete($folder);
	checkLastError();
        migrationLog("Deleted obsolete directory tree: " . basename($folder));
    }
}

// ==================================================================
// MIGRATE 'uploads' TO 'content/uploads' (v4.0+)
// ==================================================================
// Moving the user uploads folder into the new 'content' directory

$old_uploads_dir = $dest['htdocs'] . '/uploads';
$new_content_dir = $dest['htdocs'] . '/content';
$new_uploads_dir = $new_content_dir . '/uploads';

// Check if the old uploads folder exists in the root
if (is_dir($old_uploads_dir)) {
    migrationLog("Migrating 'uploads' directory to 'content/uploads'...");

    // Ensure the new content directory exists
    if (!is_dir($new_content_dir)) {
        mkdir($new_content_dir, 0755, true);
        checkLastError();
        migrationLog("Created new 'content' directory.");
    }

    // Copy all contents using the built-in function from update_manager.php
    recursiveCopy($old_uploads_dir, $new_uploads_dir);
    checkLastError();

    // Delete the old uploads directory to clean up the root
    recursiveDelete($old_uploads_dir);
    checkLastError();

    migrationLog("Migration of 'uploads' completed successfully.");
} else {
    migrationLog("No old 'uploads' directory found to migrate. Skipping.");
}

// ==================================================================
// ADD NEW FOLDERS TO PARTIAL UPDATE (Optional but recommended)
// ==================================================================
// Ensure that the new 'content' directory is recognized in future updates 
// if it needs to bring factory default files inside it.
if (!in_array('www/htdocs/content', $PARTIAL_UPDATE_SOURCES)) {
    $PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/content';
}

checkLastError(); // Ensure that errors in this script are shown with correct stacktrace
migrationLog("Migration tasks completed.");

// ==================================================================
// SMART CONFIG.PHP UPDATE (v4.0+)
// ==================================================================
// Migrates user-specific paths from old config.php into the new 
// config.php.template, ensuring new v4.0+ constants are preserved.

$old_config_path = $dest['htdocs'] . '/central/config.php';
$template_path   = $dest['htdocs'] . '/central/config.php.template';

if (file_exists($old_config_path) && file_exists($template_path)) {
    migrationLog("Starting smart migration of config.php...");

    $old_content      = file_get_contents($old_config_path);
    $template_content = file_get_contents($template_path);

    // Regex pattern to capture everything from $protocol to the end of $ABCD_scripts_path
    $pattern = '/(\$protocol\s*=\s*.*?\$ABCD_scripts_path\s*=\s*.*?;)/s';

    // 1. Extract the user's custom settings from the old config
    if (preg_match($pattern, $old_content, $matches)) {
        $user_custom_settings = $matches[1];

        // 2. Inject the extracted settings into the new template
        // IMPORTANTE: O uso do preg_replace_callback impede que o PHP "engula" as barras invertidas 
        // e os escapes do Windows (ex: "\\") durante a injeção do texto.
        $new_config_content = preg_replace_callback($pattern, function ($m) use ($user_custom_settings) {
            return $user_custom_settings;
        }, $template_content);

        if ($new_config_content !== null && $new_config_content !== $template_content) {

            // 3. Create a safety backup of the old config
            $backup_name = $old_config_path . '.bak_v3_' . date('Ymd_His');
            copy($old_config_path, $backup_name);
            checkLastError();
            migrationLog("Backed up old config.php to " . basename($backup_name));

            // 4. Overwrite config.php with the merged content
            file_put_contents($old_config_path, $new_config_content);
            checkLastError();
            migrationLog("Successfully updated config.php with user settings and v4.0+ variables.");
        } else {
            migrationLog("ERROR/WARNING: Failed to inject user settings into config.php.template. Regex replacement failed or matched nothing in the template.");
        }
    } else {
        migrationLog("WARNING: Could not locate the expected user settings block in the old config.php. Manual update required.");
    }
} else {
    migrationLog("Skipping config.php update: Old config or template not found.");
}