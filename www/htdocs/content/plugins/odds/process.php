<?php

/**
 * Name: process.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: This file is part of the ODDS plugin for the ABCD application. It handles the processing of form submissions, including validation, flattening of data into ISIS format, and storage in the ODDS ISIS database. The script also manages control number generation, auto-repair of missing control_number.cn files, and triggers auto-responder emails to both users and administrators. It ensures that only POST requests are processed and provides feedback on the success or failure of the operation.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */


if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

// Inicia a sessão no topo para ler o idioma antes de disparar os e-mails
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY: Block direct URL access (GET requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /plugin/odds/?action=form");
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$pluginPath = realpath(__DIR__);

global $msgstr;
if (!is_array($msgstr)) {
    $msgstr = [];
}
@include_once($abcdPath . "/lang/admin.php");
include($pluginPath . "/lang/odds.php");

/**
 * Gets the next Autoincrement Control Number from ISIS folder.
 */
function getControlNumber(string $base, string $dbPath): string|false
{
    $dir = rtrim($dbPath, '/\\') . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;
    $file = $dir . "control_number.cn";

    if (!is_dir($dir)) return false;

    // Auto-heal: Create the file if missing
    if (!file_exists($file)) {
        @file_put_contents($file, "0");
        @chmod($file, 0666);
    }

    if (is_writable($file)) {
        chmod($file, 0555); // Protect during write
        $cn = (int) file_get_contents($file);
        $cn++;

        $bakFile = $dir . "control_number.bak";
        if (file_exists($bakFile)) @unlink($bakFile);

        @rename($file, $bakFile);
        @file_put_contents($file, (string) $cn);
        chmod($file, 0666); // Restore write permission

        return str_pad((string) $cn, 6, '0', STR_PAD_LEFT);
    }

    return false;
}

/**
 * Flattens array into ISIS WXIS <tag>value</tag> format
 */
function flattenPostData(array $post, string $cn): string
{
    $capturedValue = "";
    $post["tag100"] = date("Ymd");
    $processedTags = [];

    foreach ($post as $key => $line) {
        if (str_starts_with($key, "tag")) {
            $keyNum = str_pad(trim(substr($key, 3)), 4, '0', STR_PAD_LEFT);
            $line = stripslashes($line);

            if (isset($processedTags[$keyNum])) {
                $processedTags[$keyNum] .= "\n" . $line;
            } else {
                $processedTags[$keyNum] = $line;
            }
        }
    }

    $processedTags['0001'] = $cn;

    $level = $processedTags['0006'] ?? '';
    $processedTags['0005'] = match ($level) {
        'as' => 'S',
        'am', 'amc' => 'M',
        'at' => 'T',
        'al', 'ad', 'ar', 'cj', 'aj' => 'L',
        default => ''
    };

    foreach ($processedTags as $key => $line) {
        $values = explode("\n", $line);
        foreach ($values as $v) {
            $v = trim($v);
            if ($v !== '') {
                $capturedValue .= urlencode('<') . $key . urlencode('>') . urlencode($v) . urlencode('</') . $key . urlencode('>');
            }
        }
    }
    return $capturedValue;
}

// ----------------------------------------------------------------------------------
// INIT PROCESSING
// ----------------------------------------------------------------------------------
$base = $_POST['base'] ?? 'odds';

if (isset($_POST["tag900"]) && str_starts_with($_POST["tag900"], "others_") && !empty($_POST["tag900_other"])) {
    $_POST["tag900"] = trim($_POST["tag900_other"]);
}
unset($_POST["tag900_other"]);

if (isset($_POST["tag520"]) && $_POST["tag520"] === "XX" && !empty($_POST["tag520_other"])) {
    $_POST["tag520"] = trim($_POST["tag520_other"]);
}
unset($_POST["tag520_other"]);

$cn = getControlNumber($base, $dbPath);
if ($cn === false) {
    $errorMsg = $msgstr["odds_nocontrolnr"] ?? "No control number found.";
    die("<div style='color:red; font-family:Verdana; font-weight:bold; margin:20px;'>&nbsp;&nbsp;{$errorMsg}</div>");
}

$tags = flattenPostData($_POST, $cn);

require_once $abcdPath . '/config.php';
$actparfolder = "par/";
$cipar = $base . ".par";
$IsisScript = $xWxis . "actualizar.xis";
$query = "&base=" . $base . "&cipar=" . $dbPath . $actparfolder . $cipar . "&Mfn=New&Opcion=crear&ValorCapturado=" . $tags;

$_GET['IsisScript'] = $IsisScript;
$url = $wxisUrl . "?IsisScript=" . $IsisScript . $query . "&cttype=Y&path_db=" . $dbPath;

require_once $abcdPath . "/common/wxis_llamar.php";

// ----------------------------------------------------------------------------------
// UI FEEDBACK AND EMAIL AUTO-RESPONDER (PRG PATTERN)
// ----------------------------------------------------------------------------------
$isSuccess = isset($contenido[1]) && str_starts_with($contenido[1], "MFN:");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($isSuccess) {
    $mfnCreated = trim(str_replace("MFN:", "", $contenido[1]));
    $_SESSION['odds_success_mfn'] = $mfnCreated;
    $_SESSION['odds_base'] = $base;
    $_SESSION['odds_email_status'] = "";

    // TRIGGER AUTO-RESPONDERS
    try {
        $smtpConfigFile = __DIR__ . '/config/smtp.php';
        if (file_exists($smtpConfigFile)) {
            require_once __DIR__ . '/OddsMailer.php';
            $smtpConfig = require $smtpConfigFile;

            $mailer = new OddsMailer($smtpConfig['host'], $smtpConfig['port'], $smtpConfig['username'], $smtpConfig['password'], $smtpConfig['from_email'], $smtpConfig['from_name']);

            $userEmail = trim($_POST['tag528'] ?? '');
            $userName  = trim($_POST['tag510'] ?? '');
            $docTitle  = trim($_POST['tag012'] ?? '');

            // --- Template-based User Confirmation Email ---
            $defDir = rtrim($dbPath, '/\\') . '/odds/def/' . $lang;
            $confirmTplPath = $defDir . '/odds_request_confirm_mail.tab';

            if (file_exists($confirmTplPath)) {
                $confirmTpl = file_get_contents($confirmTplPath);
                $lines = explode("\n", $confirmTpl);
                $subject = '';
                if (isset($lines[0]) && stripos(trim($lines[0]), 'subject =') === 0) {
                    $subject = trim(substr(trim($lines[0]), 9));
                    unset($lines[0]);
                }
                $body = trim(implode("\n", $lines));
            } else {
                // Safe fallback if the template file is missing
                $subject = $msgstr['odds_conf_subject'] ?? "Document Request Received - MFN: [mfn]";
                $body    = $msgstr['odds_conf_body'] ?? "<h3>Hello [name],</h3><p>We successfully received your request for the document <b>\"[title]\"</b>.</p><p>Your tracking number is: <b>[mfn]</b></p><p>We will notify you once it is processed.</p>";
            }

            // Replace dynamic tags
            $replacements = [
                '[name]'  => $userName,
                '[title]' => $docTitle,
                '[mfn]'   => $mfnCreated,
                '[date]'  => date('d/m/Y')
            ];

            $userSubject = str_replace(array_keys($replacements), array_values($replacements), $subject);
            $userBody    = str_replace(array_keys($replacements), array_values($replacements), $body);

            if (!empty($userEmail)) {
                $mailer->sendCustomEmail($userEmail, $userSubject, $userBody);
            }

            // --- NEW: Better Internal Admin Alert Email ---
            $adminSubject = "New ODDS Request - MFN: {$mfnCreated}";
            $adminBody = "<h3 style='color:#3669c6;'>New Document Request</h3>
                          <p>A new request has been submitted by <b>{$userName}</b> ({$userEmail}).</p>
                          <p><b>Tracking Number:</b> {$mfnCreated}<br>
                          <b>Document Reference:</b> {$docTitle}</p>
                          <p>Please log in to the ABCD backoffice to process this request.</p>";

            $mailer->sendCustomEmail($smtpConfig['from_email'], $adminSubject, $adminBody);


            $_SESSION['odds_email_status'] = "<div style='color:green; font-size:12px; margin: 10px 0 0 10px;'>&#10004; Confirmation emails successfully sent.</div>";
        }
    } catch (Exception $e) {
        $_SESSION['odds_email_status'] = "<div style='color:orange; font-size:12px; margin: 10px 0 0 10px;'>&#9888; Record saved, but email notification failed.</div>";
    }

    // REDIRECIONA PARA A TELA LIMPA
    header("Location: /service/odds/?action=success");
    exit;
} else {
    die("<b>Erro fatal ao tentar salvar no banco de dados ISIS.</b>");
}