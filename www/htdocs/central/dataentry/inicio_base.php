<?php
    /* Modifications
2021-03-02 fho4abcd Replaced helper code fragment by included file
2021-03-15 fho4abcd Replaced dbinfo code by included file
2021-04-15 fho4abcd use charset from config.php
2022-06-19 fho4abcd corrected html + translations + removed display of <base>/modulos.dat (unknown file)
2025-12-23 fho4abcd HTML5
2026-05-05 rogercgui Centralized the display of the base name, charset and maxmfn, and the treatment of errors and warnings, in a single block of code, to improve readability and maintainability.
2026-11-16 fho4abcd Improved html code, removed duplicate check for fdt
2026-11-17 fho4abcd Removed linebreak from MAXMFN
2026-11-18 fho4abcd Linebreak removal moved to inc_get-dbinfo.php
*/
session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}

//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
include("../common/get_post.php");
include('../config.php');
include("../lang/admin.php");
include("../lang/dbadmin.php");

unset($_SESSION["tagsel"]);
unset($_SESSION["count"]);

//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
if (!isset($arrHttp["base"])) die;

include("../common/header.php");
?>
<body>
    <script>
        function CreateFiles() {
            document.CreateFiles.action = "../dbadmin/chk_dbdef.php"
            document.CreateFiles.encabezado.value = "s"
            document.CreateFiles.target = "_top"
            document.CreateFiles.submit();
        }
    </script>

    <form name="CreateFiles" onSubmit="return false" method="post" accept-charset=utf-8>
        <input type=hidden name="encabezado" value="s">
        <input type=hidden name="base" value="<?php echo $arrHttp["base"] ?>">
    </form>

    <?php $wiki_help = "Entrada_de_datos";
    include "../common/inc_div-helper.php"
    ?>
    <div>
        <?php
        $archivofdt = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".fdt";
        $cont = "";
        $warning = "";

        if (!file_exists($archivofdt)) {
            $archivofdt = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
            if (!file_exists($archivofdt)) {
                echo "<h4>" . $msgstr["misfile"] . " " . $archivofdt."</h4>";
                echo "<p>" . $msgstr["cf_notice"] . "</p>";
                echo '<a class="button" href="javascript:CreateFiles();">' . $msgstr["cf_createfiles"] . '</a>';
                $cont = "N";
            }
        }
        $archivo = $db_path . $arrHttp["base"] . "/data/" . $arrHttp["base"] . ".fst";
        if (!file_exists($archivo)) {
            echo  "<h4>" . $msgstr["fatal"] . ".. " . $msgstr["misfile"] . "  " . $arrHttp["base"] . "/data/" . $arrHttp["base"] . ".fst";
            $cont = "N";
        }

        $archivo = $db_path . $arrHttp["base"] . "/pfts/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".pft";
        if (!file_exists($archivo)) {
            $archivo = $db_path . $arrHttp["base"] . "/pfts/" . $lang_db . "/" . $arrHttp["base"] . ".pft";
            if (!file_exists($archivo))
                $warning = "<br><h6>" . $msgstr["warning"] . "... " . $msgstr["misfile"] . " " . $arrHttp["base"] . "/pfts/" . $_SESSION['lang'] . "/" . $arrHttp["base"] . ".pft";
        }


        if ($cont == "N") die;

        // Get info about the current database from the database
        include("../common/inc_get-dbinfo.php");
        $LOC_MAXMFN=$arrHttp["MAXMFN"];
        $i = -1;

        //The available output formats are read
        unset($fp);
        if (!isset($arrHttp["inicio"])) {   //indica que no se van a colocar los formatos en el toolbar
            if (file_exists($db_path . $arrHttp["base"] . "/pfts/" . $_SESSION["lang"] . "/formatos.dat")) {
                $fp = file($db_path . $arrHttp["base"] . "/pfts/" . $_SESSION["lang"] . "/formatos.dat");
            } else {
                if (file_exists($db_path . $arrHttp["base"] . "/pfts/" . $lang_db . "/formatos.dat")) {
                    $fp = file($db_path . $arrHttp["base"] . "/pfts/" . $lang_db . "/formatos.dat");
                }
            }
            echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options.length=0\n";
            $i = -1;
            if (isset($fp)) {
                foreach ($fp as $linea) {
                    if (trim($linea) != "") {
                        $linea = trim($linea);
                        $ll = explode('|', $linea);
                        $cod = $ll[0];
                        $nom = $ll[1];
                        if (
                            isset($_SESSION["permiso"][$arrHttp["base"] . "_pft_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_pft_" . $ll[0]]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"])
                            or isset($_SESSION["permiso"]["CENTRAL_ALL"])
                        ) {
                            $i = $i + 1;
                            echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i]=new Option('$nom','$cod')\n";
                        }
                    }
                }
            }
            $i = $i + 1;
            if (isset($_SESSION["permiso"][$arrHttp["base"] . "_pft_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or $_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"]) {
                echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i]=new Option('" . $msgstr["noformat"] . "','')\n";
                echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.formato.options[$i+1]=new Option('" . $msgstr["all"] . "','ALL')\n";
            }
            unset($fp);
            echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.wks.options.length=0\n";
            //Se leen las hojas de entrada disponibles
            if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/formatos.wks")) {
                $fp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/formatos.wks");
            } else {
                if (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/formatos.wks"))
                    $fp = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/formatos.wks");
            }
            $i = 0;
            $wks_p = array();
            if (isset($fp)) {
                foreach ($fp as $linea) {
                    if (trim($linea) != "") {
                        $linea = trim($linea);
                        $l = explode('|', $linea);
                        $cod = trim($l[0]);
                        $nom = trim($l[1]);
                        if (
                            isset($_SESSION["permiso"][$arrHttp["base"] . "_fmt_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_fmt_" . $cod])
                            or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"])
                        ) {
                            $i = $i + 1;
                            $wks_p[$cod] = "Y";
                            echo "if (top.ModuloActivo==\"catalog\") top.menu.document.forma1.wks.options[$i]=new Option('$nom','$cod')\n";
                        }
                    }
                }
            }
            $i = $i + 1;
            //Se lee la tabla de tipos de registro
            unset($fp);
            if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/typeofrecord.tab")) {
                $fp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/typeofrecord.tab");
            } else {
                if (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/typeofrecord.tab"))
                    $fp = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/typeofrecord.tab");
            }
            $i = 0;
            $typeofr = "";
            if (isset($fp)) {
                foreach ($fp as $linea) {
                    if ($i == 0) {
                        $l = explode(" ", $linea);
                        echo "top.tl='" . trim($l[0]) . "'\n";
                        if (isset($l[1]))
                            echo "top.nr='" . trim($l[1]) . "'\n";
                        else
                            echo "top.nr=''\n";
                        $i = 1;
                    } else {
                        if (trim($linea) != "") {
                            $l = explode('|', $linea);
                            $cod = $l[0];
                            $ix = strpos($cod, ".");
                            $cod = substr($cod, 0, $ix);
                            if (isset($wks_p[$cod]))
                                $typeofr .= trim($linea) . "$$$";
                        }
                    }
                }
                echo "top.typeofrecord=\"$typeofr\"\n";
            } else {
                echo "top.typeofrecord=\"\"\n";
            }
        }

        //The fdt is read to determine the prefix and extraction format of the main entry index field
        //Existence of the file is checked above
        unset($fp);
        $fp = file($archivofdt);
        $pi = "";
        $fe = "";
        $HTML = "";
        $URL = "";
        foreach ($fp as $linea) {
            if (trim($linea) != "") {
                $fdt = explode('|', $linea);
                if ($fdt[7] == "B") $HTML = $fdt[1];  //LOAD EXTERNAL TEXT FILE  IN THIS TAG
                if ($fdt[7] == "H") $URL = $fdt[1];   //URL TO TH DOCUMENT LOADED IN $HTML
                if ($fdt[3] == 1) {          //MAIN FIELD ALPHABETIC INDEX
                    $pi = $fdt[12];
                    $fe = $fdt[13];
                    if (trim($fe == "")) {
                        $fe = "v" . trim($fdt[1]);
                    }
                }
            }
        }
        ?>
        <div style='text-align: center;'>
            <!-- Header with the name of the base-->
            <h2 style='margin: 40px 0 15px 0; color:var(--abcd-blue);'>
                <i class='fas fa-database'></i>&nbsp;<?php echo $msgstr["bd"] . ": ";?><span style='text-transform: uppercase;'><?php echo $arrHttp["base"];?></span>
            </h2>
            <!-- Main information-->
            <div style='margin-bottom: 15px;'>
                <span class='badge'><i class='fas fa-code'></i>&nbsp;<?php echo $charset;?></span>
                <span><?php echo $msgstr["maxmfn"] . ": ";?><strong style='color: var(--abcd-red); font-size: 1.2rem;'><?php echo $LOC_MAXMFN;?></strong></span>
            </div>
            <?php
            // Treatment of Alerts and Errors (maintained with the native .alert class)
            if ($arrHttp["BD"] == "N") {
                echo "<div class='alert' style='text-align: left; margin-bottom: 10px; font-size: 0.95rem;'>" . $msgstr["database"] . " " . $msgstr["ne"] . "</div>";
            }
            if ($arrHttp["IF"] == "N") {
                echo "<div class='alert' style='text-align: left; margin-bottom: 10px; font-size: 0.95rem;'>" . $msgstr["if"] . " " . $msgstr["ne"] . "</div>";
            }
            if ($arrHttp["EXCLUSIVEWRITELOCK"] != 0) {
                echo "<div class='alert' style='text-align: left; margin-bottom: 10px; font-size: 0.95rem;'>" . $msgstr["database"] . " " . $msgstr["exwritelock"] . "=" . $arrHttp["EXCLUSIVEWRITELOCK"] . ". " . $msgstr["contactdbadm"] . "</div>";
                echo "<script>top.lock_db='Y'</script>";
            }
            if (trim($warning) != "") {
                echo "<div class='alert' style='text-align: left; margin-bottom: 10px; font-size: 0.95rem;'>" . $warning . "</div>";
            }
            ?>
            <!--Footer with technical information -->
            <div style='font-size: 0.85rem;  margin: 15px 0 40px;'>
            <?php
            if ($wxisUrl != "") {
                ?>
                <i class='fas fa-cogs'></i>&nbsp;<?php echo $msgstr['showisisversion'].": ";?>
                <span style='font-family:monospace;color:var(--abcd-blue);'><?php echo $wxisUrl;?></span>
                <?php
            } else {
                $ix = strpos($Wxis, "cgi-bin");
                $wxs = substr($Wxis, $ix);
                ?>
                <i class='fas fa-cogs'></i>&nbsp;<?php echo $msgstr['showisisversion'].": ";?>
                <span style='font-family:monospace;color:var(--abcd-blue);'><?php echo $wxs ;?></span>
                <?php
            }
            ?>
            </div>
        </div>
        <!-- What is the function of next script? Was present in abcd1.4. Required for navigation-->
        <script>
        top.maxmfn='<?php echo $LOC_MAXMFN;?>'
        top.mfn=0
        top.wks=''
        top.Formato=''
        top.RegistrosSeleccionados=''
        top.RegSel_pos=0
        html='<?php echo $HTML;?>'
        url='<?php echo $URL;?>'
        top.prefijo_indice='<?php echo $pi;?>'
        top.formato_indice='<?php echo urlencode($fe)?>'
        if (html=='' && top.HTML==''){	//No changes in the toolbar

        }else{
        top.HTML='<?php echo $HTML;?>'
        top.URL='<?php echo $URL;?>'
        top.lock_db=""
        top.menu.location.href="menu_main.php?base="+top.base+"&reload=N"
        }
    </script>
    </div>
<?php include("../common/footer.php"); ?>