<?php

/**
 * @file       stats_ajax.php
 * @brief      This script processes AJAX requests for generating statistical tables in the ABCD.
 * It handles character encoding conversions between JavaScript (UTF-8) and PHP (ISO-8859-1),
 * retrieves data based on the provided parameters, and returns an HTML table as a response.
 * @author      Roger Craveiro Guilherme
 * @date        2026-05-08
 * 
 * @details
 * The script starts by initializing the session and including necessary configuration and language files.
 */

session_start();
include("../common/get_post.php");
include("../config.php");
include("../lang/statistics.php");
include("../lang/admin.php");

if (!isset($_SESSION["permiso"])) die("Sessão expirada.");

if (isset($arrHttp["tables"])) $arrHttp["tables"] = urldecode($arrHttp["tables"]);
if (isset($arrHttp["rows"])) $arrHttp["rows"] = urldecode($arrHttp["rows"]);
if (isset($arrHttp["cols"])) $arrHttp["cols"] = urldecode($arrHttp["cols"]);
if (isset($arrHttp["proc"])) $arrHttp["proc"] = urldecode($arrHttp["proc"]);
if (isset($arrHttp["Expresion"])) $arrHttp["Expresion"] = urldecode($arrHttp["Expresion"]);

include("tables_generate_inc.php");

$tab = array();
$rows = array();
$cols = array();
$tabs = array();
$tipo = array();
$filter_date = array();

$tab_vars = LeerVariables($db_path, $arrHttp, $lang_db);

// THE ENGINE THAT GETS THE JOB DONE AND MEETS THE SPECIFICATIONS IN THE TABLE CONFIGURATION
ExecutarEstatisticasMultiLoop($arrHttp, $lang_db, $tab_vars, $db_path, $xWxis);

ob_start();

if (empty($tab)) {
    $msg_no_data = isset($msgstr["no_data_found"]) ? $msgstr["no_data_found"] : "Nenhum dado encontrado para os parâmetros selecionados. Verifique a expressão ou o intervalo MFN.";
    echo "<div style='padding:20px; color:#d9534f; background:#f9f2f2; border:1px solid #dca7a7; text-align:center; border-radius:4px;'>" . toPageEncoding($msg_no_data) . "</div>";
} else {
    ConstruirSalida($tab, $tabs, $tipo, $rows, $cols);
}

// CAPTURE THE ENTIRE BUFFER (WHICH IS CURRENTLY IN ISO-8859-1)
$html_final = ob_get_clean();

header("Content-Type: text/html; charset=UTF-8");

// FINAL BATCH CONVERSION TO UTF-8
if ($charset == "ISO-8859-1" || $charset == "ANSI") {
    echo mb_convert_encoding($html_final, 'UTF-8', 'ISO-8859-1');
} else {
    echo $html_final;
}
?>