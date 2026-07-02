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

$table = $arrHttp["table"];
$backtoscript = "../dbadmin/menu_traducir.php";
$savescript = "javascript:Enviar()";

include("../common/inc_nodb_lang.php");
include("../common/header.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");

$guessed = $msgstr["undefined"];
if ($guessstatus == "basesdef") $guessed = $msgstr["basesdef"];
if ($guessstatus == "lang")     $guessed = $msgstr["lang"] . " " . $lang;
if ($guessstatus == "manual")   $guessed = $msgstr["manualset"];

global $langManager;
$table = $arrHttp["table"];
$lang  = $arrHttp["lang"];

$centralPath = defined('ABCD_CENTRAL_PATH') ? ABCD_CENTRAL_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
$contentPath = defined('ABCD_CONTENT_PATH') ? ABCD_CONTENT_PATH : dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR;

// 1. Carrega as chaves originais em ingl?s
$englishFile = $centralPath . "lang/en/" . $table;
$englishTerms = [];
if (file_exists($englishFile)) {
    $lines = file($englishFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $englishTerms[trim($key)] = trim($value);
    }
}

// 2. Carrega as tradu??es padr?o de f?brica (Core)
$coreFile = $centralPath . "lang/" . $lang . "/" . $table;
$coreTerms = [];
if ($lang !== 'en' && file_exists($coreFile)) {
    $lines = file($coreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $coreTerms[trim($key)] = trim($value);
    }
}

// 3. Carrega as customiza??es locais do usu?rio (Cofre 'content')
$customFile = $contentPath . "lang/" . $lang . "/" . $table;
$customTerms = [];
if (file_exists($customFile)) {
    $lines = file($customFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $customTerms[trim($key)] = trim($value);
    }
}

// 4. Monta o array final
$msgstrarr = [];
foreach ($englishTerms as $key => $enValue) {
    $currentValue = $customTerms[$key] ?? $coreTerms[$key] ?? '';
    $msgstrarr[$key] = [
        "code" => $key,
        "00"   => $enValue,
        $lang  => $currentValue
    ];
}
?>

<body>
    <script>
        function Enviar() {
            document.forma1.submit()
        }

        function doReload(selectvalue) {
            document.continuar.selcharset.value = selectvalue
            document.continuar.submit();
        }
    </script>
    <?php include("../common/institutional_info.php"); ?>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["traducir"] . ": " . $arrHttp["table"] ?>
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
        if ($table == "") {
            echo $msgstr["errsellang"] . "<p><a href=javascript:history.back()>" . $msgstr["regresar"] . "</a>";
            die;
        }
        ?>
        <div class="formContent">
            <a name="top"></a>
            <div align=center>
                <h3><?php echo $msgstr["translate"] . " " . $lang . "/" . $table ?></h3>
                <form name=continuar action=translate.php method=post>
                    <table style=text-align:right cellspacing=1 cellpadding=4>
                        <tr style=text-align:center bgcolor=#e7e7e7>
                            <th colspan=2><?php echo $msgstr["code"] ?></th>
                            <th><?php echo $msgstr["r_desde"] ?></th>
                        </tr>
                        <tr>
                            <td><?php echo $msgstr["show"] . " " . $table . " in"; ?></td>
                            <td><select name=selcharset id="selcharset" onchange="doReload(this.value)">
                                    <option value='ISO-8859-1' <?php echo $ichecked; ?>>ISO-8859-1</option>
                                    <option value='UTF-8' <?php echo $uchecked; ?>>UTF-8</option>
                                </select>
                            </td>
                            <td style="color:blue"> <?php echo $guessed; ?> </td>
                        </tr>
                    </table>
                    <input type=hidden name="table" value="<?php echo $table; ?>">
                    <input type=hidden name="lang" value="<?php echo $lang; ?>">
                </form>
            </div>
            <br>
            <div class="formContent">

                <form method=post action=translate_update.php name=forma1>
                    <input type=hidden name=lang value="<?php echo $lang; ?>">
                    <input type=hidden name=table value="<?php echo $table; ?>">
                    <table>
                        <tr bgcolor=#eeeeee>
                            <th><?php echo $msgstr["validation_U"] ?? 'Chave'; ?></th>
                            <th>00</th>
                            <th><?php echo $msgstr["transtext"]; ?></th>
                        </tr>
                        <?php
                        foreach ($msgstrarr as $var => $msgarr) {
                            echo "<tr >";
                            echo "<td style='color:darkred'>" . $msgarr["code"] . "</td>";
                            echo "<td style='color:blue'>" . $msgarr["00"] . "</td>";
                            $value = "";
                            if (isset($msgarr[$lang])) $value = $msgarr[$lang];
                            $msgname = "msg_" . $msgarr["code"];
                        ?>
                            <td width=75%><input type=text style='width:100%' name="<?php echo $msgname; ?>" value="<?php echo str_replace("\"", "&quot;", $value); ?>"></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </form>
                <br>
                <a href="#top"><?php echo $msgstr["up"] ?> <img src='/central/dataentry/img/up2.gif'></a>

            </div>
        </div>
        <?php echo include("../common/footer.php") ?>