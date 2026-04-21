<?php

/**
 * Name: RepeatableRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for repeatable fields in the ABCD data entry editor.
 * This class generates the HTML for rendering repeatable fields, which can have multiple occurrences, based on the field configuration and options provided. It supports both fixed and dynamic numbers of occurrences.
 */

class RepeatableRenderer {
    public static function render($tag, $fondocelda, $field_t): void {
        global $valortag, $fdt, $ver, $arrHttp, $Path, $db_path, $lang_db, $config_date_format, $msgstr;
        
        $filas = explode("\n", $valortag[$tag]);
        $t = explode('|', $field_t);
        $cant_fil = $t[8];
        if ($ver = "") unset($ver);
        $xtb = explode('/', $t[8]);
        if (count($xtb) > 1) {
            $fixed_rows = $xtb[0];
            $size = $xtb[1];
        } else {
            if ($t[7] == 'TB') {
                $fixed_rows = $xtb[0];
                $size = 1;
            } else {
                $fixed_rows = "";
                $size = $xtb[0];
            }
        }
        $tope = count($filas);
        if ($tope == 0) $tope = 1;
        if ($fixed_rows > 1) {
            $tope = $fixed_rows;
            for ($ixr = count($filas); $ixr <= $fixed_rows; $ixr++) {
                $filas[] = "";
            }
        }
        echo '<td class="td td.no-wrap">' . $t[2] . '<table id=id_$tag >';
        $i = -1;
        $n = 100;
        for ($i = 0; $i < $tope; $i++) {
            if ($i > count($filas)) {
                $campo = "";
            } else {
                $campo = $filas[$i];
            }
            if (!$ver) {
                $Etq = $tag . "_" . $i;
                $maxlength = 0;
                if ($t[9] <> "") {
                    $len_f = explode('/', $t[9]);
                    $n = $len_f[0];
                    if (isset($len_f[1])) $maxlength = $len_f[1];
                }
                echo "<tr><td width=20>";
                switch ($t[7]) {
                    case "ISO":
                        $date_format = ConvertDateSpec($config_date_format); // format of the input field
                        echo "<input tabindex='0' type=text size=8 name=tag$Etq id=tag$Etq value='";
                        if (trim($campo) != "") echo $campo;
                        echo "'";
                        if ($type_de[7] = "ISO") echo " onChange='Javascript:DateToIso(this.value,document.forma1.tag$Etq)'";
                        echo ">
                <a class=\"bt-fdt\" href='javascript:CalendarSetup(\"tag$Etq\",\"$date_format\",\"f_tag$Etq\", \"\",true )'>
                 <i class=\"far fa-calendar-alt\" id=\"f_tag$Etq\" style=\"cursor: pointer;\" title=\"Date selector\"
                      /></i></a>";
                        break;
                    default:
                        if ($size > 1) {
                            if ($t[9] == 0 || $t[9] == "") $t[9] = 100;
                            echo "<textarea name=tag" . $Etq . " rows=$size cols=" . $t[9] . " class=td";
                            if ($maxlength != 0) {
                                echo " onKeyDown=\"textCounter(document.forma1.tag" . $Etq . ",document.forma1.rem$Etq,$maxlength)\"
                          onKeyUp=\"textCounter(document.forma1.tag" . $Etq . ",document.forma1.rem$Etq,$maxlength)\"";
                            }
                            if (isset($t[20]) and $t[20] == "U")
                                echo " onKeyUp=\"CheckInventory($Etq)\"";
                            echo "> " . $campo . "</textarea>";
                            if ($maxlength != 0) {
                                echo "\n<script>max_l['$Etq']=$maxlength</script>\n";
                                $lengthmax = strlen($campo);
                                if ($lengthmax == 0) {
                                    $lengthmax = $maxlength;
                                } else {
                                    $lengthmax = $maxlength - $lengthmax;
                                }
                                echo "<br><span align=right><input type=\"text\" name=\"rem$Etq\" size=\"3\" maxlength=\"$maxlength\" value=\"$lengthmax\" class=charCount onfocus=blur()>" . $msgstr["avalchars"] . "</span>\n";
                            }
                        } else {
                            if ($maxlength != 0)
                                echo "<a style=\"text-decoration:none\" onMouseover=\"ddrivetip(document.forma1.tag" . $Etq . ".value,'linen',200 )\"; onMouseout=\"hideddrivetip()\"; onclick=\"hideddrivetip()\">";
                            echo "<input type=text name=tag" . $Etq . " size=$n";
                            if ($maxlength != 0) echo " maxlength=$maxlength";
                            echo " class=td value=\"$campo\">";
                            if ($maxlength != 0) echo "</a>";
                        }
                        if ($t[10] == "D" or $t[10] == "T") {
                            $sc_col = "";
                            $separa = ";";
                            $base_alfa = $t[11];
                            if ($base_alfa == "") $base_alfa = $arrHttp["base"];
                            $Formato_alfa = $t[13];
                            $prefijo = $t[12];
                            if ($t[10] == "T")
                                echo "<a class=\"bt-fdt\" href='javascript:AbrirTesauro(\"tag$Etq\",\"" . $type_de[11] . "\",\"0\")'><i class=\"fas fa-cubes\"></i></a>";
                            echo "<a class=\"bt-fdt\" href='javascript:AbrirIndiceAlfabetico(document.forma1.tag$Etq,\"$prefijo\",\"$sc_col\",\"$separa\",\"$base_alfa\",\"$base_alfa.par\",\"tag$Etq\",\"1\",\"\",\"$Formato_alfa\")'><i class=\"fas fa-search\"></i></a>";
                        }
                }
                echo "</td>";
            } else {
                echo "<tr><td width=20>" . $campo . "</td></tr>";
            }
        }
        echo "</table>";
        if ($fixed_rows == "" and !$ver) {
            echo "<a href=javascript:addRow('" . $t[1] . "','','','')>" . $msgstr["add"] . "</a><br><br>";
        }
    }
}