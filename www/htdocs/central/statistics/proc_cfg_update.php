<?php
session_start();
if (!isset($_SESSION["permiso"])) die;
include("../common/get_post.php");
include("../config.php");
include("../lang/dbadmin.php");
include("../lang/statistics.php");
include("../common/header.php");
?>

<body>
    <?php
    if (isset($arrHttp["encabezado"])) {
        include("../common/institutional_info.php");
        $encabezado = "&encabezado=s";
    } else {
        $encabezado = "";
    }
    ?>
    <div class="sectionInfo">
        <div class="breadcrumb"><?php echo $msgstr["stats"] . " - " . $msgstr["stat_cfg_procs"] . ": " . $arrHttp["base"]; ?></div>
        <div class="actions">
            <?php
            if (isset($arrHttp["from"]) and $arrHttp["from"] == "statistics") $backtoscript = "tables_generate.php";
            else $backtoscript = "../dbadmin/menu_modificardb.php";
            include "../common/inc_back.php";
            ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php
    $ayuda = "stats_config_tabs.html";
    include "../common/inc_div-helper.php";
    ?>
    <div class="middle form">
        <div class="formContent">
            <?php
            $file = $db_path . $arrHttp["base"] . "/def/" . $lang . "/proc.cfg";
            $fp = fopen($file, "w");
            $vc = explode("\n", $arrHttp["ValorCapturado"]);
            foreach ($vc as $value) {
                $r = fwrite($fp, $value . "\n");
                echo $value . "<br>";
            }
            $r = fclose($fp);
            echo "<br><h4>" . $arrHttp["base"] . "/" . $lang . "/def/proc.cfg" . " " . $msgstr["updated"] . "</h4>";

            include("inc_stat_menu.php");
            ?>
        </div>
    </div>
    <?php include("../common/footer.php"); ?>