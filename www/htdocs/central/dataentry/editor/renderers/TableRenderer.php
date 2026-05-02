<?php

/**
 * Name: TableRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * Description: Renderer for table fields with modern HTML5 Drag and Drop.
 */

class TableRenderer
{
    private static $jsInjected = false;

    public static function render($filas, $tag, $fondocelda, $field_t): void
    {
        global $valortag, $fdt, $ver, $arrHttp, $Path, $db_path, $lang_db, $config_date_format, $msgstr;

        $TablaLeida = "";
        $cipar = $arrHttp["cipar"];
        $seleccion = "";
        $celda = "";
        $cols = -1;
        $columnas = array();
        $val_def = array();
        $size = array("");
        $t = explode('|', $field_t);
        $subc = $t[5];
        if ($ver == "")  unset($ver);
        if (substr($subc, 0, 1) == '-')  $subc = "_" . substr($subc, 1);
        $cant_cols = strlen($subc);
        if (isset($ver)) {
            $celda = " cellpadding=0 cellspacing=5 border=0 ";
            if (count($filas) == 0) return;
        }
        $seleccion = array();
        $ind = array();

        echo "<td colspan='2' class='table-fdt-four' style='padding: 15px 10px 20px 0;'>";

        echo "<div style='font-weight:bold; font-size:14px; color:#0056b3; margin-bottom:10px; border-bottom:1px solid #e0e0e0; padding-bottom:5px;'>";
        echo htmlspecialchars($t[2] ?? '', ENT_QUOTES, 'UTF-8');
        echo "</div>";

        echo "<div class='abcd-table-container' style='border: 1px solid #c0c0c0; border-left: 4px solid #17a2b8; background-color: #fafcfc; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 10px; overflow-x: auto;'>";
        echo "<table id=id_" . $t[1] . " " . $celda . " style='width:100%; border-collapse: collapse;'>";

        echo "<thead><tr>";

        // Coluna do "Pegador" (Drag handle) apenas em edição
        if (!isset($ver)) {
            echo "<th style='width: 20px; border-bottom: 2px solid #ddd; text-align: center;'><i class='fas fa-arrows-alt-v' style='color: #aaa;'></i></th>";
        }

        foreach ($filas as $lin) {
            if (trim($lin) != "") {
                $l = explode('|', $lin);
                $cols = $cols + 1;
                $val_def[$l[5]] = isset($l[15]) ? $l[15] : "";
                $len = isset($l[9]) ? $l[9] : "";
                $size[$cols] = $len;
                $Tabla = isset($l[10]) ? $l[10] : "";
                $Tab_name = isset($l[11]) ? $l[11] : "";

                if (trim($Tabla) != "") {
                    switch ($Tabla) {
                        case "P":
                            $Tab_name = str_replace("%path_database%", $db_path, $Tab_name);
                            $xx = explode('/', $Tab_name);
                            $fp = array();
                            if (count($xx) > 1 and file_exists($Tab_name)) {
                                $fp = file($Tab_name);
                            } else {
                                if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $Tab_name))
                                    $fp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $Tab_name);
                                else if (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $Tab_name))
                                    $fp = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $Tab_name);
                            }
                            foreach ($fp as $tab) {
                                if (trim($tab) != "") {
                                    if ($Tab_name == $db_path . "bases.dat" and ($arrHttp["base"] == "purchaseorder" or $arrHttp["base"] == "suggestions")) {
                                        $tbz = explode('|', $tab);
                                        if (!isset($tbz[2]) or trim($tbz[2]) != "Y") continue;
                                    }
                                    if (!isset($seleccion[$cols]) or $seleccion[$cols] == "") $seleccion[$cols] = $tab;
                                    else $seleccion[$cols] .= ";" . $tab;
                                }
                            }
                            break;
                    }
                }
                $ind[$cols] = $lin;
                if (count($filas) > 1) {
                    if (trim($l[2]) != "" and (isset($l[7]) && $l[7] != "I")) {
                        echo "<th style='font-size:12px; color:#444; text-align:left; padding: 0 8px 8px 8px; border-bottom: 2px solid #ddd;'>" . trim($l[2]) . "</th>";
                    }
                }
            }
        }

        if (!isset($ver)) {
            echo "<th style='width: 40px; border-bottom: 2px solid #ddd;'></th>";
        }
        echo "</tr></thead><tbody>";

        $filas_data = explode("\n", isset($valortag[$tag]) ? $valortag[$tag] : "");
        $tope = count($filas_data);
        if ($tope == 0) $tope = 1;
        $cant_fil = isset($t[8]) ? $t[8] : 2;
        $fixed_rows = $cant_fil;
        if ($cant_fil == 0) $cant_fil = 2;
        if ($fixed_rows > 1) {
            $tope = $fixed_rows;
            for ($ixr = count($filas_data); $ixr <= $fixed_rows; $ixr++) {
                $filas_data[$ixr] = "";
            }
        }

        for ($i = 0; $i < $tope; $i++) {
            $valorf = ($i >= count($filas_data)) ? "" : trim($filas_data[$i]);
            if (substr($subc, 0, 1) == '_') $valorf = "^_" . $valorf;

            for ($isc = 0; $isc < strlen($subc); $isc++) {
                $delim = substr($subc, $isc, 1);
                $pos = strpos(strtoupper($valorf), "^" . strtoupper($delim));
                $campo = "";
                if (is_integer($pos)) {
                    $campo = substr($valorf, $pos + 2, strlen($valorf));
                    $pos = strpos($campo, "^");
                    if (!is_integer($pos)) $pos = strlen($campo);
                    $campo = substr($campo, 0, $pos);
                    $columnas[$isc] = $campo;
                } else {
                    $columnas[$isc] = isset($ver) ? "&nbsp;" : (isset($val_def[$delim]) ? $val_def[$delim] : "");
                }
            }

            $bgColor = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
            // Adicionamos as classes de identificação para o Drag and Drop
            echo "<tr class='draggable-table-row' style='background:{$bgColor}; border-bottom:1px solid #eee;'>";

            // Célula do Pegador
            if (!isset($ver)) {
                echo "<td class='drag-handle' style='text-align: center; vertical-align: middle; cursor: grab; color: #ccc;' title='Arraste para reordenar'><i class='fas fa-grip-vertical'></i></td>";
            }

            for ($j = 0; $j <= $cols; $j++) {
                $n = isset($size[$j]) ? $size[$j] : 100;
                if ($n == 0) $n = 100;
                $campo = (count($columnas) > 0 && $i < count($filas_data)) ? (isset($columnas[$j]) ? $columnas[$j] : "") : (isset($ver) ? " &nbsp; " : "");

                $linea = isset($ind[$j]) ? $ind[$j] : "";
                $type_de = explode('|', $linea);

                $td7 = isset($type_de[7]) ? trim($type_de[7]) : "";
                $td8 = isset($type_de[8]) ? trim($type_de[8]) : 1;
                $td9 = isset($type_de[9]) ? trim($type_de[9]) : "";
                $td10 = isset($type_de[10]) ? trim($type_de[10]) : "";
                $td11 = isset($type_de[11]) ? trim($type_de[11]) : "";
                $td12 = isset($type_de[12]) ? trim($type_de[12]) : "";
                $td13 = isset($type_de[13]) ? trim($type_de[13]) : "";
                $td14 = isset($type_de[14]) ? trim($type_de[14]) : "";
                $td20 = isset($type_de[20]) ? trim($type_de[20]) : "";

                $Etq = $tag . "_" . $i . "_" . substr($subc, $j, 1);

                echo "<td class='td td.no-wrap' style='padding: 8px; vertical-align:middle;'>";

                $style_input = "width:100%; box-sizing:border-box; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-family: inherit; font-size: 13px;";
                $iso_tag = "";

                switch ($td7) {
                    case "I":
                        if (isset($ver)) echo $campo;
                        else echo "<input type=hidden name=tag$Etq id=tag$Etq value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">\n";
                        break;
                    case "S":
                        $nombrec = "tag" . $tag . "_" . $i . "_" . substr($subc, $j, 1);
                        if (isset($seleccion[$j]) && ($seleccion[$j] != "")) {
                            if (!isset($ver)) SelectRenderer::renderColocarSelect($tag, substr($subc, $j, 1), $nombrec, $seleccion[$j], $campo, $td11);
                            else echo $campo;
                        }
                        break;
                    case "D":
                        if (isset($ind[$j + 1])) {
                            $next_field = explode('|', $ind[$j + 1]);
                            $nf7 = isset($next_field[7]) ? trim($next_field[7]) : "";
                            if ($nf7 == "ISO") $iso_tag = $tag . "_" . $i . "_" . substr($subc, $j + 1, 1);
                        }
                    case "ISO":
                        if (isset($ver)) echo $campo;
                        else CalendarHelper::render($campo, $td7, $iso_tag, $Etq);
                        break;
                    case "C":
                        if (isset($ver)) echo $campo;
                        else CheckRenderer::render($linea, $fondocelda, $campo, $tag, $td11, $td9, $td7, $subc);
                        break;
                    case " ":
                    case "":
                    case "X":
                        $maxlength = 0;
                        if ($td9 <> "") {
                            $len_f = explode('/', $td9);
                            $n = $len_f[0];
                            if (isset($len_f[1]))  $maxlength = $len_f[1];
                        }
                        if ($td8 > 1) {
                            if (!isset($ver)) {
                                echo "<textarea name=tag" . $Etq . " rows=" . $td8 . " cols=" . $n . " style=\"$style_input\" ";
                                if ($maxlength > 0) echo " onKeyDown=\"textCounter(document.forma1.tag" . $Etq . ",document.forma1.rem$Etq,$maxlength)\" onKeyUp=\"textCounter(document.forma1.tag" . $Etq . ",document.forma1.rem$Etq,$maxlength)\"";
                                else if ($td20 == "U") echo " onKeyUp=\"CheckInventory($Etq)\"";
                                echo " id=tag" . $Etq . ">" . htmlspecialchars($campo, ENT_QUOTES) . "</textarea>";
                                if ($i == 0) echo "\n<script>max_l['$Etq']=$maxlength</script>\n";
                                if ($maxlength > 0) {
                                    $lengthmax = strlen($campo) == 0 ? $maxlength : $maxlength - strlen($campo);
                                    echo "<br><input tabindex='0' type=\"text\" name=\"rem$Etq\" size=\"3\" maxlength=\"$maxlength\" value=\"$lengthmax\" class=charCount onfocus=blur()>" . $msgstr["avalchars"] . "\n";
                                }
                            } else {
                                echo nl2br(htmlspecialchars($campo, ENT_QUOTES));
                            }
                        } else {
                            if (!isset($ver)) {
                                echo "<input tabindex='0' type=text name=tag" . $Etq . " id=tag" . $Etq . " size=$n style=\"$style_input\" ";
                                if ($maxlength != 0) echo " maxlength=$maxlength ";
                                echo " value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">";
                            } else {
                                echo nl2br(htmlspecialchars($campo, ENT_QUOTES));
                            }
                        }
                        break;
                    case "XF":
                        if (isset($ver)) echo $campo;
                        else echo "<input tabindex='0' type=text name=tag" . $Etq . " id=tag" . $Etq . " size=$n maxlength=$n style=\"$style_input\" value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">";
                        break;
                    case "U":
                        if (isset($ver)) echo $campo;
                        else {
                            $maxlength = 0;
                            if ($td9 <> "") {
                                $len_f = explode('/', $td9);
                                $n = $len_f[0];
                                if (isset($len_f[1])) $maxlength = $len_f[1];
                            }
                            UploadRenderer::renderInput($Etq, $campo, $td8, $n, $style_input, $maxlength);
                        }
                        break;
                    case "K":
                        if (isset($ver)) echo $campo;
                        else {
                            echo "<input tabindex='0' type=text name=tag" . $Etq . " id=tag" . $Etq . " size=$n style=\"$style_input\" value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">";
                            echo "<a class=\"bt-fdt\" href=javascript:EnviarArchivo('tag$Etq')><i class=\"fas fa-upload\" alt=\"Subir archivo al servidor\"></i></a>";
                            echo "<a href=javascript:EditarArchivo('tag$Etq')><i class=\"far fa-edit\" alt=\"Editar archivo existente\"></i></a>";
                        }
                        break;
                    case "AI":
                        if (isset($ver)) echo $campo;
                        else {
                            echo "<input type=hidden name=autoincrement value=$Etq>";
                            echo "<input type=hidden name=tag" . $Etq . " size=$n value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">" . htmlspecialchars($campo, ENT_QUOTES);
                        }
                        break;
                    case "N":
                        if (isset($ver)) echo $campo;
                        else echo "<input type='number' name=tag" . $Etq . " size=$n style=\"$style_input\" value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\">";
                        break;
                    case "RO":
                        if (isset($ver)) echo $campo;
                        else {
                            echo htmlspecialchars($campo, ENT_QUOTES);
                            echo "<input type=hidden name=tag" . $Etq . " id=tag" . $Etq . " size=$n value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\" onfocus=blur()>";
                        }
                        break;
                    case "DC":
                        if (isset($ver)) echo $campo;
                        else {
                            CalendarHelper::render($campo, $td7, $iso_tag, $Etq);
                            if ($campo == "") echo "<a class=\"bt-fdt\" href=javascript:AgregarFecha('tag$Etq')><i class=\"fas fa-plus\" title='" . $msgstr["add"] . "'></i></a>";
                        }
                        break;
                    case "OC":
                        if (isset($ver)) echo $campo;
                        else {
                            echo "<input tabindex='0' type=text name=tag" . $Etq . " size=10 style=\"$style_input\" value=\"" . htmlspecialchars($campo, ENT_QUOTES) . "\" onfocus=blur()>";
                            if ($campo == "") echo "<a class=\"bt-fdt\" href=javascript:AgregarOperador('tag$Etq')><i class=\"fas fa-plus\"  title='" . $msgstr["add"] . "'></i></a>";
                        }
                        break;
                }

                if ($td7 != "COMBO" and $td7 != "COMBORO") {
                    if ($td10 == "D" or $td12 != "") {
                        $sc_col = ($j == 0) ? $subc : substr($subc, $j, 1);
                        $separa = ";";
                        $base_alfa = $td11 == "" ? $arrHttp["base"] : $td11;
                        $Formato_alfa = $td13;
                        if (trim($td14) != "") $Formato_alfa .= ",`$$$`," . $td14;
                        $prefijo = $td12;
                        if (!isset($ver)) {
                            echo "&nbsp;<a class=\"bt-fdt\" href='javascript:AbrirIndiceAlfabetico(document.forma1.tag$Etq,\"$prefijo\",\"$sc_col\",\"$separa\",\"$base_alfa\",\"$base_alfa.par\",\"tag$Etq\",\"1\",\"\",\"$Formato_alfa\")'><i class=\"fas fa-search\"></i></a>";
                            if ($td7 != "I" and $td10 == "T") {
                                echo "&nbsp;<a class=\"bt-fdt\" href='javascript:AbrirTesauro(\"tag$Etq\",\"" . $td11 . "\",\"0\")'><i class=\"fas fa-cubes\"></i></a>";
                            }
                        }
                    }
                }

                echo "</td>\n";
            }

            if (!isset($ver)) {
                $Etq_row = $tag . "_" . $i . "_" . substr($subc, 0, 1);
                echo "<td style='vertical-align:middle; text-align:center; width: 40px; padding: 8px; border-left: 1px solid #eee;'>";
                echo "<a href=\"javascript:RowClean('$Etq_row','$subc')\" title='" . $msgstr["erase"] . "' style='color:#dc3545; display:inline-block; padding:5px;'><i class=\"fas fa-trash-alt\" style=\"font-size:14px;\"></i></a>";
                echo "</td>";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table></div>";

        if (isset($t[4]) && $t[4] == 1 and $fixed_rows == "" and !isset($ver)) {
            $vd = "";
            if (count($val_def) > 0) {
                foreach ($val_def as $key => $value) $vd .= $key . "|" . $value . "$$$";
            }
            echo "<div style='margin-top: 12px; display: flex; gap: 10px;'>";
            echo "<a class='bt bt-blue' style='padding: 6px 12px;' href=javascript:addRow('" . $t[1] . "','$subc','add','$vd')><i class='fas fa-plus'></i> " . $msgstr["add"] . "</a>";
            echo "<a class='bt bt-blue' style='padding: 6px 12px;' href=javascript:addRow('" . $t[1] . "','$subc','duplicate','$vd')><i class='fas fa-copy'></i> " . $msgstr["duplicate_last"] . "</a>";
            echo "</div>";
        }
        echo "</td></tr>";

        self::injectJS();
    }

    private static function injectJS()
    {
        if (self::$jsInjected) return;
        self::$jsInjected = true;
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            let draggedRow = null;

            function bindDragEvents(row, tag) {
                if (row.hasAttribute('data-drag-bound')) return;
                row.setAttribute('data-drag-bound', 'true');
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', function(e) {
                    draggedRow = row;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', row.innerHTML);
                    setTimeout(() => row.style.opacity = '0.5', 0);
                });

                row.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    const bounding = row.getBoundingClientRect();
                    const offset = bounding.y + (bounding.height / 2);
                    if (e.clientY - offset > 0) {
                        row.style.borderBottom = '2px solid #17a2b8';
                        row.style.borderTop = '';
                    } else {
                        row.style.borderTop = '2px solid #17a2b8';
                        row.style.borderBottom = '';
                    }
                });

                row.addEventListener('dragleave', function(e) {
                    row.style.borderTop = '';
                    row.style.borderBottom = '';
                });

                row.addEventListener('drop', function(e) {
                    e.preventDefault();
                    row.style.borderTop = '';
                    row.style.borderBottom = '';
                    if (row !== draggedRow && draggedRow) {
                        const tbody = row.parentNode;
                        const bounding = row.getBoundingClientRect();
                        const offset = bounding.y + (bounding.height / 2);
                        if (e.clientY - offset > 0) {
                            tbody.insertBefore(draggedRow, row.nextSibling);
                        } else {
                            tbody.insertBefore(draggedRow, row);
                        }
                        ABCD_reindexTable(tbody, tag);
                    }
                });

                row.addEventListener('dragend', function() {
                    if (draggedRow) draggedRow.style.opacity = '1';
                    draggedRow = null;
                    const tbody = row.parentNode;
                    tbody.querySelectorAll('tr').forEach(tr => {
                        tr.style.borderTop = '';
                        tr.style.borderBottom = '1px solid #eee';
                    });
                });
            }

            function ABCD_reindexTable(tbody, tag) {
                const rows = tbody.querySelectorAll('tr.draggable-table-row');
                rows.forEach((row, newIndex) => {
                    row.style.background = (newIndex % 2 === 0) ? '#ffffff' : '#f9f9f9';
                    
                    const inputs = row.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.name) {
                            const regex = new RegExp('tag' + tag + '_\\\\d+_');
                            input.name = input.name.replace(regex, 'tag' + tag + '_' + newIndex + '_');
                        }
                        if (input.id) {
                            const regex = new RegExp('tag' + tag + '_\\\\d+_');
                            input.id = input.id.replace(regex, 'tag' + tag + '_' + newIndex + '_');
                        }
                    });
                    
                    const links = row.querySelectorAll('a');
                    links.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href && href.includes('RowClean')) {
                            const regex = new RegExp(tag + '_\\\\d+_');
                            link.setAttribute('href', href.replace(regex, tag + '_' + newIndex + '_'));
                        }
                    });
                });
            }

            document.querySelectorAll('.abcd-table-container table').forEach(table => {
                const tag = table.id.replace('id_', '');
                const tbody = table.querySelector('tbody');
                
                if (tbody) {
                    tbody.querySelectorAll('tr.draggable-table-row').forEach(row => bindDragEvents(row, tag));
                    
                    const observer = new MutationObserver(mutations => {
                        mutations.forEach(mutation => {
                            mutation.addedNodes.forEach(node => {
                                if (node.tagName === 'TR' && node.classList.contains('draggable-table-row')) {
                                    bindDragEvents(node, tag);
                                    ABCD_reindexTable(tbody, tag);
                                }
                            });
                        });
                    });
                    observer.observe(tbody, { childList: true });
                }
            });
        });
        </script>";
    }
}
