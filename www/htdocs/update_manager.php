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

//List of files to be protected (backup and restoration)
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
$os = $_SERVER["SERVER_SOFTWARE"];
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

function isAdmin()
{
    if (!isset($_SESSION["login"]) or $_SESSION["profile"] != "adm") return false;
    return true;
}

function sendJsonResponse($status, $percent, $message, $debug = null)
{
    header('Content-Type: application/json');
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status' => $status,
        'percent' => $percent,
        'message' => $message,
        'debug' => $debug
    ]);
    exit;
}

function writeLog($message, $type = 'INFO')
{
    global $log_file;
    $timestamp = date('H:i:s');
    file_put_contents($log_file, "[$timestamp] [$type] " . $message . PHP_EOL, FILE_APPEND);
    $retval = htmlspecialchars($message);
    if ($type == 'warning') {
        $retval = "<span style='color:#ffc107'>" . $retval . "</span>";
    } elseif ($type == 'error' || $type == 'ERROR') {
        $retval = "<span style='color:#ff6666'>" . $retval . "</span>";
    }
    return "[$timestamp] " . $retval;
}

function recursiveDelete($dir)
{
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($dir);
}

function recursiveCopy($src, $dst)
{
    global $os_in_gitname;
    if (!is_dir($dst)) mkdir($dst, 0755, true);

    $len = strlen($src);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
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

function secureUpgradeFolder($dir)
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

function getLatestReleaseInfo()
{
    $api_url = 'https://api.github.com/repos/' . GITHUB_REPOSITORY . '/releases';
    $ch = curl_init();
    
    // TEMPORARY FOR TESTING
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
    
    if (curl_errno($ch)) throw new Exception('Curl Error: ' . curl_error($ch));

    $data = json_decode($response, true);
    
    if (isset($data['message']) && stripos($data['message'], 'rate limit') !== false) {
         throw new Exception('GitHub API Rate Limit Exceeded. A valid PAT is required for testing.');
    }

    if (isset($data['message'])) throw new Exception('GitHub API Error: ' . $data['message']);
    if (!is_array($data) || empty($data)) throw new Exception('No releases found.');

    return $data[0];
}

function checkLastError()
{
    $errmsg = error_get_last();
    if ($errmsg != null) {
        error_clear_last();
        throw new Exception("Detected problem: " . $errmsg["message"]);
    }
}

function restoreConfigs()
{
    global $logs, $PROTECTED_FILES, $backup_dir, $root_dir, $msgstr;
    $logs[] = writeLog($msgstr["upd_restoring_prot"] ?? "Restoring protected files...");
    foreach (PROTECTED_FILES as $rel_path) {
        $bkp = $backup_dir . '/' . basename($rel_path);
        $dest_file = $root_dir . '/' . $rel_path;
        if (file_exists($bkp)) {
            copy($bkp, $dest_file);
            $logs[] = writeLog(sprintf($msgstr["upd_restored"] ?? "Restored: %s", $rel_path));
        }
    }

    $opac_uploads_src = $root_dir . '/opac/uploads';
    $opac_uploads_bkp = $backup_dir . '/opac_uploads_backup';
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
    if (!isAdmin()) sendJsonResponse('error', 0, $msgstr["upd_access_denied"] ?? "Access Denied");

    // Re-include lang file inside AJAX context if available to load $msgstr
    if (file_exists("central/lang/dbadmin.php")) include("central/lang/dbadmin.php");

    $action = $_POST['ajax_action'];
    $logs = [];
    $phplogfile = ini_get("error_log");
    ini_set('display_errors', 0); 

    try {
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
                    copy($root_dir . '/' . $rel_path, $backup_dir . '/' . basename($rel_path));
                    $logs[] = writeLog(sprintf($msgstr["upd_backup_item"] ?? "Backup: %s", $rel_path));
                }
            }

            $opac_uploads_src = $root_dir . '/opac/uploads';
            $opac_uploads_bkp = $backup_dir . '/opac_uploads_backup';

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

            sendJsonResponse('continue', 5, implode("<br>", $logs));
        }

        // === STEP 2: DOWNLOAD ===
        if ($action === 'download') {
            $zip_path = $temp_dir . '/update.zip';

            if (file_exists($zip_path) && filesize($zip_path) > 1000000) {
                sendJsonResponse('continue', 20, writeLog($msgstr["upd_manual_zip"] ?? "Existing local update.zip found (Manual Upload). Skipping download."));
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
            sendJsonResponse('continue', 20, implode("<br>", $logs));
        }

        // === STEP 3: EXTRACT (BATCHED) ===
        if ($action === 'extract') {
            $zip_path = $temp_dir . '/update.zip';
            $unzip_dir = $temp_dir . '/unzipped';
            if (!is_dir($unzip_dir)) mkdir($unzip_dir, 0755, true);

            $zip = new ZipArchive;
            if ($zip->open($zip_path) !== TRUE) throw new Exception($msgstr["upd_err_zip"] ?? "Failed to open ZIP file.");

            $totalFiles = $zip->numFiles;
            $startIndex = isset($_SESSION['zip_extract_index']) ? $_SESSION['zip_extract_index'] : 0;
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
            sendJsonResponse('continue', 80, writeLog(sprintf($msgstr["upd_extract_ok"] ?? "Extraction completed. Total files: %d", $totalFiles)));
        }

        // === STEP 4: INSTALL & MIGRATION (BATCHED) ===
        if ($action === 'install') {
            $main_config = $root_dir . '/' . PROTECTED_FILES[0];
            if (!file_exists($main_config)) throw new Exception($msgstr["upd_err_config"] ?? "Main config not found");

            if (!defined('ABCD_UPDATE_MODE')) define('ABCD_UPDATE_MODE', true);
            require_once $main_config;
            checkLastError();

            global $cgibin_path, $db_path, $ABCD_scripts_path;
            $dest = [
                'htdocs' => rtrim($ABCD_scripts_path, '/\\'),
                'bases' => rtrim($db_path, '/\\'),
                'cgi-bin' => rtrim($cgibin_path, '/\\')
            ];

            $unzip_dir = $temp_dir . '/unzipped';
            $dirs = glob($unzip_dir . '/*');
            if (!isset($dirs[0])) throw new Exception($msgstr["upd_err_empty_zip"] ?? "Empty ZIP file extraction");
            $source_root = $dirs[0];

            $is_partial = ($_SESSION['update_type'] === 'partial');
            $startIndex = isset($_SESSION['install_index']) ? $_SESSION['install_index'] : 0;

            // Run pre-installation hooks and validation only once
            if ($startIndex === 0) {
                $logs[] = writeLog($msgstr["upd_mig_tasks"] ?? "Check for migration tasks...");
                $migration_script_zip = $source_root . '/www/htdocs/upgrade/update_actions.php';
                $migration_script_local = $upgrade_dir . '/update_actions.php';
                $script_to_run = '';

                if (file_exists($migration_script_zip)) {
                    $script_to_run = $migration_script_zip;
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
                    $PARTIAL_UPDATE_SOURCES[] = 'www/htdocs/version.php';
                    $_SESSION['partial_sources'] = $PARTIAL_UPDATE_SOURCES;

                    $logs[] = writeLog($msgstr["upd_perm_check"] ?? "Checking directory permissions before starting...");
                    $permission_errors = [];

                    foreach ($PARTIAL_UPDATE_SOURCES as $src) {
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

                    $permission_errors = array_unique($permission_errors);
                    if (!empty($permission_errors)) {
                        $error_msg = ($msgstr["upd_perm_denied"] ?? "Permission Denied! Cannot write to:") . "<br> - " . implode("<br> - ", $permission_errors);
                        throw new Exception($error_msg);
                    }
                    $logs[] = writeLog($msgstr["upd_perm_ok"] ?? "Permissions check passed. Proceeding with update.");
                } else {
                    $logs[] = writeLog($msgstr["upd_full_start"] ?? "Starting COMPLETE Update...", 'WARNING');
                    if (strlen($dest['htdocs']) < 5 || strlen($dest['bases']) < 5)
                        throw new Exception($msgstr["upd_err_paths"] ?? "Path variables seem unsafe. Aborting full update.");

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

                    if ($source_basename === 'update_manager.php' || $source_basename === 'version.php') {$d_path = $dest['htdocs'] . '/' .$source_basename;
                    } elseif (strpos($src, 'www/htdocs/') === 0) {$d_path = $dest['htdocs'] . '/' . str_replace('www/htdocs/', '', $src);
                    } elseif (strpos($src, 'www/bases-examples_Windows/') === 0) {$d_path = $dest['bases'] . '/' . str_replace('www/bases-examples_Windows/', '', $src);
                    } elseif (strpos($src, 'www/cgi-bin_Windows/') === 0) {$d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Windows/', '', $src);
                    } elseif (strpos($src, 'www/cgi-bin_Linux/') === 0) {$d_path = $dest['cgi-bin'] . '/' . str_replace('www/cgi-bin_Linux/', '', $src);
                    }

                    if ($d_path && file_exists($s_path)) {$logs[] = writeLog(sprintf($msgstr["upd_updating"] ?? "Updating '%s'...", basename($d_path)));
                        if (is_dir($s_path)) {
                            recursiveDelete($d_path);
                            checkLastError();
                            recursiveCopy($s_path,$d_path);
                            checkLastError();
                        } else {
                            $parent = dirname($d_path);
                            if (!is_dir($parent)) mkdir($parent, 0755, true);
                            copy($s_path,$d_path);
                            checkLastError();
                            if ($os_in_gitname == "Linux" && pathinfo($d_path, PATHINFO_EXTENSION) == "") chmod($d_path, 0755);
                        }
                    } elseif (!file_exists($s_path)) {
                        $logs[] = writeLog(sprintf($msgstr["upd_not_updated"] ?? "'%s' NOT updated (No source).", basename($d_path)), "warning");
                    }
                    
                    $_SESSION['install_index'] =$startIndex + 1;
                    $percent = 80 + round((($startIndex + 1) /$totalSources) * 20);
                    sendJsonResponse('continue', $percent, implode("<br>", $logs));
                } else {
                    restoreConfigs();
                    ini_set('display_errors', 1);
                    recursiveDelete($temp_dir);
                    unset($_SESSION['install_index']);
                    unset($_SESSION['partial_sources']);
                    sendJsonResponse('done', 100, $msgstr["upd_done"] ?? "Update completed successfully!");
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
$lang =$_SESSION["lang"];

include("central/common/get_post.php");
include("central/common/inc_nodb_lang.php");
include("central/lang/dbadmin.php");
include("central/lang/prestamo.php");
include("central/common/header.php");
include("central/common/institutional_info.php");
?>

<div class=sectionInfo>
    <div class=breadcrumb><?php echo ($msgstr["configure"] ?? "Configure") . " ABCD"; ?></div>
    <div class="actions">
        <?php include "central/common/inc_back.php"; ?>
    </div>
    <div class="spacer">&#160;</div>
</div>

<style>
    .update-container { max-width: 800px; margin: 20px auto; background: #343a40; color: #e9ecef; padding: 20px; border-radius: 5px; font-family: sans-serif; }
    h1 { color: #ffc107; border-bottom: 1px solid #555; padding-bottom: 10px; }
    .progress-wrapper { background: #555; height: 30px; border-radius: 15px; margin: 20px 0; overflow: hidden; position: relative; display: none; }
    .progress-bar { height: 100%; background: #28a745; width: 0%; transition: width 0.3s ease; }
    .progress-text { position: absolute; width: 100%; text-align: center; line-height: 30px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px #000; }
    .log-window { background: #212529; height: 300px; overflow-y: auto; padding: 10px; font-family: monospace; font-size: 13px; border: 1px solid #555; margin-top: 15px; display: none; color: #ccc; }
    .btn-action { background: #ffc107; border: none; padding: 15px 30px; color: #000; font-weight: bold; cursor: pointer; border-radius: 5px; font-size: 16px; width: 100%; margin-top: 10px; }
    .btn-action:disabled { background: #777; cursor: not-allowed; }
    .btn-action:hover { background: #e0a800; }
    .options { margin: 20px 0; border: 1px solid #555; padding: 15px; border-radius: 5px; background: #444; }
    .info-version { margin-bottom: 20px; border-left: 4px solid #ffc107; padding-left: 10px; }
    .info-box.error { background-color: #dc3545; color: #fff; padding: 15px; border-radius: 4px; }
</style>

<div class="all">
    <div class="update-container">
        <h1>ABCD Update Manager (v4.3)</h1>

        <?php if (!isAdmin()): ?>
            <div class="info-box error"><?php echo $msgstr["upd_access_denied"] ?? "Denied access: You are not allowed to run this script."; ?></div>
        <?php elseif (!extension_loaded('zip') || !extension_loaded('curl')): ?>
            <div class="info-box error"><?php echo $msgstr["upd_err_ext"] ?? "Critical Error: PHP Extensions 'zip' and 'curl' are required."; ?></div>
        <?php else:
            try {
                $release = getLatestReleaseInfo();
                $remote_ver =$release['tag_name'];
                $body_txt =$release['body'];
            } catch (Exception $e) {
                $remote_ver = ($msgstr["upd_err_fetch"] ?? "Error fetching info: ") . $e->getMessage();$body_txt = "";
            }
        ?>

            <div id="setup-panel">
                <div class="info-version">
                    <p><?php echo $msgstr["upd_curr_ver"] ?? "Current Version:"; ?> <strong><?php echo LOCAL_VERSION; ?></strong></p>
                    <p><?php echo $msgstr["upd_latest_ver"] ?? "Latest Version:"; ?> <strong><?php echo $remote_ver; ?></strong></p>
                    <?php if (!empty($body_txt)) echo "<pre style='background:#222; padding:10px; white-space: pre-wrap;'>" . htmlspecialchars($body_txt) . "</pre>"; ?>
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

                <button class="btn-action" onclick="startUpdate()" id="btnStart"><?php echo $msgstr["upd_btn_start"] ?? "START UPDATE PROCESS"; ?></button>
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
    async function startUpdate() {
        let conf_msg = "<?php echo $msgstr['upd_confirm'] ?? 'Are you sure you want to proceed?'; ?>";
        if (!confirm(conf_msg)) return;
        document.getElementById('setup-panel').style.display = 'none';
        document.getElementById('progressBox').style.display = 'block';
        document.getElementById('logBox').style.display = 'block';

        const type = document.querySelector('input[name="u_type"]:checked').value;
        try {
            await runStep('init', type);
            await runStep('download', type);
            await runLoop('extract', type);
            await runLoop('install', type); 
        } catch (e) {
            console.error(e);
            appendLog(`<span style="color:#ff6666">FATAL ERROR: ${e.message}</span>`);
            appendLog(`<span style="color:#ffc107"><?php echo ($msgstr['upd_err_more_log'] ?? 'More in log: ') . str_replace('\\', '/', $log_file); ?></span>`);
            document.getElementById('pBar').style.background = '#dc3545';
            alert("<?php echo $msgstr['upd_failed'] ?? 'Update Failed'; ?>");
        }
    }

    async function runStep(action, type) {
        appendLog(`>> Action: ${action.toUpperCase()}...`);
        const formData = new FormData();
        formData.append('ajax_action', action);
        formData.append('update_type', type);

        const req = await fetch('', { method: 'POST', body: formData });
        if (!req.ok) throw new Error(`HTTP Error ${req.status}`);

        const text = await req.text();
        let res;
        try { res = JSON.parse(text); } catch (e) { throw new Error("Invalid Server Response: " + text.substring(0, 100)); }

        handleResponse(res);
        if (res.status === 'error') throw new Error(res.message);
    }

    async function runLoop(action, type) {
        appendLog(`>> Action: ${action.toUpperCase()} (Batch Processing)...`);
        let finished = false;
        while (!finished) {
            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('update_type', type);

            const req = await fetch('', { method: 'POST', body: formData });
            if (!req.ok) throw new Error(`HTTP Error ${req.status}`);

            const text = await req.text();
            let res;
            try { res = JSON.parse(text); } catch (e) { throw new Error("Invalid Server Response: " + text.substring(0, 100)); }

            if (res.status === 'error') throw new Error(res.message);
            handleResponse(res);
            
            // Fix: Check for completion either by status or specific message
            if (res.status === 'done' || (res.message && res.message.toLowerCase().includes("completed successfully"))) {
                finished = true;
            }
        }
    }

    function handleResponse(res) {
        if(res.percent) {
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
</script>