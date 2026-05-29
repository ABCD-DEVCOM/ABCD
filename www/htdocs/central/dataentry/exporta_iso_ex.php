<?php
/* Modifications
2021-02-07 fho4abcd Add translation for download action.
2021-03-08 fho4abcd Replaced helper code fragment by included file
2021-03-08 fho4abcd Correct preview for txt files
2021-03-15 fho4abcd Operation in/out of a frame + improved back button + better error processing
2021-03-15 fho4abcd Add functionality from utilities/iso_export.php: folder from caller+busy indicator
2021-03-15 fho4abcd replace double quotes in values: search expressions will now work
2021-03-16 fho4abcd iso files not directly written to disc are now written without extra linefeeds
2021-03-24 fho4abcd Use function to delete a file, improve confirm
2021-03-25 fho4abcd Enable export by MX (includes option for marc leader data)
2021-03-26 fho4abcd Button mxread: now with file and only for iso files
2021-04-21 fho4abcd Show iso files with mx_show_iso + move action columns after file name column
2021-05-27 fho4abcd Show always footer. Check permissions work folder. Translations
2021-06-05 fho4abcd Export without marc without isotag. Export with marc with iso=marc + isotag.
20211216 fho4abcd Backbutton by included file
20220711 fho4abcd Use $actparfolder as location for .par files
20221216 fho4abcd No longer use 'marc=' in command. The outisotag1=3000 seems sufficient
*/

global $arrHttp;
set_time_limit(0);
session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];


include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
/*
** Old code might not send specific info.
** Set defaults for the return script and frame info
*/
$backtoscript = "../dataenty/administrar.php"; // The default return script
$inframe = 1;                      // The default runs in a frame
if (isset($arrHttp["backtoscript"])) $backtoscript = $arrHttp["backtoscript"];
if (isset($arrHttp["inframe"]))      $inframe = $arrHttp["inframe"];
if (!isset($arrHttp["tipo"]))         $arrHttp["tipo"] = "iso";
if (!isset($arrHttp["archivo"]))      $arrHttp["archivo"] = "dummy"; // can be used by preview
if (!isset($arrHttp["storein"]))      $arrHttp["storein"] = "/wrk";
// ensure that the file has extension iso or txt. Avoids overwriting db files (.mst/.tab/.pft/.fdt,...)
$fileinfo = pathinfo($arrHttp["archivo"]);
if (!isset($fileinfo['extension'])) { // no extension
    $arrHttp["archivo"] = $arrHttp["archivo"] . "." . $arrHttp["tipo"];
} else if ($fileinfo['extension'] == "") { // empty extension
    $arrHttp["archivo"] = $arrHttp["archivo"] . $arrHttp["tipo"];
} else if ($fileinfo['extension'] != $arrHttp["tipo"]) { //wrong extension
    $arrHttp["archivo"] = $fileinfo['filename'] . "." . $arrHttp["tipo"];
}
$filename = $arrHttp["archivo"];


//==================== Functions =============================
// se incluye la rutina que convierte los rótulos a tags isis
include("rotulos2tags.php");
include("../common/inc_file-delete.php");

/*--------------------------------------------------------------*/
function Confirmar()
{
    global $msgstr;
?>
    <br><br>
    <input class="bt bt-green" type=button name=continuar value="<?php echo $msgstr["continuar"] ?>" onclick=Confirmar()>
    &nbsp; &nbsp;<input class="bt bt-red" type=button name=cancelar value="<?php echo $msgstr["cancelar"] ?>" onclick=Regresar()>
    </div>
    </div>
    </body>

    </html>
    <?php
}

/*--------------------------------------------------------------*/
function GuardarArchivo($contenido, $fullpath)
{
    global $db_path, $Wxis, $xWxis, $wxisUrl, $arrHttp, $msgstr;
    // if wxis did not write to disc we do it here
    // this is the reverse "if" of "Write iso files always direct to disc" in Exportar
    if ($arrHttp["tipo"] != "iso" or isset($arrHttp["seleccionados"])) {
        $fp = fopen($fullpath, "w");
        if (!$fp) {
            echo "<div align=center><br><br><h4><font color=red>$fullpath</font>: " . $msgstr["revisarpermisos"] . "</h4></div>";
            die;
        }
        fwrite($fp, $contenido);
        fclose($fp);
    }
    // If nothing was found we have an error
    if (!file_exists($fullpath)) {
        echo "<div><font color=red>The export action did not result in any data</font></div>";
        echo "<div><font color=red>File <b>" . $fullpath . "</b> does not exist </font></div>";
        echo "<a href='javascript:Regresar()'>Try again</a>";
        die;
    } else {
    ?>
        <div align=center><br><br>
            <h4><?php echo $fullpath ?> &nbsp; <?php echo $msgstr["okactualizado"] ?> &nbsp;
                <a class="bt bt-green" href=javascript:Download()> <i class="fas fa-download"></i> <?php echo $msgstr["download"] ?></a>
            </h4>
        </div>
        <?php
        if ($arrHttp["tipo"] == "iso") {
        ?>
            <br><input class="bt bt-blue" type=button name=mxread value='<?php echo $msgstr["show"] . " " . $msgstr["cnv_iso"]; ?>' onclick=ActivarMx()>
        <?php
        }
    }
}


/*--------------------------------------------------------------*/
//Se lee la tabla con la estructura de conversión de rótulos a tags isis
function LeerTablaCnv()
{
    global $separador, $arrHttp, $db_path;
    $fp = file($db_path . $arrHttp["base"] . "/cnv/" . $arrHttp["cnv"]);
    $ix = -1;
    $Pft = "";
    foreach ($fp as $value) {
        if (substr($value, 0, 2) <> '//') {
            if ($ix == -1) {
                $separador = trim($value);
                $ix = 0;
            } else {
                $ix = $ix + 1;
                $t = explode('|', $value);
                $t[1] = trim($t[1]);
                $t[0] = trim($t[0]);
                //				$Pft.="if p(v".$t[1].") then '".$t[0]."' (v".$t[1]."/)/fi, \n";
                $rotulo[$t[1]][0] = $t[0];
                $rotulo[$t[1]][1] = $t[1];
                $rotulo[$t[1]][2] = $t[2];
                if (isset($t[3])) $rotulo[$t[1]][3] = $t[3];
                if (isset($t[4])) $rotulo[$t[1]][4] = $t[4];
                if (isset($t[5])) $rotulo[$t[1]][5] = $t[5];
                if (trim($t[5]) == "") {
                    if ($separador == "[TABS]") {
                        $Pft .= "if p(v" . $t[1] . ") then (v" . $t[1] . "+|; |) fi,'|'";
                    } else {
                        $Pft .= "if p(v" . $t[1] . ") then '" . $t[0] . "' (v" . $t[1] . "/)/fi,\n";
                    }
                } else {
                    if ($separador == "[TABS]") {
                        $Pft .= $t[5] . "'|'";
                    } else {
                        $Pft .= "'" . $t[0] . "' " . $t[5] . "/";
                    }
                }
            }
        }
    }
    if ($separador == "[TABS]") {
        $Pft .= "/";
    } else {
        $Pft .= "'$separador'/#";
    }
    return $Pft;
}

/*--------------------------------------------------------------
** - $Pft      : string with the text conversion specification
** - $fullpath : outputfile specification. Note that wxis does not always write this file
*/
function Exportar($Pft, $fullpath)
{
    global $Wxis, $xWxis, $db_path, $arrHttp, $msgstr, $separador, $wxisUrl, $actparfolder;
    $query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Formato=" . urlencode($Pft);
    if (isset($arrHttp["Mfn"]) and trim($arrHttp["Mfn"]) != "") {
        $query .= "&Opcion=rango&Mfn=" . $arrHttp["Mfn"] . "&to=" . $arrHttp["to"];
    } else {
        if (isset($arrHttp["Expresion"]) and trim($arrHttp["Expresion"]) != "") {
            $query .= "&Opcion=buscar&Expresion=";
            $query .= urlencode($arrHttp["Expresion"]);
        } else if (isset($arrHttp["seleccionados"])) {
            $query .= "&Opcion=seleccionados&Seleccionados=";
            $query .= "&Mfn=" . str_replace(",", "&Mfn=", $arrHttp["seleccionados"]);
        }
    }
    // Write iso files always direct to disc
    if ($arrHttp["tipo"] == "iso" and !isset($arrHttp["seleccionados"])) $query .= "&archivo=" . urlencode($fullpath);
    $contenido = "";
    $IsisScript = $xWxis . "export_txt.xis";
    include("../common/wxis_llamar.php");
    if ($err_wxis != "") die; // do no continue if there are severe errors
    if ($arrHttp["Accion"] == "P") { // Preview
        $salida = "";
        $nl = "<br>";
        foreach ($contenido as $value)  $salida .= $value . $nl;
        if (trim($value) == $separador) $salida .= $nl . $nl;
        echo "$salida";
        die;
    }
    if ($arrHttp["Accion"] == "S") { // this for data not directly written to disc
        if ($arrHttp["tipo"] == "iso") { // iso is only converted from array to string
            $salida = "";
            foreach ($contenido as $value) {
                $salida .= $value;
            }
            return $salida;
        } else { //txt requires some formatting (why?)
            $salida = "";
            $nl = PHP_EOL;
            foreach ($contenido as $value) {
                $salida .= $value . PHP_EOL;
            }
            if (trim($value) == $separador) $salida .= $nl . $nl;
            return $salida;
        }
    }
}
/*--------------------------------------------------------------
** - $fullpath : outputfile specification.
*/
function ExportarMX($fullpath)
{
    global $mx_path, $db_path, $arrHttp, $msgstr;
    $strINV = $mx_path . " db=" . $db_path . $arrHttp["base"] . "/data/" . $arrHttp["base"];
    $strINV .= " iso=";
    $strINV .= $fullpath;

    // Enter the range
    if (isset($arrHttp["Mfn"]) and trim($arrHttp["Mfn"]) != "") {
        $strINV .= " from=" . $arrHttp["Mfn"] . " to=" . $arrHttp["to"];
    }
    // Enter the marc special for the tag
    if (isset($arrHttp["usemarcformat"]) and $arrHttp["usemarcformat"] == "on") {
        $strINV .= " outisotag1=3000";
    }
    //
    if (isset($arrHttp["Expresion"]) and trim($arrHttp["Expresion"]) != "") {
        $expresion = str_replace('"', '', trim($arrHttp["Expresion"]));
        $strINV .= " bool=\"" . $expresion . "\"";
    }
    $strINV .= " -all now 2>&1";
    echo $msgstr["commandline"] . ": " . $strINV . "<br>";

    // Execute the command
    exec($strINV, $output, $status);
    // collect regular and error output
    $straux = "";
    for ($i = 0; $i < count($output); $i++) {
        $straux .= $output[$i] . "<br>";
    }
    // If nothing was found we have an error
    if ($status == 0) {
        clearstatcache();
        if (!file_exists($fullpath) or filesize($fullpath) == 0) {
            echo "<div><font color=red>The export action did not result in any data</font></div>";
            echo "<div><font color=red>File <b>" . $fullpath . "</b> does not exist or is empty</font></div>";
            echo "<a href='javascript:Regresar()'>Try again</a>";
            return 1;
        }
        ?>
        <div align=center><br><br>
            <h4><?php echo $fullpath ?> &nbsp; <?php echo $msgstr["okactualizado"] ?> &nbsp;
                <a class="bt bt-green" href=javascript:Download()> <i class="fas fa-download"></i><?php echo $msgstr["download"] ?></a>
            </h4>
        </div>
<?php
        if ($straux != "") echo "<hr>" . $straux;
    } else {
        echo ("<h3><font color='red'><br>" . $msgstr["processfailed"] . "</font></h3>");
        echo "<font color='red'>" . $straux . "</font>";
        return 1;
    }
    return 0;
}
//----------------------- End functions --------------------------------------------------
//----------------------------------------------------------------------------------------


//foreach ($arrHttp as $var=>$value) echo "**$var=$value<br>";
include("../common/header.php");
?>

<body>
    <script>
        function Download() {
            document.download.submit()
        }

        function Confirmar() {
            document.continuar.confirmar.value = "OK";
            document.getElementById('preloader').style.visibility = 'visible'
            document.continuar.submit()
        }

        function Regresar() {
            document.continuar.action = "exporta_iso.php";
            document.continuar.submit()
        }

        function ActivarMx() {
            document.getElementById('preloader').style.visibility = 'visible'
            document.continuar.action = '../utilities/mx_show_iso.php?copyname=<?php echo $arrHttp["archivo"] ?>'
            document.continuar.submit()
        }
    </script>
    <?php
    // Create a form for submission.
    echo "<form name=continuar action=exporta_iso_ex.php method=post>\n";
    foreach ($_REQUEST as $var => $value) {
        // some values (e.g. expresion) may contain quotes or other "non-standard" values
        $value = htmlspecialchars($value);
        echo "<input type=hidden name=$var value=\"$value\">\n";
    }
    if (!isset($arrHttp["confirmar"])) {
        $arrHttp["confirmar"] = "";
        echo "<input type=hidden name=confirmar value=\"\">\n";
    }
    echo "</form>\n";
    ?>
    <img src="../dataentry/img/preloader.gif" alt="Loading..." id="preloader"
        style="visibility:hidden;position:absolute;top:30%;left:45%;border:2px solid;" />
    <?php
    // If outside a frame: show institutional info
    if ($inframe != 1) include "../common/institutional_info.php";
    ?>
    <div class="sectionInfo">
        <div class="breadcrumb"><?php echo $msgstr["cnv_export"] . " " . $msgstr["cnv_" . $arrHttp["tipo"]] ?>
        </div>
        <div class="actions">
            <?php
            // Show 'back' button, except for a Preview action (in a separate window)
            if ($arrHttp["Accion"] != "P") {
                include "../common/inc_back.php";
            }
            ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>

    <?php
    $ayuda = "exportiso.html";
    include "../common/inc_div-helper.php"
    ?>

    <div class="middle form">
        <div class="formContent">
            <?php
            $marcleader = 0;
            $usemx = 0;
            $seleccionados = 0;
            echo "<table>";
            // Display the conversion file and convert the table in this file
            if (isset($arrHttp["cnv"]) and trim($arrHttp["cnv"]) != "") {
                echo "<tr><td>" . $msgstr["cnv_tab"] . ":<td><td>" . $arrHttp["cnv"] . "</td></tr>";
                $Pft = LeerTablaCnv();
            } else {
                $Pft = "";
            }
            // Display search expression
            if (isset($arrHttp["Expresion"]) and trim($arrHttp["Expresion"]) != "") {
                echo "<tr><td>" . $msgstr["buscar"] . ":<td><td>" . $arrHttp["Expresion"] . "</td></tr>";
            }
            // Display range
            if (isset($arrHttp["Mfn"]) and trim($arrHttp["Mfn"]) != "") {
                echo "<tr><td>" . $msgstr["r_mfnr"] . ":<td><td>" . $arrHttp["Mfn"] . " &rarr; " . $arrHttp["to"] . "</td></tr>";
            }
            // Display selection
            if (isset($arrHttp["seleccionados"]) and trim($arrHttp["seleccionados"]) != "") {
                echo "<tr><td>" . $msgstr["selected_records"] . ":<td><td>" . $arrHttp["seleccionados"] . "</td></tr>";
                $seleccionados++;
            }
            // Display Marc Leader selection
            if (isset($arrHttp["usemarcformat"]) and $arrHttp["usemarcformat"] == "on") {
                echo "<tr><td>" . $msgstr["ft_ldr"] . ":<td><td>" . $arrHttp["usemarcformat"] . "</td></tr>";
                $marcleader++;
            }
            // Compute full foldername + full filename and display full filename
            // Note that wrong characters (e.g. / and \) are already removed in the calling script
            $storein = $arrHttp["storein"];
            if ((strpos($storein, "/") === 0)) $storein = substr($storein, 1);
            $full_storein = $db_path . $storein;
            $fullpath = $full_storein . "/" . $filename;
            echo "<tr><td>" . $msgstr["r_fgent"] . ":<td><td>" . $fullpath . "</td></tr>";

            // Display the executable that will be used
            if (isset($arrHttp["usemx"]) and $arrHttp["usemx"] == "on") {
                $testbutton = '<a href="../utilities/mx_test.php?mx_path=' . $mx_path . '" target=test onclick=OpenWindow()>Test MX</a>';
                echo "<tr><td>" . $msgstr["procesar"] . ":<td><td>" . $mx_path . "&nbsp;&nbsp;&nbsp;" . $testbutton . "</td></tr>";
                $usemx++;
            } else {
                echo "<tr><td>" . $msgstr["procesar"] . ":<td><td>" . $wxisUrl . "</td></tr>";
            }
            echo "</table><br>";

            // Check the existence and permissions of the supplied folder
            clearstatcache();
            if (PHP_OS_FAMILY == "Linux") {
                if (is_executable($full_storein)) {
                    $isexec = true;
                } else {
                    $isexec = false;
                }
            } else {
                $isexec = true; // Executable only for Linux
            }
            if (!is_writable($full_storein) or !$isexec) {
                echo "<div style='color:red'>" . $full_storein . " <b>" . $msgstr["notreadable"] . "</b></div>";
                die;
            }

            // Check that seleccionados is not combined with MX
            if ($seleccionados > 0 and $usemx > 0) {
                echo "<div style='color:red'><b>" . $msgstr["selected_records"] . " </b>" . $msgstr["mx_notsupport"] . "</b></div>";
                echo "<a href='javascript:Regresar()'>" . $msgstr["entervalcomb"] . "</a>";
                die;
            }
            // Check that mx is selected if Marc Leader is set
            if ($marcleader > 0 and $usemx == 0) {
                echo "<div style='color:red'><b>" . $msgstr["ft_ldr"] . " </b>" . $msgstr["mx_required"] . "</b></div>";
                echo "<a href='javascript:Regresar()'>" . $msgstr["entervalcomb"] . "</a>";
                die;
            }

            // Display Continue and Cancel in first activation of this script
            if (!isset($arrHttp["confirmar"]) or (isset($arrHttp["confirmar"]) and $arrHttp["confirmar"] != "OK")) {
                Confirmar();
            } else {
                // This part will be excuted the second invocation. The busy symbol runs...
                // Remove the target file now: prevents downloading an old file if the process would fail
                $errors = DeleteFile($fullpath, 0);
                if ($errors > 0) {
                    echo "<div><font color=red><b>Export ABORTED</b></font></div>";
                    echo "<div><h2><font color=red><b>Content of existing " . $fullpath . "</b> is not modified !</font></h2></div>";
                    echo "<a href='javascript:Regresar()'> Cancel</a>";
                    die;
                }
                // Export the given range to file or to $data
                // The method depends on the checkbox of "usemx"
                if (isset($arrHttp["usemx"]) and $arrHttp["usemx"] == "on") {
                    $exportret = ExportarMX($fullpath);
                    if ($exportret == 0) echo "<br><input class=\"bt bt-blue\" type=button name=mxread value=\"" . $msgstr["mx_dbread"] . "\" onclick=ActivarMx()>";
                } else {
                    // Compute exported information.
                    // Show it in case of action Preview
                    $data = Exportar($Pft, $fullpath);
                    // Download the exported information in case of action Submit
                    if ($arrHttp["Accion"] == "S") {
                        GuardarArchivo($data, $fullpath);
                    }
                }
            ?>

        </div>
    </div>
    <form name=download action="../utilities/download.php">
        <input type=hidden name=archivo value="<?php echo $filename ?>">
    </form>
<?php
            }
            include("../common/footer.php");
?>
</body>

</html>