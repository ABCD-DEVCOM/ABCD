<?php

/**
 * Name: translate.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Translation management file for the ABCD application.
 * This file handles the loading and management of translation strings for different languages.
 * changelog: 
 * 20260702 rogercgui Added support for cascading translation system using LanguageManager.
 */

session_start();
if (!isset($_SESSION["permiso"]["CENTRAL_EDHLPSYS"]) and !isset($_SESSION["permiso"]["CENTRAL_ALL"])) {
    header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");

$lang = $_SESSION["lang"];
$table = $arrHttp["table"];

// Define o caminho do cofre 'content'
$contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;
$targetFolder = $contentPath . "lang/" . $lang . "/";
$targetFile = $targetFolder . $table;

// Cria a pasta de forma segura
if (!is_dir($targetFolder)) {
    mkdir($targetFolder, 0777, true);
}

// Processa o formulário e constrói o arquivo
$fileContent = "";
$charCount = 0;
foreach ($arrHttp as $var => $value) {
    if (substr($var, 0, 4) == "msg_") {
        $key = substr($var, 4);
        $value = str_replace(array("\r", "\n"), "", $value);
        $value = stripslashes($value);

        $line = $key . "=" . $value . "\n";
        $fileContent .= $line;
        $charCount += strlen($line);
    }
}

// Grava exclusivamente na pasta content
$fp = @fopen($targetFile, "w");
if ($fp === false) {
    $contents_error = error_get_last();
    echo "<p style='color:red'>" . $msgstr["notok"] . " : " . $contents_error["message"] . "</p>";
    die;
}
$res = fwrite($fp, $fileContent);
fclose($fp);

$backtoscript = "../dbadmin/menu_traducir.php";
include("../common/header.php");
echo "<body>";
include("../common/institutional_info.php");
?>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr["traducir"] . ": " . $table; ?>
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
            echo "<h3 align=center>" . $msgstr["actualizados"] . " " . $lang . "/" . $table . ". " . $charCount . " " . $msgstr["characters"] . "</h3> ";
        } else {
            echo "<p style='color:red'>Erro ao escrever no cofre content.</p>";
        }
        ?>
    </div>
</div>
<?php include("../common/footer.php"); ?>