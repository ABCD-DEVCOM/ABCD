<?php

/**
 * Name: translate.php
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

$table  = $arrHttp["table"] ?? '';
$lang   = $arrHttp["lang"] ?? $_SESSION["lang"] ?? 'en';
$plugin = $arrHttp["plugin"] ?? null;
$type   = $arrHttp["type"] ?? '';
$isRawFile = ($type === 'textarea'); // Flag para determinar o modo de edição

$backtoscript = "../dbadmin/menu_traducir.php";
$savescript = "javascript:Enviar()";

include("../common/inc_nodb_lang.php");
include("../common/header.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");

$guessed = $msgstr["undefined"] ?? 'Undefined';
if (isset($guessstatus)) {
    if ($guessstatus == "basesdef") $guessed = $msgstr["basesdef"];
    if ($guessstatus == "lang")     $guessed = $msgstr["lang"] . " " . $lang;
    if ($guessstatus == "manual")   $guessed = $msgstr["manualset"];
}

global $langManager;

$centralPath = defined('ABCD_CENTRAL_PATH') ? ABCD_CENTRAL_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
$contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;

// Define o caminho base
if ($plugin) {
    $safePlugin = preg_replace('/[^a-zA-Z0-9_\-]/', '', $plugin);
    $factoryPath = rtrim($contentPath, '/\\') . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $safePlugin . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
} else {
    $factoryPath = rtrim($centralPath, '/\\') . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
}

$customLangPath = rtrim($contentPath, '/\\') . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;

// --- LÓGICA DE CARREGAMENTO ---

if ($isRawFile) {
    // MODO ARQUIVO BRUTO (TEXTAREA / HTML)
    $englishFile = $factoryPath . "en/" . $table;
    $englishContent = file_exists($englishFile) ? file_get_contents($englishFile) : '';

    $coreFile = $factoryPath . $lang . "/" . $table;
    $coreContent = ($lang !== 'en' && file_exists($coreFile)) ? file_get_contents($coreFile) : '';

    $customFile = $customLangPath . $table;
    $customContent = file_exists($customFile) ? file_get_contents($customFile) : '';

    $currentContent = $customContent ?: ($coreContent ?: $englishContent);
} else {
    // MODO TRADICIONAL (TABULAR KEY=VALUE)
    function parseTabFile(string $filePath): array
    {
        $terms = [];
        if (file_exists($filePath)) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2);
                $terms[trim($key)] = trim($value);
            }
        }
        return $terms;
    }

    $englishTerms = parseTabFile($factoryPath . "en/" . $table);
    $coreTerms = ($lang !== 'en') ? parseTabFile($factoryPath . $lang . "/" . $table) : [];
    $customTerms = parseTabFile($customLangPath . $table);

    $allKeys = array_keys($englishTerms + $coreTerms + $customTerms);
    sort($allKeys);

    $msgstrarr = [];
    foreach ($allKeys as $key) {
        $msgstrarr[$key] = [
            "code" => $key,
            "00"   => $englishTerms[$key] ?? $coreTerms[$key] ?? '',
            $lang  => $customTerms[$key] ?? $coreTerms[$key] ?? $englishTerms[$key] ?? ''
        ];
    }
}

$esc_charset = (isset($charset) && strtoupper($charset) === 'UTF-8') ? 'UTF-8' : 'ISO-8859-1';
?>

<body>
    <script>
        function Enviar() {
            if (typeof CKEDITOR !== 'undefined') {
                for (var instanceName in CKEDITOR.instances) {
                    CKEDITOR.instances[instanceName].updateElement();
                }
            }
            document.forma1.submit();
        }

        function doReload(selectvalue) {
            document.continuar.selcharset.value = selectvalue;
            document.continuar.submit();
        }
    </script>
    <?php include("../common/institutional_info.php"); ?>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo ($msgstr["traducir"] ?? 'Translate') . ": " . htmlspecialchars($table, ENT_QUOTES, $esc_charset); ?>
            <?php if ($plugin) echo " (Plugin: " . htmlspecialchars($plugin, ENT_QUOTES, $esc_charset) . ")"; ?>
        </div>
        <div class="actions">
            <?php include "../common/inc_save.php" ?>
            <?php include "../common/inc_back.php" ?>
            <?php include "../common/inc_home.php" ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php
    include "../common/inc_div-helper.php";
    $ichecked = "";
    $uchecked = "";
    if (isset($selcharset) && $selcharset == "UTF-8")
        $uchecked = "selected";
    else
        $ichecked = "selected";
    ?>
    <div class="middle form">
        <?php
        if (empty($table)) {
            echo ($msgstr["errsellang"] ?? 'Language not selected') . "<p><a href=javascript:history.back()>" . ($msgstr["regresar"] ?? 'Back') . "</a>";
            die;
        }
        ?>
        <div class="formContent">
            <a name="top"></a>
            <div align=center>
                <h3><?php echo ($msgstr["translate"] ?? 'Translate') . " " . htmlspecialchars($lang . "/" . $table, ENT_QUOTES, $esc_charset) ?></h3>
                <form name=continuar action=translate.php method=post>
                    <table style="text-align:right" cellspacing=1 cellpadding=4>
                        <tr style="text-align:center; background-color:#e7e7e7">
                            <th colspan=2><?php echo $msgstr["code"] ?? 'Code' ?></th>
                            <th><?php echo $msgstr["r_desde"] ?? 'Source' ?></th>
                        </tr>
                        <tr>
                            <td><?php echo ($msgstr["show"] ?? 'Show') . " " . htmlspecialchars($table, ENT_QUOTES, $esc_charset) . " in"; ?></td>
                            <td><select name=selcharset id="selcharset" onchange="doReload(this.value)">
                                    <option value='ISO-8859-1' <?php echo $ichecked; ?>>ISO-8859-1</option>
                                    <option value='UTF-8' <?php echo $uchecked; ?>>UTF-8</option>
                                </select>
                            </td>
                            <td style="color:blue"> <?php echo htmlspecialchars($guessed, ENT_QUOTES, $esc_charset); ?> </td>
                        </tr>
                    </table>
                    <input type=hidden name="table" value="<?php echo htmlspecialchars($table, ENT_QUOTES, $esc_charset); ?>">
                    <input type=hidden name="lang" value="<?php echo htmlspecialchars($lang, ENT_QUOTES, $esc_charset); ?>">
                    <?php if ($plugin): ?>
                        <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($plugin, ENT_QUOTES, $esc_charset); ?>">
                    <?php endif; ?>
                    <?php if ($type): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, $esc_charset); ?>">
                    <?php endif; ?>
                </form>
            </div>
            <br>
            <div class="formContent">

                <form method=post action=translate_update.php name=forma1>
                    <input type=hidden name=lang value="<?php echo htmlspecialchars($lang, ENT_QUOTES, $esc_charset); ?>">
                    <input type=hidden name=table value="<?php echo htmlspecialchars($table, ENT_QUOTES, $esc_charset); ?>">
                    <?php if ($plugin): ?>
                        <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($plugin, ENT_QUOTES, $esc_charset); ?>">
                    <?php endif; ?>
                    <?php if ($type): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, $esc_charset); ?>">
                    <?php endif; ?>

                    <?php if ($isRawFile): ?>
                        <!-- INTERFACE PARA ARQUIVOS BRUTOS / HTML (CKEDITOR) -->
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight: bold; color: blue;">00 (Original / Inglês):</label>
                            <div style="background: #f1f1f1; padding: 10px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; color: #555;">
                                <?php echo htmlspecialchars($englishContent, ENT_QUOTES | ENT_SUBSTITUTE, $esc_charset); ?>
                            </div>
                        </div>

                        <div>
                            <label style="font-weight: bold;"><?php echo $msgstr["transtext"] ?? 'Mensagem Traduzida'; ?>:</label>
                            <textarea id="file_content_editor" name="file_content" style="width:100%; height:300px;"><?php echo htmlspecialchars($currentContent, ENT_QUOTES | ENT_SUBSTITUTE, $esc_charset); ?></textarea>
                        </div>
                    <?php else: ?>
                        <!-- INTERFACE TABULAR (TRADICIONAL ABCD) -->
                        <table width="100%">
                            <tr bgcolor="#eeeeee">
                                <th width="20%"><?php echo $msgstr["validation_U"] ?? 'Key'; ?></th>
                                <th width="40%">00 (Original)</th>
                                <th width="40%"><?php echo $msgstr["transtext"] ?? 'Translated Text'; ?></th>
                            </tr>
                            <?php
                            foreach ($msgstrarr as $var => $msgarr) {
                                echo "<tr>";

                                $safeCode = htmlspecialchars($msgarr["code"] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, $esc_charset);
                                $safe00   = htmlspecialchars($msgarr["00"] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, $esc_charset);

                                echo "<td style='color:darkred; word-break: break-all;'>" . $safeCode . "</td>";
                                echo "<td style='color:blue; word-break: break-all;'>" . $safe00 . "</td>";

                                $value = $msgarr[$lang] ?? '';
                                $msgname = "msg_" . bin2hex($msgarr["code"]);

                                echo "<td><input type='text' style='width:100%' name=\"$msgname\" value=\"" . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $esc_charset) . "\"></td>";
                                echo "</tr>";
                            }
                            ?>
                        </table>
                    <?php endif; ?>
                </form>
                <br>
                <a href="#top"><?php echo $msgstr["up"] ?? 'Up' ?> <img src='/central/dataentry/img/up2.gif'></a>

            </div>
        </div>

        <?php if ($isRawFile): ?>
            <!-- Inicialização do CKEditor para Modo HTML/Raw -->
            <script src="<?php echo $server_url . "/" . $app_path . "/ckeditor/ckeditor.js"; ?>"></script>
            <script>
                if (typeof CKEDITOR !== 'undefined') {
                    CKEDITOR.replace('file_content_editor', {
                        height: 300,
                        toolbar: 'Basic',
                        removeButtons: 'Anchor,Subscript,Superscript,Strike,Styles,SpecialChar'
                    });
                }
            </script>
        <?php endif; ?>

        <?php include("../common/footer.php") ?>