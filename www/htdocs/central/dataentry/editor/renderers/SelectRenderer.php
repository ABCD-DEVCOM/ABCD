<?php
/**
 * Name: SelectRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for select fields in the ABCD data entry editor.
 * This class generates the HTML for rendering select inputs based on the field configuration and options provided. It supports both single and multiple selection modes, as well as integration with picklists for dynamic option management.
 */

class SelectRenderer {
    public static function renderColocarSelect($tag, $subc, $nombrec, $lista_opciones, $campo, $picklist): void {
        global $msgstr, $base;
        
        $opcion = explode(';', $lista_opciones);
        echo "<select name=" . $nombrec . " id=$nombrec>\n";
        echo "<option value=''></option>";
        foreach ($opcion as $lin) {
            if (trim($lin) != "") {
                $lt = explode('|', $lin);
                if (!isset($lt[0]) or $lt[0] == "") $lt[0] = $lt[1];
                if (!isset($lt[1]) or $lt[1] == "") $lt[1] = $lt[0];
                echo "<option value=\"";
                echo trim($lt[0]) . "\"";
                if (trim(strtoupper($campo)) == trim(strtoupper($lt[0])) && $campo != "")
                    echo " selected";
                echo ">";
                if (trim($lt[1]) == "")
                    echo $lt[0];
                else
                    echo trim($lt[1]);
                echo " \n";
            }
        }
        echo "</select>";
        if (isset($_SESSION["permiso"])) {
            if (
                isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
                isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])  or
                isset($_SESSION["permiso"][$base . "_CENTRAL_ACTPICKLIST"])
            ) {
                echo " <a class=\"bt-fdt\" href=\"javascript:AgregarPicklist('$picklist','$nombrec','$campo')\"><i class=\"fas fa-edit\" title='" . $msgstr["mod_picklist"] . "' ></i></a>";
            }
            echo " <a class=\"bt-fdt\" href=\"javascript:RefrescarPicklist('$picklist','$nombrec','$campo')\"><i class=\"fas fa-redo\" title='" . $msgstr["reload_picklist"] . "'></i></a>";
        }
    }

    public static function renderDibujarSelect($linea, $fondocelda, $valor, $tag, $ksc, $opciones, $rep, $subc): void {
        global $ver, $base, $arrHttp, $Path, $Tabla_sel, $db_path, $lang_db, $msgstr;
        
        $t = explode('|', $linea);
        $tipo = rtrim($t[7]);
        $rep = $t[4];
        $subc = rtrim($t[5]);
        $ksc = strlen($subc);
        $delimsc = rtrim($t[6]);
        echo "<td>";
        $TipoS = "";
        if ($rep == 1) $TipoS = " multiple";
        
        if (!$ver) {
            $file_options = "";
            $fp = array();
            $opc = array();
            if (strpos($opciones, '%path_database%') === false) {
                if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $opciones)) {
                    $file_options = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $opciones;
                    $fp = file($file_options);
                } else {
                    if (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $opciones)) {
                        $file_options = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $opciones;
                        $fp = file($file_options);
                    }
                }
            } else {
                $file_options = str_replace('%path_database%', $db_path, $opciones);
                if (file_exists($file_options))  //echo $file_options;
                    $fp = file($file_options);
            }
            $i = 0;
            $lensel = count($fp);
            if ($lensel > 10)
                $lensel = 0;
            else
                $lensel = $lensel + 1;

            $val = explode("\n", $valor);
            foreach ($fp as $linea) {
                if ($opciones == "%path_database%bases.dat" and ($arrHttp["base"] == "purchaseorder"
                    or $arrHttp["base"] == "suggestions")) {
                    $tbz = explode('|', $linea);
                    if (isset($tbz[2]) and trim($tbz[2]) == "Y") {
                    } else {
                        continue;
                    }
                }
                $linea = trim($linea);
                $pp = explode('|', $linea);
                if ($linea != "") {
                    if ($pp[0] != "") {
                        $key1 = $pp[0];
                    } else {
                        $key1 = $pp[1];
                    }
                    if (!isset($pp[1]) or $pp[1] == "") {
                        $key2 = $key1;
                    } else {
                        $key2 = $pp[1];
                    }
                    $opc[$key2] = $key1;
                }
            }
            $check = "";
            $selected = false;
            foreach ($opc as $key1 => $key2) {
                $opcVal = $key2;
                if (trim($subc) != "") {
                    $opcVal = "^" . substr($subc, 0, 1) . $key2 . "^" . substr($subc, 1, 1) . $key1;
                }
                foreach ($val as $check) {
                    if (trim($check) != "") {
                        if ($subc != "") {
                            $cc = explode('^', $check);
                            $check = substr($cc[1], 1);
                        }
                        if (trim($check) == trim($key2) and trim($key2) != "") $selected = true;
                    }
                }
            }
            $subc = rtrim($t[5]);
            echo "<select name=tag$tag $TipoS id=tag$tag";
            if ($lensel <> 0 and $TipoS == " multiple") {
                if ($selected == true) $lensel--;
                echo " size=$lensel";
            }
            echo ">\n";
            if ($selected == false) {
                echo "<option value=\"\" disabled selected>" . $msgstr["seloption"] . "</option>\n";            }
            $check = "";
            foreach ($opc as $key1 => $key2) {
                $opcVal = $key2;
                if (trim($subc) != "") {
                    $opcVal = "^" . substr($subc, 0, 1) . $key2 . "^" . substr($subc, 1, 1) . $key1;
                }
                echo "<option value=\"" . $opcVal . "\"";
                foreach ($val as $check) {
                    if (trim($check) != "") {
                        if ($subc != "") {
                            $cc = explode('^', $check);
                            $check = substr($cc[1], 1);
                        }
                        if (trim($check) == trim($key2) and trim($key2) != "") echo " selected";
                    }
                }
                echo ">" . $key1 . " &nbsp; &nbsp;\n";
            }
            echo "</select>";
            if ($file_options != "" or $file_options == "") {
                $opciones = urlencode($opciones);
                if (isset($_SESSION["permiso"])) {
                    echo "<td>";
                    if (isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or  isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])  or  isset($_SESSION["permiso"][$base . "_CENTRAL_ACTPICKLIST"])) {
                        echo " <a class=\"bt-fdt\" href=\"javascript:AgregarPicklist('$opciones','tag$tag','$check')\"><i class=\"fas fa-edit\" title='" . $msgstr["mod_picklist"] . "' ></i></a>";
                    }
                    echo " <a class=\"bt-fdt\" href=\"javascript:RefrescarPicklist('$opciones','tag$tag','$check')\"><i class=\"fas fa-redo\" title='" . $msgstr["reload_picklist"] . "' ></i></a>";
                }
            }
        } else {
            $filas = explode("\n", $valor);
            $ix = 0;
            foreach ($filas as $lin) {
                $ix = $ix + 1;
                $lin = trim($lin);
                echo "$lin";
                if ($ix < count($filas)) echo "<br>";
            }
        }
        echo "\n</td></tr>\n";
    }
}