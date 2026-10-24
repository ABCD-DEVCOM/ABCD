<?php

/**
 * ==============================================================================
 * ABCD Update Manager (Interactive)
 * ==============================================================================
 *
 * DESCRIPTION:
 * This script presents an interface for the administrator, displaying the
 * information from the last Github release and allowing you to choose between a
 * partial (default, secure) or complete update (surpasses everything except
 * the config.php).
 *
 * @author Roger Craveiro Guilherme
 * 
 * 20250930 fho4abcd Added several checks and apropriate messages for partial run:
 * -- Added log file. Is overwritten on next run.
 * -- Activated check for admin rights: user must have profile "adm"
 * -- Check that php zip extension is loaded.
 * -- Check download and unzip result. Tested with insufficient disk quota
 * -- Check that all required sources are present in the package before actual update
 * -- Detect and log run-time errors in actual update. Tested with wrong permissions
 * 20251009 fho4bcd
 * -- Check that php curl extension is loaded.
 * -- Copy also cgi-bin subfolders ansi and utf8. Set executable permissions on executables
 * -- Print the log file timestamp in the server timezone (only on linux servers)
 * 20251126 fho4abcd
 * -- overall time limit set to 45 minutes (arbitrary large number), removed timelimit show error pop-up
 * -- Attention that PHP error log may contain more errors. Shows the PHP error log filename
 * 20251127 rogercgui Refactored for LiteSpeed compatibility & AJAX UI
 * 20251129 rogercgui Fix: Removed curl_close() deprecated warnings
 * 20251130 rogercgui Security: Auto-creates .htaccess to protect backup/temp folders inside htdocs
 * -- Implements "Chunked Extraction" to avoid server timeouts.
 * -- UI with Progress Bar and real-time logging.
 * -- Manual Upload detection (skips download if update.zip exists).
 * -- Secure: Blocks HTTP access to the upgrade folder.
 * 20251208 fho4abcd
 * -- Check for PHP errors and throw exceptions if necessary
 * -- Show log filenames to help error investigations
 * -- Do not show PHP errors to avoid unknown server responses
 * -- Moved version update to last update action (so update can be redone easily
 * -- Restore backups of configs also in case of errors (and hope for the best :))
 * -- Removed 'site' related configurations (e.g. local people can do with the existing code what they want
 * -- If a resource for an update is missing: Show only a warning and continue
 * 20260321 rogercgui Protection for the OPAC’s ‘uploads’ folder. The aim is to preserve the institution’s logos and icons.
 * 20260419 fho4abcd Removed call to non-existing function logMessage
 * 20261007 rogercgui Refactored 'install' step to use batched AJAX processing for real-time progress logging
 * -- Replaced hardcoded UI and log strings with $msgstr localization variables and English fallbacks.
 * -- Added cache-busting parameter to the page reload action.
 * -- Fixed migration script path to look inside 'www/htdocs/upgrade/update_actions.php'.
 * 20261022 rogercgui Fixes for batched installation lifecycle and pre-download self-update (v4.0.2):
 * -- Added pre-download 'self_update' step: fetches remote update_manager.php from release tag/assets, validates syntax with token_get_all(), performs atomic replacement with .bak backup, invalidates OPcache, and reloads UI when updated.
 * -- Standardized explicit JSON protocol statuses ('continue', 'step_done', 'done', 'reload_required', 'error').
 * -- Fixed 'Main config not found' error across AJAX batches by immediately calling restoreConfigs() after the 'central' folder is replaced.
 * -- Added fallback check to restore 'central/config.php' from backup at the start of each AJAX request if missing.
 */

// Increases the maximum execution time per request (reset on every AJAX call)
set_time_limit(300);

// Memory adjustment for large packages
ini_set('memory_limit', '512M');

// --- General Settings ---
define('GITHUB_REPOSITORY', 'ABCD-DEVCOM/ABCD');

// Check if version file exists before requiring
if (file_exists(__DIR__ . '/version.php')) require_once(__DIR__ . '/version.php');
if (!defined('ABCD_VERSION')) define('ABCD_VERSION', 'Unknown');
define('LOCAL_VERSION', ABCD_VERSION);

// List of files to be protected (backup and restoration)
const PROTECTED_FILES = [
    'central/config.php'
];

// List of Origin Files/Folders (in ZIP) for partial update.
$PARTIAL_UPDATE_SOURCES = [];
$PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/update_manager.php';
$PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/upgrade/update_actions.php';
$PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/central';
$PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/assets';
$PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/opac';
$PARTIAL_UPDATE_SOURCES[] = 'www/bases-examples_Windows/lang';

// ABCD installation root directory (HTDOCS)
$root_dir = __DIR__;

// Folder Definitions (Inside htdocs, but protected)
$upgrade_dir = $root_dir . "/upgrade";
$temp_dir    = $upgrade_dir . "/temp/";
$backup_dir  = $upgrade_dir . "/backup/";
$log_file    = $upgrade_dir . "/upgradelog.log";

// Ensure directories exist
if (!is_dir($upgrade_dir)) mkdir($upgrade_dir, 0775, true);
if (!is_dir($temp_dir))    mkdir($temp_dir, 0775, true);
if (!is_dir($backup_dir))  mkdir($backup_dir, 0775, true);
checkLastError();

// Determine OS and set OS dependent variables
$os = $_SERVER["SERVER_SOFTWARE"] ?? "";
$os_in_gitname = (stripos($os, "Win") > 0) ? "Windows" : "Linux";

if ($os_in_gitname == "Windows") {
    $PARTIAL_UPDATE_SOURCES[] = 'www/cgi-bin_Windows/ansi';
    $PARTIAL_UPDATE_SOURCES[] = 'www/cgi-bin_Windows/utf8';
} else {
    $PARTIAL_UPDATE_SOURCES[] = 'www/cgi-bin_Linux/ansi';
    $PARTIAL_UPDATE_SOURCES[] = 'www/cgi-bin_Linux/utf8';

    // Timezone fix for Linux logs
    exec('date +%Z', $output, $retval);
    if ($retval == 0 && isset($output[0])) {
        $tz = timezone_name_from_abbr($output[0]);
        if ($tz) date_default_timezone_set($tz);
    }
}

// --- HELPER FUNCTIONS ---

function isAdmin(): bool
{
    if (!isset($_SESSION["login"]) || $_SESSION["profile"] != "adm") return false;
    return true;
}

function sendJsonResponse(string $status, int|float $percent, string $message, mixed $debug = null): void
{
    header('Content-Type: application/json');
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status'  => $status,
        'percent' => $percent,
        'message' => $message,
        'debug'   => $debug
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function writeLog(string $message, string $type = 'INFO'): string
{
    global $log_file;
    // Language files may bring ANSI (Windows-1252) text and HTML entities such as &apos;.
    // Normalize to plain UTF-8 text first, so it is neither shown as "N�O" nor double-escaped ("&amp;apos;").
    if (function_exists('mb_check_encoding') && !mb_check_encoding($message, 'UTF-8')) {
        $message = mb_convert_encoding($message, 'UTF-8', 'Windows-1252');
    }
    $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $timestamp = date('H:i:s');
    file_put_contents($log_file, "[$timestamp] [$type] " . $message . PHP_EOL, FILE_APPEND);
    $retval = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($type == 'warning' || $type == 'WARNING') {
        $retval = "<span style='color:#ffc107'>" . $retval . "</span>";
    } elseif ($type == 'error' || $type == 'ERROR') {
        $retval = "<span style='color:#ff6666'>" . $retval . "</span>";
    }
    return "[$timestamp] " . $retval;
}

function recursiveDelete(string $dir): void
{
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    @rmdir($dir);
}

function recursiveCopy(string $src, string $dst): void
{
    global $os_in_gitname;
    if (!is_dir($dst)) mkdir($dst, 0755, true);

    $len = strlen($src);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $fileinfo) {
        $subPath = substr($fileinfo->getPathname(), $len);
        $target = $dst . $subPath;
        if ($fileinfo->isDir()) {
            if (!is_dir($target)) mkdir($target, 0755, true);
        } else {
            $parentDir = dirname($target);
            if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);
            copy($fileinfo->getRealPath(), $target);
            if ($os_in_gitname == "Linux" && pathinfo($target, PATHINFO_EXTENSION) == "") {
                chmod($target, 0755);
            }
        }
    }
}

function secureUpgradeFolder(string $dir): void
{
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
    }
    $webconfig = $dir . '/web.config';
    if (!file_exists($webconfig)) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <security>
      <requestFiltering>
        <hiddenSegments>
          <add segment="temp" />
          <add segment="backup" />
        </hiddenSegments>
      </requestFiltering>
    </security>
  </system.webServer>
</configuration>';
        file_put_contents($webconfig, $xml);
    }
}

function getLatestReleaseInfo(): array
{
    global $msgstr;
    $api_url = 'https://api.github.com/repos/' . GITHUB_REPOSITORY . '/releases';
    $ch = curl_init();

    // Optional developer token for testing rate limits
    $github_token = '';

    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $headers = ['User-Agent: ABCD-Update-Manager'];
    if (!empty($github_token) && $github_token !== 'YOUR_GITHUB_PAT_HERE') {
        $headers[] = 'Authorization: token ' . $github_token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(($msgstr["upd_err_curl"] ?? 'Curl Error: ') . curl_error($ch));
    }

    $data = json_decode($response, true);

    if (isset($data['message']) && stripos($data['message'], 'rate limit') !== false) {
        throw new Exception($msgstr["upd_err_ratelimit"] ?? 'GitHub API Rate Limit Exceeded. A valid PAT is required for testing.');
    }

    if (isset($data['message'])) {
        throw new Exception(($msgstr["upd_err_gh_api"] ?? 'GitHub API Error: ') . $data['message']);
    }
    if (!is_array($data) || empty($data)) {
        throw new Exception($msgstr["upd_err_no_rel"] ?? 'No releases found.');
    }

    return $data[0];
}

/**
 * Fetches the raw update_manager.php from the target release (checks release assets first,
 * then falls back to the raw tag URL). Returns null if unreachable so the update can still proceed.
 */
function fetchRemoteUpdateManager(array $release): ?string
{
    $candidate_urls = [];

    if (!empty($release['assets']) && is_array($release['assets'])) {
        foreach ($release['assets'] as $asset) {
            if (($asset['name'] ?? '') === 'update_manager.php' && !empty($asset['browser_download_url'])) {
                $candidate_urls[] = $asset['browser_download_url'];
            }
        }
    }

    if (!empty($release['tag_name'])) {
        $tag = rawurlencode((string)$release['tag_name']);
        $candidate_urls[] = 'https://raw.githubusercontent.com/' . GITHUB_REPOSITORY . '/' . $tag . '/www/htdocs/update_manager.php';
    }

    foreach ($candidate_urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ABCD-Update-Manager');
        $content = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!curl_errno($ch) && $http_code === 200 && is_string($content) && str_contains($content, 'ABCD Update Manager')) {
            return $content;
        }
    }

    return null;
}

/**
 * Atomically replaces a target PHP file after validating its syntax with token_get_all(),
 * keeping a .bak copy and invalidating OPcache.
 */
function atomicReplacePhpFile(string $target_file, string $new_code, string $backup_file): void
{
    global $msgstr;

    // 1. Validate PHP syntax before writing
    try {
        token_get_all($new_code, TOKEN_PARSE);
    } catch (\Throwable $e) {
        throw new Exception(sprintf($msgstr["upd_self_err_syntax"] ?? "Self-update aborted: remote update_manager.php has a syntax error (%s).", $e->getMessage()));
    }

    $tmp_file = $target_file . '.tmp';
    if (@file_put_contents($tmp_file, $new_code, LOCK_EX) === false) {
        throw new Exception(sprintf($msgstr["upd_self_err_write"] ?? "Self-update failed: cannot write temporary file (%s).", basename($tmp_file)));
    }

    $written_code = @file_get_contents($tmp_file);
    if ($written_code === false || hash('sha256', $written_code) !== hash('sha256', $new_code)) {
        @unlink($tmp_file);
        throw new Exception(sprintf($msgstr["upd_self_err_write"] ?? "Self-update failed: cannot write temporary file (%s).", basename($tmp_file)));
    }

    // 2. Create backup of the current file
    if (file_exists($target_file)) {
        if (!@copy($target_file, $backup_file)) {
            @unlink($tmp_file);
            throw new Exception(sprintf($msgstr["upd_self_err_backup"] ?? "Self-update failed: could not create backup (%s).", basename($backup_file)));
        }
    }

    // 3. Atomic rename over the target file (with Windows copy fallback)
    if (!@rename($tmp_file, $target_file)) {
        if (!@copy($tmp_file, $target_file)) {
            @unlink($tmp_file);
            if (file_exists($backup_file)) {
                @copy($backup_file, $target_file);
            }
            throw new Exception(sprintf($msgstr["upd_self_err_rename"] ?? "Self-update failed: could not replace %s.", basename($target_file)));
        }
        @unlink($tmp_file);
    }

    // 4. Invalidate OPcache and file stat cache immediately
    clearstatcache(true, $target_file);
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($target_file, true);
    }
}

function checkLastError(): void
{
    global $msgstr;
    $errmsg = error_get_last();
    if ($errmsg != null) {
        error_clear_last();
        throw new Exception(($msgstr["upd_err_detected"] ?? "Detected problem: ") . $errmsg["message"]);
    }
}

function restoreConfigs(): void
{
    global $logs, $backup_dir, $root_dir, $msgstr;
    $logs[] = writeLog($msgstr["upd_restoring_prot"] ?? "Restoring protected files...");
    foreach (PROTECTED_FILES as $rel_path) {
        $bkp = rtrim($backup_dir, '/\\') . '/' . basename($rel_path);
        $dest_file = $root_dir . '/' . $rel_path;
        if (file_exists($bkp)) {
            if (!is_dir(dirname($dest_file))) {
                mkdir(dirname($dest_file), 0755, true);
            }
            copy($bkp, $dest_file);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($dest_file, true);
            }
            $logs[] = writeLog(sprintf($msgstr["upd_restored"] ?? "Restored: %s", $rel_path));
        }
    }

    $opac_uploads_src = $root_dir . '/opac/uploads';
    $opac_uploads_bkp = rtrim($backup_dir, '/\\') . '/opac_uploads_backup';
    if (is_dir($opac_uploads_bkp)) {
        $logs[] = writeLog($msgstr["upd_restoring_opac"] ?? "Restoring the OPAC uploads folder...");
        recursiveCopy($opac_uploads_bkp, $opac_uploads_src);
        $logs[] = writeLog($msgstr["upd_restored_opac"] ?? "The 'opac/uploads' folder has been successfully restored.");
    }
}

// ============================================================================
// AJAX HANDLER
// ============================================================================
global $msgstr;

if (isset($_POST['ajax_action'])) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!defined('ABCD_UPDATE_MODE')) define('ABCD_UPDATE_MODE', true);

    // Ensure central/config.php is present before loading it or language files
    $main_config = $root_dir . '/' . PROTECTED_FILES[0];
    $bkp_config  = rtrim($backup_dir, '/\\') . '/' . basename(PROTECTED_FILES[0]);
    if (!file_exists($main_config) && file_exists($bkp_config)) {
        if (!is_dir(dirname($main_config))) mkdir(dirname($main_config), 0755, true);
        @copy($bkp_config, $main_config);
    }

    if (file_exists($main_config)) {
        require_once $main_config;
    }
    if (file_exists($root_dir . "/central/lang/dbadmin.php")) {
        include_once($root_dir . "/central/lang/dbadmin.php");
    }

    if (!isAdmin()) sendJsonResponse('error', 0, $msgstr["upd_access_denied"] ?? "Access Denied");

    $action = $_POST['ajax_action'];
    $logs = [];
    $phplogfile = ini_get("error_log");
    ini_set('display_errors', 0);
    error_clear_last();

    try {
        // === STEP 0: PRE-DOWNLOAD SELF-UPDATE ===
        if ($action === 'self_update') {
            secureUpgradeFolder($upgrade_dir);
            $logs[] = writeLog($msgstr["upd_self_checking"] ?? "Checking if Update Manager requires a self-update before starting...");

            $zip_path = rtrim($temp_dir, '/\\') . '/update.zip';
            if (file_exists($zip_path) && filesize($zip_path) > 1000000) {
                $logs[] = writeLog($msgstr["upd_self_skip_manual"] ?? "Manual update.zip detected. Skipping remote self-update check.");
                sendJsonResponse('step_done', 2, implode("<br>", $logs));
            }

            $release = getLatestReleaseInfo();
            $remote_manager_code = fetchRemoteUpdateManager($release);

            if ($remote_manager_code === null) {
                $logs[] = writeLog($msgstr["upd_self_skip_unavail"] ?? "Remote update_manager.php not fetched separately. Proceeding with standard package flow.", "warning");
                sendJsonResponse('step_done', 2, implode("<br>", $logs));
            }

            $local_manager_code = @file_get_contents(__FILE__);
            if ($local_manager_code !== false && hash('sha256', $local_manager_code) !== hash('sha256', $remote_manager_code)) {
                $backup_manager = rtrim($backup_dir, '/\\') . '/update_manager.php.bak';
                atomicReplacePhpFile(__FILE__, $remote_manager_code, $backup_manager);
                $logs[] = writeLog($msgstr["upd_self_updated_reload"] ?? "Update Manager has been updated to the latest release version. Reloading interface to apply changes...");
                sendJsonResponse('reload_required', 3, implode("<br>", $logs));
            }

            $logs[] = writeLog($msgstr["upd_self_up_to_date"] ?? "Update Manager is already up to date.");
            sendJsonResponse('step_done', 2, implode("<br>", $logs));
        }

        // === STEP 1: INITIALIZATION & BACKUP ===
        if ($action === 'init') {
            recursiveDelete($temp_dir);
            if (!is_dir($temp_dir)) mkdir($temp_dir, 0775, true);
            secureUpgradeFolder($upgrade_dir);

            file_put_contents($log_file, "--- Update Started: " . date('Y-m-d H:i:s') . " ---\n");
            $logs[] = writeLog($msgstr["upd_init_start"] ?? "Starting initialization...");
            $logs[] = writeLog($msgstr["upd_init_secure"] ?? "Securing upgrade folder...");
            $logs[] = writeLog(sprintf($msgstr["upd_server_os"] ?? "Server OS: %s", $os_in_gitname));

            $logs[] = writeLog($msgstr["upd_backup_prot"] ?? "Backing up protected files...");
            foreach (PROTECTED_FILES as $rel_path) {
                if (file_exists($root_dir . '/' . $rel_path)) {
                    copy($root_dir . '/' . $rel_path, rtrim($backup_dir, '/\\') . '/' . basename($rel_path));
                    $logs[] = writeLog(sprintf($msgstr["upd_backup_item"] ?? "Backup: %s", $rel_path));
                }
            }

            $opac_uploads_src = $root_dir . '/opac/uploads';
            $opac_uploads_bkp = rtrim($backup_dir, '/\\') . '/opac_uploads_backup';

            if (is_dir($opac_uploads_src)) {
                $logs[] = writeLog($msgstr["upd_backup_opac"] ?? "Backing up the OPAC uploads folder...");
                recursiveCopy($opac_uploads_src, $opac_uploads_bkp);
                $logs[] = writeLog($msgstr["upd_backup_opac_ok"] ?? "Backup of 'opac/uploads' completed.");
            } else {
                $logs[] = writeLog($msgstr["upd_backup_opac_skip"] ?? "Folder 'opac/uploads' not found. Skipping backup.", "warning");
            }

            $_SESSION['zip_extract_index'] = 0;
            $_SESSION['install_index'] = 0;
            $_SESSION['update_type'] = $_POST['update_type'];

            sendJsonResponse('step_done', 5, implode("<br>", $logs));
        }

        // === STEP 2: DOWNLOAD ===
        if ($action === 'download') {
            $zip_path = rtrim($temp_dir, '/\\') . '/update.zip';

            if (file_exists($zip_path) && filesize($zip_path) > 1000000) {
                sendJsonResponse('step_done', 20, writeLog($msgstr["upd_manual_zip"] ?? "Existing local update.zip found (Manual Upload). Skipping download."));
            }

            $release = getLatestReleaseInfo();
            $logs[] = writeLog(sprintf($msgstr["upd_target_ver"] ?? "Target Version: %s", $release['tag_name']));
            $logs[] = writeLog($msgstr["upd_downloading"] ?? "Downloading package from GitHub...");

            $fp = fopen($zip_path, 'w');
            if (!$fp) throw new Exception($msgstr["upd_err_temp"] ?? "Cannot write to temp directory");

            $ch = curl_init($release['zipball_url']);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ABCD-Update-Manager');
            curl_exec($ch);

            if (curl_errno($ch)) throw new Exception(curl_error($ch));
            fclose($fp);

            $logs[] = writeLog($msgstr["upd_download_ok"] ?? "Download completed successfully.");
            sendJsonResponse('step_done', 20, implode("<br>", $logs));
        }

        // === STEP 3: EXTRACT (BATCHED) ===
        if ($action === 'extract') {
            $zip_path = rtrim($temp_dir, '/\\') . '/update.zip';
            $unzip_dir = rtrim($temp_dir, '/\\') . '/unzipped';
            if (!is_dir($unzip_dir)) mkdir($unzip_dir, 0755, true);

            $zip = new ZipArchive;
            if ($zip->open($zip_path) !== TRUE) throw new Exception($msgstr["upd_err_zip"] ?? "Failed to open ZIP file.");

            $totalFiles = $zip->numFiles;
            $startIndex = isset($_SESSION['zip_extract_index']) ? (int)$_SESSION['zip_extract_index'] : 0;
            $timeLimit = 4.0;
            $startTime = microtime(true);

            for ($i = $startIndex; $i < $totalFiles; $i++) {
                if ((microtime(true) - $startTime) > $timeLimit) {
                    $_SESSION['zip_extract_index'] = $i;
                    $zip->close();
                    $percent = 20 + round(($i / $totalFiles) * 60);
                    sendJsonResponse('continue', $percent, writeLog(sprintf($msgstr["upd_extracting"] ?? "Extracted %d of %d files...", $i, $totalFiles)));
                }
                $filename = $zip->getNameIndex($i);
                $zip->extractTo($unzip_dir, $filename);
            }

            $zip->close();
            $_SESSION['zip_extract_index'] = 0;
            sendJsonResponse('step_done', 80, writeLog(sprintf($msgstr["upd_extract_ok"] ?? "Extraction completed successfully. Total files: %d", $totalFiles)));
        }

        // === STEP 4: INSTALL & MIGRATION (BATCHED) ===
        if ($action === 'install') {
            if (!file_exists($main_config)) {
                if (file_exists($bkp_config)) {
                    if (!is_dir(dirname($main_config))) mkdir(dirname($main_config), 0755, true);
                    copy($bkp_config, $main_config);
                } else {
                    throw new Exception($msgstr["upd_err_config"] ?? "Main config not found");
                }
            }

            require_once $main_config;
            error_clear_last();

            global $cgibin_path, $db_path, $ABCD_scripts_path;
            $dest = [
                'htdocs'  => rtrim($ABCD_scripts_path, '/\\'),
                'bases'   => rtrim($db_path, '/\\'),
                'cgi-bin' => rtrim($cgibin_path, '/\\')
            ];

            $unzip_dir = rtrim($temp_dir, '/\\') . '/unzipped';
            $dirs = glob($unzip_dir . '/*');
            if (!isset($dirs[0])) throw new Exception($msgstr["upd_err_empty_zip"] ?? "Empty ZIP file extraction");
            $source_root = $dirs[0];

            $is_partial = ($_SESSION['update_type'] === 'partial');
            $startIndex = isset($_SESSION['install_index']) ? (int)$_SESSION['install_index'] : 0;

            // Run pre-installation hooks and validation only once (Batch 0)
            if ($startIndex === 0) {
                $logs[] = writeLog($msgstr["upd_mig_tasks"] ?? "Check for migration tasks...");
                $migration_script_zip   = $source_root . '/www/htdocs/upgrade/update_actions.php';
                $migration_script_root  = $source_root . '/upgrade/update_actions.php';
                $migration_script_local = $upgrade_dir . '/update_actions.php';
                $script_to_run = '';

                if (file_exists($migration_script_zip)) {
                    $script_to_run = $migration_script_zip;
                    $logs[] = writeLog($msgstr["upd_mig_src_zip"] ?? "Migration Source: From ZIP Package (Standard)");
                } elseif (file_exists($migration_script_root)) {
                    $script_to_run = $migration_script_root;
                    $logs[] = writeLog($msgstr["upd_mig_src_zip"] ?? "Migration Source: From ZIP Package (Standard)");
                } elseif (file_exists($migration_script_local)) {
                    $script_to_run = $migration_script_local;
                    $logs[] = writeLog($msgstr["upd_mig_src_local"] ?? "Migration Source: Local File (Dev/Manual Override)", "warning");
                }

                if ($script_to_run) {
                    $logs[] = writeLog($msgstr["upd_mig_exec"] ?? "Executing migration tasks...");
                    if (!is_readable($script_to_run)) throw new Exception($msgstr["upd_err_script"] ?? "Migration script is not readable");
                    include($script_to_run);
                    checkLastError();
                    $logs[] = writeLog($msgstr["upd_mig_ok"] ?? "Migration script executed successfully.");
                } else {
                    $logs[] = writeLog($msgstr["upd_mig_skip"] ?? "No migration script found (Skipping).");
                }

                if ($is_partial) {
                    $logs[] = writeLog($msgstr["upd_partial_start"] ?? "Starting Partial Update...");
                    if (!in_array('www/htdocs/version.php', $PARTIAL_UPDATE_SOURCES)) {
                        $PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/version.php';
                    }
                    $_SESSION['partial_sources'] = array_values(array_unique($PARTIAL_UPDATE_SOURCES));

                    $logs[] = writeLog($msgstr["upd_perm_check"] ?? "Checking directory permissions before starting...");
                    $permission_errors = [];

                    foreach ($_SESSION['partial_sources'] as $src) {
                        $d_path = '';
                        $source_basename = basename($src);
                        if ($source_basename === 'update_manager.php' || $source_basename === 'version.php') {
                            $d_path = $dest['htdocs'] . '/' . $source_basename;
                        } elseif (strpos($src, 'www/htdocs/') === 0) {
                            $d_path = $dest['htdocs'] . '/' . str_replace('www/htdocs/', '', $src);
                        } elseif (strpos($src, 'www/bases-examples_Windows/') === 0) {
                            $d_path = $dest['bases'] . '/' . str_replace('www/bases-examples_Windows/', '', $src);
                        } elseif (strpos($src, 'www/cgi-bin_Windows/') === 0) {
                            $d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Windows/', '', $src);
                        } elseif (strpos($src, 'www/cgi-bin_Linux/') === 0) {
                            $d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Linux/', '', $src);
                        }
                        if ($d_path) {
                            $check_path = is_dir($d_path) ? $d_path : dirname($d_path);
                            if (file_exists($check_path)) {
                                $test_file = $check_path . '/.abcd_perm_test';
                                if (@file_put_contents($test_file, 'test') === false) {
                                    $permission_errors[] = $check_path;
                                } else {
                                    @unlink($test_file);
                                }
                            } elseif (is_dir(dirname($check_path)) && !is_writable(dirname($check_path))) {
                                $permission_errors[] = dirname($check_path);
                            }
                        }
                    }

                    error_clear_last();
                    $permission_errors = array_unique($permission_errors);
                    if (!empty($permission_errors)) {
                        $error_msg = ($msgstr["upd_perm_denied"] ?? "Permission Denied! Cannot write to:") . "<br> - " . implode("<br> - ", $permission_errors);
                        throw new Exception($error_msg);
                    }
                    $logs[] = writeLog($msgstr["upd_perm_ok"] ?? "Permissions check passed. Proceeding with update.");
                } else {
                    $logs[] = writeLog($msgstr["upd_full_start"] ?? "Starting COMPLETE Update...", 'WARNING');
                    if (strlen($dest['htdocs']) < 5 || strlen($dest['bases']) < 5) {
                        throw new Exception($msgstr["upd_err_paths"] ?? "Path variables seem unsafe. Aborting full update.");
                    }

                    recursiveDelete($dest['htdocs']);
                    recursiveDelete($dest['bases']);
                    recursiveCopy($source_root . '/www/htdocs', $dest['htdocs']);
                    recursiveCopy($source_root . '/www/bases-examples_Windows', $dest['bases']);

                    restoreConfigs();
                    recursiveDelete($temp_dir);
                    sendJsonResponse('done', 100, implode("<br>", $logs));
                }
            }

            // Batched Installation Loop for Partial Updates
            if ($is_partial) {
                $sources = $_SESSION['partial_sources'];
                $totalSources = count($sources);

                if ($startIndex < $totalSources) {
                    $src = $sources[$startIndex];
                    $s_path = $source_root . '/' . $src;
                    $d_path = '';
                    $source_basename = basename($src);

                    if ($source_basename === 'update_manager.php' || $source_basename === 'version.php') {
                        $d_path = $dest['htdocs'] . '/' . $source_basename;
                    } elseif (strpos($src, 'www/htdocs/') === 0) {
                        $d_path = $dest['htdocs'] . '/' . str_replace('www/htdocs/', '', $src);
                    } elseif (strpos($src, 'www/bases-examples_Windows/') === 0) {
                        $d_path = $dest['bases'] . '/' . str_replace('www/bases-examples_Windows/', '', $src);
                    } elseif (strpos($src, 'www/cgi-bin_Windows/') === 0) {
                        $d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Windows/', '', $src);
                    } elseif (strpos($src, 'www/cgi-bin_Linux/') === 0) {
                        $d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Linux/', '', $src);
                    }

                    if ($d_path && file_exists($s_path)) {
                        $logs[] = writeLog(sprintf($msgstr["upd_updating"] ?? "Updating '%s'...", basename($d_path)));
                        if (is_dir($s_path)) {
                            recursiveDelete($d_path);
                            checkLastError();
                            recursiveCopy($s_path, $d_path);
                            checkLastError();

                            // Immediately restore central/config.php after replacing 'central'
                            if ($source_basename === 'central') {
                                restoreConfigs();
                            }
                        } else {
                            $parent = dirname($d_path);
                            if (!is_dir($parent)) mkdir($parent, 0755, true);
                            if ($source_basename === 'update_manager.php') {
                                $new_mgr_code = @file_get_contents($s_path);
                                if ($new_mgr_code !== false) {
                                    atomicReplacePhpFile($d_path, $new_mgr_code, rtrim($backup_dir, '/\\') . '/update_manager.php.bak');
                                }
                            } else {
                                copy($s_path, $d_path);
                                checkLastError();
                                if (function_exists('opcache_invalidate') && pathinfo($d_path, PATHINFO_EXTENSION) === 'php') {
                                    @opcache_invalidate($d_path, true);
                                }
                            }
                            if ($os_in_gitname == "Linux" && pathinfo($d_path, PATHINFO_EXTENSION) == "") chmod($d_path, 0755);
                        }
                    } elseif (!file_exists($s_path)) {
                        $logs[] = writeLog(sprintf($msgstr["upd_not_updated"] ?? "'%s' NOT updated (No source).", basename($d_path)), "warning");
                    }

                    $_SESSION['install_index'] = $startIndex + 1;
                    $percent = min(99, 80 + round((($startIndex + 1) / $totalSources) * 20));
                    sendJsonResponse('continue', $percent, implode("<br>", $logs));
                } else {
                    // Removing the temporary files can take minutes (thousands of files): announce it in a
                    // first response, so the user sees the update is still working, and do it in the next one.
                    if (empty($_SESSION['cleanup_announced'])) {
                        restoreConfigs();
                        $_SESSION['cleanup_announced'] = true;
                        $logs[] = writeLog($msgstr["upd_cleaning"] ?? "Finalizing: removing temporary files (this may take a few minutes)...");
                        sendJsonResponse('continue', 99, implode("<br>", $logs));
                    }
                    ini_set('display_errors', 1);
                    recursiveDelete($temp_dir);
                    unset($_SESSION['install_index']);
                    unset($_SESSION['partial_sources']);
                    unset($_SESSION['cleanup_announced']);
                    $logs[] = writeLog($msgstr["upd_done"] ?? "Update completed successfully!");
                    sendJsonResponse('done', 100, implode("<br>", $logs));
                }
            }
        }
    } catch (Exception $e) {
        $logs[] = writeLog($e->getMessage(), "ERROR");
        if ($phplogfile != "") {
            $logs[] = writeLog("");
            $logs[] = writeLog(sprintf($msgstr["upd_err_php_log"] ?? "See also PHP logfile %s", $phplogfile));
        }
        restoreConfigs();
        $logs[] = writeLog($msgstr["upd_err_tmp_kept"] ?? 'Temporary files are kept for error investigation and will be removed on restart.');
        sendJsonResponse('error', 90, $e->getMessage());
    }
    exit;
}

// ============ UI CODE (Frontend) ============
include("central/config.php");
session_start();

if (!isset($_SESSION["permiso"])) header("Location: central/common/error_page.php");
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
$lang = $_SESSION["lang"];

include("central/common/get_post.php");
include("central/common/inc_nodb_lang.php");
include("central/lang/dbadmin.php");
include("central/lang/prestamo.php");
include("central/common/header.php");
include("central/common/institutional_info.php");
?>

<div class="sectionInfo">
    <div class="breadcrumb"><?php echo ($msgstr["configure"] ?? "Configure") . " ABCD"; ?></div>
    <div class="actions">
        <?php include "central/common/inc_back.php"; ?>
    </div>
    <div class="spacer">&#160;</div>
</div>

<style>
    .update-container {
        max-width: 800px;
        margin: 20px auto;
        background: #343a40;
        color: #e9ecef;
        padding: 20px;
        border-radius: 5px;
        font-family: sans-serif;
    }

    h1 {
        color: #ffc107;
        border-bottom: 1px solid #555;
        padding-bottom: 10px;
    }

    .progress-wrapper {
        background: #555;
        height: 30px;
        border-radius: 15px;
        margin: 20px 0;
        overflow: hidden;
        position: relative;
        display: none;
    }

    .progress-bar {
        height: 100%;
        background: #28a745;
        width: 0%;
        transition: width 0.3s ease;
    }

    .progress-text {
        position: absolute;
        width: 100%;
        text-align: center;
        line-height: 30px;
        font-weight: bold;
        color: #fff;
        text-shadow: 1px 1px 2px #000;
    }

    .log-window {
        background: #212529;
        height: 300px;
        overflow-y: auto;
        padding: 10px;
        font-family: monospace;
        font-size: 13px;
        border: 1px solid #555;
        margin-top: 15px;
        display: none;
        color: #ccc;
    }

    .btn-action {
        background: #ffc107;
        border: none;
        padding: 15px 30px;
        color: #000;
        font-weight: bold;
        cursor: pointer;
        border-radius: 5px;
        font-size: 16px;
        width: 100%;
        margin-top: 10px;
    }

    .btn-action:disabled {
        background: #777;
        cursor: not-allowed;
    }

    .btn-action:hover {
        background: #e0a800;
    }

    .options {
        margin: 20px 0;
        border: 1px solid #555;
        padding: 15px;
        border-radius: 5px;
        background: #444;
    }

    .info-version {
        margin-bottom: 20px;
        border-left: 4px solid #ffc107;
        padding-left: 10px;
    }

    .info-box.error {
        background-color: #dc3545;
        color: #fff;
        padding: 15px;
        border-radius: 4px;
    }
</style>

<div class="all">
    <div class="update-container">
        <h1><?php echo ($msgstr["upd_title"] ?? "ABCD Update Manager") . " (v4.5)"; ?></h1>

        <?php if (!isAdmin()): ?>
            <div class="info-box error"><?php echo $msgstr["upd_access_denied"] ?? "Denied access: You are not allowed to run this script."; ?></div>
        <?php elseif (!extension_loaded('zip') || !extension_loaded('curl')): ?>
            <div class="info-box error"><?php echo $msgstr["upd_err_ext"] ?? "Critical Error: PHP Extensions 'zip' and 'curl' are required."; ?></div>
        <?php else:
            try {
                $release = getLatestReleaseInfo();
                $remote_ver = $release['tag_name'];
                $body_txt = $release['body'];
            } catch (Exception $e) {
                $remote_ver = ($msgstr["upd_err_fetch"] ?? "Error fetching info: ") . $e->getMessage();
                $body_txt = "";
            }
        ?>

            <div id="setup-panel">
                <div class="info-version">
                    <p><?php echo $msgstr["upd_curr_ver"] ?? "Current Version:"; ?> <strong><?php echo LOCAL_VERSION; ?></strong></p>
                    <p><?php echo $msgstr["upd_latest_ver"] ?? "Latest Version:"; ?> <strong><?php echo $remote_ver; ?></strong></p>
                    <?php if (!empty($body_txt)) {
                        // The page charset comes from header.php (may be ISO-8859-1 on ANSI installs), while the
                        // GitHub text is UTF-8. Non-ASCII chars are sent as numeric entities (&#128640;), which
                        // browsers read as Unicode in any page charset: emojis and diacritics display correctly.
                        $body_html = htmlspecialchars($body_txt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        if (function_exists('mb_encode_numericentity')) {
                            $body_html = mb_encode_numericentity($body_html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
                        }
                        echo "<pre style='background:#222; padding:10px; white-space: pre-wrap;'>" . $body_html . "</pre>";
                    } ?>
                </div>

                <div class="options">
                    <label style="cursor:pointer">
                        <input type="radio" name="u_type" value="partial" checked>
                        <strong><?php echo $msgstr["upd_opt_partial"] ?? "Partial Update (Recommended)"; ?></strong>
                        <p style="margin:5px 0 10px 25px; font-size:0.9em; color:#ddd"><?php echo $msgstr["upd_opt_partial_desc"] ?? "Updates core files only. Preserves databases and customizations."; ?></p>
                    </label>
                    <hr style="border-color:#555">
                    <label style="cursor:pointer">
                        <input type="radio" name="u_type" value="completa">
                        <strong><?php echo $msgstr["upd_opt_full"] ?? "Full Update (Destructive)"; ?></strong>
                        <p style="margin:5px 0 0 25px; font-size:0.9em; color:#ff9999"><?php echo $msgstr["upd_opt_full_desc"] ?? "Deletes HTDOCS and BASES before installing. Use only if corrupted."; ?></p>
                    </label>
                </div>

                <button class="btn-action" onclick="startUpdate(false)" id="btnStart"><?php echo $msgstr["upd_btn_start"] ?? "START UPDATE PROCESS"; ?></button>
            </div>

            <div class="progress-wrapper" id="progressBox">
                <div class="progress-bar" id="pBar"></div>
                <div class="progress-text" id="pText">0%</div>
            </div>

            <div class="log-window" id="logBox"></div>

            <div id="final-msg" style="display:none; text-align:center; margin-top:20px;">
                <h2 style="color:#28a745"><?php echo $msgstr["upd_complete"] ?? "Update Complete!"; ?></h2>
                <div><?php echo ($msgstr["upd_log_file"] ?? "Log file: ") . $log_file; ?></div>
                <button class="btn-action" onclick="reloadCleanCache()"><?php echo $msgstr["upd_btn_reload"] ?? "Reload Page"; ?></button>
            </div>

        <?php endif; ?>
    </div>
</div>
<?php include("central/common/footer.php"); ?>

<script>
    async function startUpdate(skipConfirm = false) {
        let conf_msg = "<?php echo $msgstr['upd_confirm'] ?? 'Are you sure you want to proceed?'; ?>";
        if (!skipConfirm && !confirm(conf_msg)) return;
        document.getElementById('setup-panel').style.display = 'none';
        document.getElementById('progressBox').style.display = 'block';
        document.getElementById('logBox').style.display = 'block';

        const type = document.querySelector('input[name="u_type"]:checked').value;
        try {
            if (!skipConfirm) {
                const selfRes = await runStep('self_update', type);
                if (selfRes && selfRes.status === 'reload_required') {
                    setTimeout(() => {
                        window.location.href = 'update_manager.php?nocache=' + new Date().getTime() + '&autorun=' + encodeURIComponent(type);
                    }, 1200);
                    return;
                }
            }
            await runStep('init', type);
            await runStep('download', type);
            await runLoop('extract', type);
            await runLoop('install', type);
        } catch (e) {
            console.error(e);
            appendLog(`<span style="color:#ff6666"><?php echo $msgstr['upd_fatal_error'] ?? 'FATAL ERROR:'; ?> ${e.message}</span>`);
            appendLog(`<span style="color:#ffc107"><?php echo ($msgstr['upd_err_more_log'] ?? 'More in log: ') . str_replace('\\', '/', $log_file); ?></span>`);
            document.getElementById('pBar').style.background = '#dc3545';
            alert("<?php echo $msgstr['upd_failed'] ?? 'Update Failed'; ?>");
        }
    }

    async function runStep(action, type) {
        appendLog(`>> <?php echo $msgstr['upd_action'] ?? 'Action:'; ?> ${action.toUpperCase()}...`);
        const formData = new FormData();
        formData.append('ajax_action', action);
        formData.append('update_type', type);

        const req = await fetchWithTicker('', {
            method: 'POST',
            body: formData
        });
        if (!req.ok) throw new Error(`HTTP Error ${req.status}`);

        const text = await req.text();
        let res;
        try {
            res = JSON.parse(text);
        } catch (e) {
            throw new Error("Invalid Server Response: " + text.substring(0, 100));
        }

        handleResponse(res);
        if (res.status === 'error') throw new Error(res.message);
        return res;
    }

    // Shows the elapsed seconds next to the percentage while a slow step is running (so it does not look frozen)
    async function fetchWithTicker(url, options) {
        const pText = document.getElementById('pText');
        const base = pText.innerHTML;
        const t0 = Date.now();
        const timer = setInterval(() => {
            const secs = Math.floor((Date.now() - t0) / 1000);
            if (secs >= 4) pText.innerHTML = base + ' &middot; ' + secs + 's';
        }, 1000);
        try {
            return await fetch(url, options);
        } finally {
            clearInterval(timer);
        }
    }

    async function runLoop(action, type) {
        appendLog(`>> <?php echo $msgstr['upd_action'] ?? 'Action:'; ?> ${action.toUpperCase()} <?php echo $msgstr['upd_batch_proc'] ?? '(Batch Processing)...'; ?>`);
        let finished = false;
        while (!finished) {
            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('update_type', type);

            const req = await fetchWithTicker('', {
                method: 'POST',
                body: formData
            });
            if (!req.ok) throw new Error(`HTTP Error ${req.status}`);

            const text = await req.text();
            let res;
            try {
                res = JSON.parse(text);
            } catch (e) {
                throw new Error("Invalid Server Response: " + text.substring(0, 100));
            }

            if (res.status === 'error') throw new Error(res.message);
            handleResponse(res);

            if (res.status === 'step_done' || res.status === 'done' || (res.message && res.message.toLowerCase().includes("completed successfully"))) {
                finished = true;
            }
        }
    }

    function handleResponse(res) {
        if (res.percent) {
            document.getElementById('pBar').style.width = res.percent + '%';
            document.getElementById('pText').innerHTML = res.percent + '%';
        }
        if (res.message) appendLog(res.message);
        if (res.status === 'done') document.getElementById('final-msg').style.display = 'block';
    }

    function appendLog(msg) {
        const box = document.getElementById('logBox');
        const cleanMsg = msg.replace(/\n/g, "<br>");
        box.innerHTML += `<div>${cleanMsg}</div>`;
        box.scrollTop = box.scrollHeight;
    }

    function reloadCleanCache() {
        window.location.href = 'update_manager.php?nocache=' + new Date().getTime();
    }

    // Automatically resume update after a self-update reload
    window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const autorun = params.get('autorun');
        if (autorun === 'partial' || autorun === 'completa') {
            const radio = document.querySelector(`input[name="u_type"][value="${autorun}"]`);
            if (radio) radio.checked = true;
            window.history.replaceState({}, document.title, 'update_manager.php');
            startUpdate(true);
        }
    });
</script>