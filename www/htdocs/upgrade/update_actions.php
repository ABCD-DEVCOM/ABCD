<?php
/*
** ABCD Migration Script
** Executed automatically by update_manager.php v4.2+
*/

if (!defined('ABCD_UPDATE_MODE')) die("Direct access not allowed.");

global $dest, $PARTIAL_UPDATE_SOURCES, $msgstr;

// --- PHP Compatibility Check (v4.0+) ---
// Deve ser rodado antes de qualquer outra ação para evitar quebrar o sistema
// caso o servidor esteja usando uma versão antiga.
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $msg_php_error_pt = "O ABCD v4 exige a versão PHP 8.1 ou superior para funcionar corretamente (sua versão atual é " . PHP_VERSION . "). Atualize seu servidor antes de prosseguir com a instalação.";
    $msg_php_error_en = "ABCD v4 requires PHP 8.1 or higher to run properly (your current version is " . PHP_VERSION . "). Please update your server before proceeding with the installation.";

    $display_msg = isset($msgstr["mig_php_version_error"]) ? $msgstr["mig_php_version_error"] : "{$msg_php_error_pt}<br><br>{$msg_php_error_en}";

    die("
    <div style='font-family: sans-serif; background: #dc3545; color: white; padding: 20px; max-width: 600px; margin: 50px auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <h2 style='margin-top: 0;'>Update Blocked</h2>
        <p><strong>{$display_msg}</strong></p>
    </div>");
}

// Ensure that the variable $dest exists, otherwise stop before giving a fatal error.
if (!isset($dest) || !is_array($dest)) {
    writeLog($msgstr["mig_err_dest"] ?? "ERROR: Variable \$dest not found in migration script.", "error");
    checkLastError();
    return;
}

// Auxiliary function for log (if available)
function migrationLog(string $msg, string $type = "info"): void
{
    if (function_exists('writeLog')) writeLog("MIGRATION: " . $msg, $type);
}

// Advanced robust delete for folders (recursive)
function robustDelete(string $dir): void
{
    if (!file_exists($dir)) return;
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                    robustDelete($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    @unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        @rmdir($dir);
    } else {
        @unlink($dir);
    }
}

migrationLog($msgstr["mig_checking"] ?? "Checking for structural changes...");

// ==================================================================
// CASE 1: DELETING OBSOLETE FILES AND FOLDERS
// ==================================================================
$files_to_delete = [
    $dest['htdocs'] . '/info.php'
];

foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        @unlink($file);
        migrationLog(sprintf($msgstr["mig_deleted_file"] ?? "Deleted obsolete file: %s", basename($file)));
    }
}

$folders_to_delete = [
    $dest['htdocs'] . '/odds'
];

foreach ($folders_to_delete as $folder) {
    if (is_dir($folder)) {
        robustDelete($folder);
        migrationLog(sprintf($msgstr["mig_deleted_dir"] ?? "Deleted obsolete directory tree: %s", basename($folder)));
    }
}


// ==================================================================
// CASE 2: SMART INJECTION OF NEW BASES (Spectrum only)
// ==================================================================
global $source_root, $os_in_gitname;
migrationLog($msgstr["mig_checking_bases"] ?? "Checking for missing files in bases directory...");

$bases_src_dir = $source_root . '/www/bases-examples_' . $os_in_gitname;
$bases_dst_dir = rtrim($dest['bases'], '/\\');

$safeCopyNewFiles = function (string $src, string $dst) use (&$safeCopyNewFiles): void {
    if (!is_dir($dst)) {
        @mkdir($dst, 0755, true);
    }
    $iterator = new DirectoryIterator($src);
    foreach ($iterator as $item) {
        if ($item->isDot()) continue;
        $srcPath = $item->getPathname();
        $dstPath = $dst . '/' . $item->getFilename();

        if ($item->isDir()) {
            $safeCopyNewFiles($srcPath, $dstPath);
        } elseif (!file_exists($dstPath)) {
            @copy($srcPath, $dstPath);
        }
    }
};

// Inject 'spectrum' database
$spectrum_src = $bases_src_dir . '/spectrum';
$spectrum_dst = $bases_dst_dir . '/spectrum';
if (is_dir($spectrum_src)) {
    $safeCopyNewFiles($spectrum_src, $spectrum_dst);
    migrationLog($msgstr["mig_spectrum_copied"] ?? "Spectrum database structure injected safely.");
}

// Update bases.dat safely
$bases_dat_path = $bases_dst_dir . '/bases.dat';
if (file_exists($bases_dat_path)) {
    $bases_content = file($bases_dat_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $has_spectrum = false;
    $insert_index = -1;

    foreach ($bases_content as $index => $line) {
        $parts = explode('|', trim($line));
        $base_name = strtolower(trim($parts[0]));
        if ($base_name === 'spectrum') {
            $has_spectrum = true;
            break;
        }
        // Identify where system modules start to inject Spectrum right above them
        if (in_array($base_name, ['acces', 'users', 'logtrans', 'providers'])) {
            if ($insert_index === -1) {
                $insert_index = $index;
            }
        }
    }

    if (!$has_spectrum) {
        $new_line = "spectrum|SPECTRUM - Museum Management Standard";
        if ($insert_index !== -1) {
            array_splice($bases_content, $insert_index, 0, $new_line);
        } else {
            $bases_content[] = $new_line;
        }
        @file_put_contents($bases_dat_path, implode(PHP_EOL, $bases_content) . PHP_EOL);
        migrationLog($msgstr["mig_bases_dat_updated"] ?? "Updated bases.dat with spectrum entry.");
    }
}

// ==================================================================
// CASE 3: MIGRATE NEW CORE FOLDERS EXPLICITLY (plugins, abcd-api, content)
// ==================================================================
// Instead of relying on the Update Manager loop, we force copy them here 
// to ensure they are available before the config.php update runs.
$explicit_copies = [
    'www/htdocs/content'   => $dest['htdocs'] . '/content',
    'www/htdocs/abcd-api'  => $dest['htdocs'] . '/abcd-api',
    'www/htdocs/plugins.php' => $dest['htdocs'] . '/plugins.php'
];

foreach ($explicit_copies as $src_rel => $dst_abs) {
    $full_src = $source_root . '/' . $src_rel;
    if (file_exists($full_src)) {
        if (is_dir($full_src)) {
            recursiveCopy($full_src, $dst_abs);
            migrationLog(sprintf($msgstr["mig_injecting_src"] ?? "Injected new source folder: %s", basename($dst_abs)));
        } else {
            @copy($full_src, $dst_abs);
            migrationLog(sprintf($msgstr["mig_injecting_src"] ?? "Injected new source file: %s", basename($dst_abs)));
        }
    }
}

// ==================================================================
// CASE 4: SMART CONFIG.PHP UPDATE (v4.0+)
// ==================================================================
$old_config_path = $dest['htdocs'] . '/central/config.php';
$template_path   = $dest['htdocs'] . '/central/config.php.template';

if (file_exists($old_config_path) && file_exists($template_path)) {
    migrationLog($msgstr["mig_config_start"] ?? "Starting smart migration of config.php...");

    $old_content      = file_get_contents($old_config_path);
    $template_content = file_get_contents($template_path);

    // Captura estritamente o bloco OS-dependent e a definição do $xWxis
    $pattern = '/(\/\/\s*Set operation system depending variables.*?\$xWxis\s*=\s*.*?;)/s';

    if (preg_match($pattern, $old_content, $matches)) {
        $user_custom_settings = $matches[1];

        $new_config_content = preg_replace_callback($pattern, function ($m) use ($user_custom_settings) {
            return $user_custom_settings;
        }, $template_content);

        if ($new_config_content !== null && $new_config_content !== $template_content) {
            $backup_name = $old_config_path . '.bak_v3_' . date('Ymd_His');
            @copy($old_config_path, $backup_name);
            migrationLog(sprintf($msgstr["mig_config_backup"] ?? "Backed up old config.php to %s", basename($backup_name)));

            @file_put_contents($old_config_path, $new_config_content);
            migrationLog($msgstr["mig_config_success"] ?? "Successfully updated config.php with user settings and v4.0+ variables.");
        } else {
            migrationLog($msgstr["mig_config_err_regex"] ?? "Failed to inject user settings into config.php.template.", "error");
        }
    } else {
        migrationLog($msgstr["mig_config_err_match"] ?? "Could not locate the expected user settings block in the old config.php.", "warning");
    }
} else {
    migrationLog($msgstr["mig_config_skip"] ?? "Skipping config.php update: Old config or template not found.");
}

checkLastError();
migrationLog($msgstr["mig_completed"] ?? "Migration tasks completed.");
