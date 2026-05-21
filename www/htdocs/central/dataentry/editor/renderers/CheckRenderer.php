<?php
/**
 * Name: CheckRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for checkbox and radio button fields in the ABCD data entry editor.
 * This class generates the HTML for rendering checkbox and radio button inputs based on the field configuration and options provided.
 */

class CheckRenderer {
    public static function render($filas, $fondocelda, $valor, $tag, $opciones, $tope, $tipo, $subc): void {
        global $ver, $base, $arrHttp, $Path, $db_path, $lang_db, $msgstr;
        
        echo "<td class='table-fdt-four input-fdt'>";
        if (!$ver) {
            echo "<table>\n";
            if ($opciones == 1) {
                $fp = array(1);
            } else {
                $fp = array();
                if (strpos($opciones, '%path_database%') === false) {
                    if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $opciones))
                        $fp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $opciones);
                    elseif (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $opciones))
                        $fp = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $opciones);
                } else {
                    if (file_exists(str_replace('%path_database%', $db_path, $opciones)))
                        $fp = file(str_replace('%path_database%', $db_path, $opciones));
                }
            }
            $countcols = 0;
            if ($tope == "") $tope = 1;
            $val = explode("\n", $valor);
            if (count($fp) == 0) {
                echo "<tr><td class=class='td td.no-wrap'><font color=red>" . $msgstr["missing"] . " $opciones</font></td>";
            }
            foreach ($fp as $linea) {
                $linea = trim($linea);
                if ($linea != "") {
                    if ($countcols == 0) {
                        echo "<tr>";
                    }
                    $countcols++;
                    echo "<td class='td td.no-wrap'>";
                    $opc = explode('|', $linea);
                    if (!isset($opc[1])) $opc[1] = "";
                    if ($opc[0] == "") $opc[0] = $opc[1];
                    if ($opc[1] == "") $opc[1] = $opc[0];
                    if (!isset($opc[1])) $opc[1] = $opc[0];
                    $opcVal = $opc[0];
                    if (trim($subc) != "") {
                        $opcVal = "^" . substr($subc, 0, 1) . $opc[0] . "^" . substr($subc, 1, 1) . $opc[1];
                    }
                    if (strpos($opcVal, "<") === true) {
                        $opcVal = str_replace('"', '´', $opcVal);
                    }
                    if ($tipo == "R") echo "<input tabindex='0' type=radio name=tag$tag value='" . $opcVal . "'";
                    if ($tipo == "C" or $tipo == "RP") echo "<input tabindex='0' type=checkbox name=tag$tag id=tag$tag value=\"" . $opcVal . "\"";
                    foreach ($val as $check) {
                        if ($subc != "") {
                            $cc = explode('^', $check);
                            if (isset($cc[1])) $check = substr($cc[1], 1);
                        }
                        if (trim($check) == trim($opc[0])) echo " checked";
                    }
                    if ($opciones == 1)
                        echo  ">";
                    else
                        echo ">&nbsp;" . $opc[1] . " &nbsp; &nbsp;\n";
                    echo "</td>";
                    if ($countcols == $tope) {
                        $countcols = 0;
                        echo "</tr>";
                    }
                }
            }
            if ($countcols != 0 and $countcols < $tope) {
                for ($i = $countcols; $i < $tope; $i++) {
                    echo "<td></td>";
                }
                echo "</tr>";
            }
            echo "</table>";
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
    }
}