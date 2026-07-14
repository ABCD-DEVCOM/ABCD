<?php

/**
 * Name: translate_update.php
 * Description: Translation management file for the ABCD application.
 * This file handles the loading and management of translation strings for different languages.
 */

session_start();
if (!isset($_SESSION["permiso"]["CENTRAL_EDHLPSYS"]) && !isset($_SESSION["permiso"]["CENTRAL_ALL"])) {
    header("Location: ../common/error_page.php");
    exit;
}

include("../common/get_post.php");
include("../config.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");

$lang   = $arrHttp["lang"] ?? $_SESSION["lang"] ?? 'en';
$table  = $arrHttp["table"] ?? '';
$plugin = $arrHttp["plugin"] ?? null;
$type   = $arrHttp["type"] ?? '';
$isRawFile = ($type === 'textarea');

if (empty($table)) {
    echo "Error: Table not specified.";
    die;
}

// Define o caminho do cofre 'content'
$contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;
$targetFolder = rtrim($contentPath, '/\\') . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;
$targetFile = $targetFolder . $table;

// Cria a pasta do idioma de forma segura, se não existir
if (!is_dir($targetFolder)) {
    mkdir($targetFolder, 0777, true);
}

$fileContent = "";
$charCount = 0;

if ($isRawFile) {
    // MODO ARQUIVO BRUTO / HTML
    // Salva o conteúdo do CKEditor diretamente no arquivo
    $fileContent = $_POST['file_content'] ?? '';
    $fileContent = stripslashes($fileContent);
    $charCount = strlen($fileContent);
} else {
    // MODO TRADICIONAL TABULAR
    // Processa o formulário revertendo a blindagem hexadecimal
    foreach ($arrHttp as $var => $value) {
        if (str_starts_with($var, "msg_")) {
            $hexKey = substr($var, 4);

            if (ctype_xdigit($hexKey)) {
                $key = hex2bin($hexKey);
            } else {
                continue;
            }

            $value = str_replace(["\r\n", "\r", "\n"], " ", $value);
            $value = stripslashes($value);

            $line = $key . "=" . $value . "\n";
            $fileContent .= $line;
            $charCount += strlen($line);
        }
    }
}

// Grava exclusivamente na pasta content (O Cofre)
$fp = @fopen($targetFile, "w");
if ($fp === false) {
    $contents_error = error_get_last();
    echo "<p style='color:red'>" . ($msgstr["notok"] ?? 'Error') . " : " . $contents_error["message"] . "</p>";
    die;
}
$res = fwrite($fp, $fileContent);
fclose($fp);

$backtoscript = "../dbadmin/menu_traducir.php";
$esc_charset = (isset($charset) && strtoupper($charset) === 'UTF-8') ? 'UTF-8' : 'ISO-8859-1';

include("../common/header.php");
echo "<body>";
include("../common/institutional_info.php");
?>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo ($msgstr["traducir"] ?? 'Translate') . ": " . htmlspecialchars($table, ENT_QUOTES, $esc_charset); ?>
        <?php if ($plugin) echo " (Plugin: " . htmlspecialchars($plugin, ENT_QUOTES, $esc_charset) . ")"; ?>
    </div>
    <div class="actions">
        <?php include "../common/inc_back.php" ?>
        <?php include "../common/inc_home.php" ?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php"; ?>
<div class="middle form">
    <div class="formContent">
        <?php
        if ($res !== false) {
            echo "<h3 align=center>" . ($msgstr["actualizados"] ?? 'Updated') . " " . htmlspecialchars($lang . "/" . $table, ENT_QUOTES, $esc_charset) . ". " . $charCount . " " . ($msgstr["characters"] ?? 'characters') . "</h3> ";
        } else {
            echo "<p style='color:red'>Erro ao escrever no cofre content.</p>";
        }
        ?>
    </div>
</div>
<?php include("../common/footer.php"); ?>