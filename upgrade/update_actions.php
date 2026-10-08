<?php
/*
** ABCD Migration Script
** Executed automatically by update_manager.php
* 2026-10-07 20:32
*/

if (!defined('ABCD_UPDATE_MODE')) die("Direct access not allowed.");

global $dest, $PARTIAL_UPDATE_SOURCES, $msgstr, $source_root, $os_in_gitname, $backup_dir;

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

migrationLog($msgstr["mig_checking"] ?? "Checking for structural changes...");

// Copies only the files that do not exist yet at the destination (never overwrites)
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

// ==================================================================
// CASE 1: NEW FILES AND FOLDERS (Added to partial update queue)
// ==================================================================
// Only CODE goes in this queue: the manager deletes the destination and recopies it.
// content/ is the user's vault and is handled in CASE 1b (never deleted/overwritten).
$new_sources = [
    'www/htdocs/abcd-api',
    'www/htdocs/plugins.php'
];

foreach ($new_sources as $src) {
    if (!in_array($src, $PARTIAL_UPDATE_SOURCES)) {
        $PARTIAL_UPDATE_SOURCES[] = $src;
        migrationLog(sprintf($msgstr["mig_injecting_src"] ?? "Injecting new source into update queue: %s", $src));
    }
}

// ==================================================================
// CASE 1b: USER VAULT - content/ (created here, never overwritten)
// Not versioned by git when empty, so it may not exist in the ZIP.
// ==================================================================
$content_dir = $dest['htdocs'] . '/content';

foreach (['', '/uploads', '/plugins', '/lang'] as $sub) {
    $dir = $content_dir . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new Exception("Cannot create directory: " . $dir);
    }
}
migrationLog($msgstr["mig_content_created"] ?? "content/ structure ensured (uploads, plugins, lang).");

// If the package ships files inside content/, copy only the ones that do not exist yet
$content_src = $source_root . '/www/htdocs/content';
if (is_dir($content_src)) {
    $safeCopyNewFiles($content_src, $content_dir);
}

// ==================================================================
// CASE 2: DELETING OBSOLETE FILES AND FOLDERS
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
        recursiveDelete($folder);
        migrationLog(sprintf($msgstr["mig_deleted_dir"] ?? "Deleted obsolete directory tree: %s", basename($folder)));
    }
}


// ==================================================================
// CASE 3: SMART INJECTION OF NEW BASES (Spectrum only)
// ==================================================================
migrationLog($msgstr["mig_checking_bases"] ?? "Checking for missing files in bases directory...");

$bases_src_dir = $source_root . '/www/bases-examples_' . $os_in_gitname;
$bases_dst_dir = rtrim($dest['bases'], '/\\');

// Inject 'spectrum' database
$spectrum_src = $bases_src_dir . '/spectrum';
$spectrum_dst = $bases_dst_dir . '/spectrum';
if (is_dir($spectrum_src)) {
    $safeCopyNewFiles($spectrum_src, $spectrum_dst);
    migrationLog($msgstr["mig_spectrum_copied"] ?? "Spectrum database structure injected safely.");
}

// Inject spectrum.par
$par_src = $bases_src_dir . '/par/spectrum.par';
$par_dst = $bases_dst_dir . '/par/spectrum.par';
if (file_exists($par_src) && !file_exists($par_dst)) {
    if (!is_dir(dirname($par_dst))) @mkdir(dirname($par_dst), 0755, true);
    @copy($par_src, $par_dst);
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
// CASE 4: SMART CONFIG.PHP UPDATE (v4.0+)
// ==================================================================
$old_config_path = $dest['htdocs'] . '/central/config.php';
// The new template comes from the downloaded package: the old installation does not
// have it yet, because central/ is only replaced after this script runs.
$template_path   = $source_root . '/www/htdocs/central/config.php.template';

if (!file_exists($old_config_path)) {
    migrationLog($msgstr["mig_config_skip"] ?? "Skipping config.php update: Old config not found.");
} elseif (!file_exists($template_path)) {
    throw new Exception($msgstr["mig_config_err_template"] ?? "config.php.template not found in the package (www/htdocs/central/).");
} else {
    migrationLog($msgstr["mig_config_start"] ?? "Starting smart migration of config.php...");

    $old_content        = file_get_contents($old_config_path);
    $new_config_content = file_get_contents($template_path);

    // Settings preserved from the user's config.php. Everything else comes from the template.
    $preserve = [
        'os_block'          => '/\/\/\s*Set operation system depending variables.*?\}\s*else\s*\{.*?\}/s',
        'ABCD_scripts_path' => '/(?<![\w$])\$ABCD_scripts_path\s*=(?!=)[^;]*;/',
        'cgibin_path'       => '/(?<![\w$])\$cgibin_path\s*=(?!=)[^;]*;/',
        'xWxis'             => '/(?<![\w$])\$xWxis\s*=(?!=)[^;]*;/',
    ];

    foreach ($preserve as $name => $pattern) {
        if (!preg_match($pattern, $old_content, $matches)) {
            if ($name === 'os_block') {
                throw new Exception($msgstr["mig_config_err_match"] ?? "Could not locate the expected user settings block in the old config.php.");
            }
            migrationLog("Setting '\$$name' not found in the old config.php. Template default kept.", "warning");
            continue;
        }
        $user_value = $matches[0];
        $merged = preg_replace_callback($pattern, function ($m) use ($user_value) {
            return $user_value;
        }, $new_config_content, 1, $replaced);

        if ($merged === null || $replaced !== 1) {
            throw new Exception($msgstr["mig_config_err_regex"] ?? "Failed to inject user settings ('$name') into config.php.template.");
        }
        $new_config_content = $merged;
    }

    // Never write a config.php with a syntax error
    try {
        token_get_all($new_config_content, TOKEN_PARSE);
    } catch (Throwable $e) {
        throw new Exception("The merged config.php has a syntax error: " . $e->getMessage());
    }

    // Backup of the user's v3 config. It must live outside central/ (central/ is wiped later)
    $backup_name = ((isset($backup_dir) && is_dir($backup_dir)) ? rtrim($backup_dir, '/\\') : dirname($old_config_path))
        . '/config.php.bak_v3_' . date('Ymd_His');
    @copy($old_config_path, $backup_name);
    migrationLog(sprintf($msgstr["mig_config_backup"] ?? "Backed up old config.php to %s", basename($backup_name)));

    // The new config.php is written at the END of the request, not now:
    //  - the manager replaces central/ after this script, and then restores the v3 config.php
    //    from its own backup (restoreConfigs), which would overwrite our file;
    //  - the new config requires LanguageManager.php, hooks.php and PluginBridge.php (new central/).
    //    If the update stops halfway, a v4 config on v3 code would take the whole site down.
    // So it is only applied if the manager reached its last step (version.php is the last item copied).
    $final_version_src = is_file($source_root . '/www/htdocs/version.php') ? file_get_contents($source_root . '/www/htdocs/version.php') : false;
    $final_htdocs      = $dest['htdocs'];

    register_shutdown_function(function () use ($old_config_path, $new_config_content, $final_version_src, $final_htdocs) {
        $ok = ($final_version_src !== false)
            && is_file($final_htdocs . '/version.php')
            && file_get_contents($final_htdocs . '/version.php') === $final_version_src;

        foreach (['/central/common/LanguageManager.php', '/central/common/hooks.php', '/central/common/PluginBridge.php', '/plugins.php', '/abcd-api'] as $required) {
            if (!file_exists($final_htdocs . $required)) $ok = false;
        }

        if (!$ok) {
            migrationLog("config.php v4 NOT applied: the update did not complete. The v3 config.php was kept.", "error");
        } elseif (file_put_contents($old_config_path, $new_config_content) === false) {
            migrationLog("FAILED to write the new config.php. Merge it manually from config.php.template.", "error");
        } else {
            migrationLog("Successfully updated config.php with user settings and v4.0+ variables.");
        }
    });
}

checkLastError();
migrationLog($msgstr["mig_completed"] ?? "Migration tasks completed.");