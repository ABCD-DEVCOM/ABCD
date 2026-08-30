<?php
/*
* @file        facetas_cnf.php
* @author      Roger Craveiro Guilherme
* @date        2025-10-06
* @description Configuring OPAC facets
* CHANGE LOG:
* 2025-10-08 rogercgui Correction in the validation of empty lines
* 2025-11-09 rogercgui Replaces file() with file_get_contents_utf8()
* 2026-08-29 Refactored to remove hidden rows and use DOM-based dynamic table management
*/
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/facets";
include "../../common/inc_div-helper.php";

if ($_REQUEST["base"] == "META") {
?>
    <script>var idPage = "metasearch";</script>
<?php } else { ?>
    <script>var idPage = "db_configuration";</script>
<?php } ?>

<div class="middle form row m-0">
    <div class="formContent col-2 m-2 p-0">
        <?php include("conf_opac_menu.php"); ?>
    </div>
    <div class="formContent col-9 m-2">
        <?php include("menu_dbbar.php");  ?>
        <h3><?php echo $msgstr["facetas"]; ?></h3>

        <?php
        $update_message = ""; 
        $linea = array();

        if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
            $lang = $_REQUEST["lang"];
            $archivo = $db_path . $_REQUEST['base'] . "/opac/$lang/" . $_REQUEST["file"];
            $fout = fopen($archivo, "w");
            foreach ($_REQUEST as $var => $value) {
                $value = trim($value);
                $var = trim($var);
                if (substr($var, 0, 9) == "conf_base") {
                    $x = explode('_', $var);
                    $linea[$x[2]][$x[3]] = $value;
                }
            }
            foreach ($linea as $value) {
                if (isset($value[0]) && trim($value[0]) != "") {
                    ksort($value);
                    $salida = implode('|', $value);
                    fwrite($fout, $salida . "\n");
                }
            }
            fclose($fout);
            $update_message = "<div class=\"alert success\"><strong>" . $archivo . " " . $msgstr["updated"] . "</strong></div>";
        }

        if (!empty($update_message)) echo $update_message;

        if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "copiarde") {
            $archivo = $db_path . $base . "/opac/" . $_REQUEST["lang_copiar"] . "/" . $_REQUEST["archivo"];
            copy($archivo, $db_path . $base . "/opac/" . $_REQUEST["lang"] . "/" . $_REQUEST["archivo"]);
            echo "<p><font color=red>" . $db_path . $base . "/opac/$lang/" . $_REQUEST["archivo"] . " " . $msgstr["copiado"] . "</font></p>";
        }
        ?>

        <form name="indices" method="post">
            <input type="hidden" name="db_path" value="<?php echo $db_path; ?>">
            <?php
            if (!isset($_REQUEST["Opcion"]) or $_REQUEST["Opcion"] != "Guardar") {
                $archivo = $db_path . "opac_conf/$lang/bases.dat";
                $fp = file_get_contents_utf8($archivo);
                $base = $_REQUEST["base"];
                
                if ($base == "META") {
                    Entrada("MetaSearch", $msgstr["metasearch"], $lang, "facetas.dat", $base);
                } else {
                    if ($fp) {
                        foreach ($fp as $value) {
                            if (trim($value) != "") {
                                $x = explode('|', $value);
                                if ($x[0] != $_REQUEST["base"]) continue;
                                echo "<p>";
                                Entrada(trim($x[0]), trim($x[1]), $lang, trim($x[0]) . "_facetas.dat", $base);
                                break;
                            }
                        }
                    }
                }
            }
            ?>
        </form>

        <?php
        function CopiarDe($iD, $name, $lang, $file) {
            global $db_path, $msgstr;
            echo "<br>" . $msgstr["copiar_de"] . " ";
            echo "<select name=lang_copy onchange='Copiarde(\"$iD\",\"$name\",\"$lang\",\"$file\")' id=lang_copy>";
            echo "<option></option>\n";
            $fp = file_get_contents_utf8($db_path . "opac_conf/$lang/lang.tab");
            if ($fp) {
                foreach ($fp as $value) {
                    if (trim($value) != "") {
                        $a = explode("=", $value);
                        echo "<option value=" . $a[0] . ">" . trim($a[1]) . "</option>";
                    }
                }
            }
            echo "</select><br>";
        }

        function Entrada($iD, $name, $lang, $file, $base) {
            global $msgstr, $db_path;
            
            echo "<form name=\"{$iD}Frm\" method=\"post\" onsubmit=\"reindexTable(this)\">\n";
            echo "<input type=\"hidden\" name=\"Opcion\" value=\"Guardar\">\n";
            echo "<input type=\"hidden\" name=\"base\" value=\"$base\">\n";
            echo "<input type=\"hidden\" name=\"file\" value=\"$file\">\n";
            echo "<input type=\"hidden\" name=\"lang\" value=\"$lang\">\n";
            if (isset($_REQUEST["conf_level"])) {
                echo "<input type=\"hidden\" name=\"conf_level\" value=\"" . $_REQUEST["conf_level"] . "\">\n";
            }
            ?>
            
            <strong><?php echo $name . " (" . $base . ")"; ?> </strong>
            <div id="<?php echo $iD; ?>">
                <div style="display: flex;">
                    <div style="flex: 0 0 65%;">
                        <?php
                        $cuenta = 0;
                        $fp_campos = [];
                        $fst_file_path = "";
                        
                        if ($base != "" and $base != "META") {
                            $fst_file_path = $db_path . $base . "/data/$base.fst";
                            $fp_campos = file_get_contents_utf8($fst_file_path);
                            $cuenta = $fp_campos ? count($fp_campos) : 0;
                        }

                        if ($base != "" and $base != "META") {
                            $file_av = $db_path . $base . "/opac/$lang/$file";
                        } else {
                            $file_av = $db_path . "/opac_conf/$lang/$file";
                        }

                        if (!file_exists($file_av)) {
                            $fp = array();
                        } else {
                            $fp = file_get_contents_utf8($file_av);
                        }
                        ?>
                        <code><?php echo $file_av ?></code>
                        <hr>
                        <table id="facets_table_<?php echo $iD; ?>" class="table striped">
                            <thead>
                                <tr>
                                    <th class="col-3"><?php echo $msgstr["nombre"]; ?></th>
                                    <th class="col-4"><?php echo $msgstr["expr_b"]; ?></th>
                                    <th class="col-3"><?php echo $msgstr["ix_pref"]; ?></th>
                                    <th class="col-1"><?php echo $msgstr["cfg_sortby"]; ?></th>
                                    <th style="text-align: center; white-space: nowrap;"><?php echo $msgstr["actions"] ?? "Ações"; ?></th>
                                </tr>
                            </thead>
                            <tbody id="tbody_facets_<?php echo $iD; ?>">
                                <?php
                                $row = 0;
                                if ($fp) {
                                    foreach ($fp as $value) {
                                        $value = trim($value);
                                        if ($value != "") {
                                            $row++;
                                            $v = explode('|', $value);
                                            $v[0] = $v[0] ?? "";
                                            $v[1] = $v[1] ?? "";
                                            $v[2] = $v[2] ?? "";
                                            $v[3] = $v[3] ?? "Q"; // Default: Quantidade
                                            
                                            echo "<tr>";
                                            echo "<td><input type=text name=conf_base_" . $row . "_0 value=\"" . htmlspecialchars($v[0]) . "\" class='col'></td>";
                                            echo "<td><input type=text name=conf_base_" . $row . "_1 value=\"" . htmlspecialchars($v[1]) . "\" class='col'></td>";
                                            echo "<td><input type=text name=conf_base_" . $row . "_2 value=\"" . htmlspecialchars($v[2]) . "\" class='col'></td>";
                                            echo "<td>";
                                            echo "<select name=conf_base_" . $row . "_3>\n";
                                            echo "<option value=\"Q\"" . (strtoupper($v[3]) == 'Q' ? ' selected' : '') . ">" . $msgstr["cfg_quantity"] . " (Q)</option>\n";
                                            echo "<option value=\"A\"" . (strtoupper($v[3]) == 'A' ? ' selected' : '') . ">" . $msgstr["cfg_alphabetically"] . " (A)</option>\n";
                                            echo "</select>";
                                            echo "</td>\n";
                                            echo "<td style=\"text-align: center; white-space: nowrap;\">";
                                            echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, -1)'><i class='fas fa-arrow-up'></i></button> ";
                                            echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, 1)'><i class='fas fa-arrow-down'></i></button> ";
                                            echo "<button type='button' class='bt bt-blue' onclick='duplicateRow(this)'><i class='far fa-copy'></i></button> ";
                                            echo "<button type='button' class='bt bt-red' onclick='deleteRow(this)'><i class='fas fa-trash-alt'></i></button>";
                                            echo "</td>";
                                            echo "</tr>\n";
                                        }
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <div style="margin-top: 10px;">
                            <button type="button" class="bt-gray" onclick="addRowFacets('tbody_facets_<?php echo $iD; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"]; ?></button>
                        </div>
                        <p><button type="submit" class="bt-green m-2"><i class="fas fa-save"></i> <?php echo $msgstr["save"]; ?></button></p>
                    </div>
                    </form>

                    <div style="flex: 1; padding-left: 20px;">
                        <button type="button" class="accordion">
                            <i class="fas fa-question-circle"></i> <?php echo $msgstr["view_fst_help"]; ?>
                        </button>
                        <div class="panel p-0">
                            <div class="reference-box" style="max-height: 450px;">
                                <?php if ($cuenta > 0 && $fp_campos) { ?>
                                    <table class="table striped">
                                        <thead>
                                            <tr>
                                                <th colspan="3"><strong><?php echo $base . "/data/" . $base . ".fst"; ?></strong><br><br></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fp_campos as $value) {
                                                if (trim($value) != "") {
                                                    $v = explode(' ', $value, 3);
                                                    echo "<tr><td>" . (isset($v[0]) ? $v[0] : '') . "</td><td>" . (isset($v[1]) ? $v[1] : '') . "</td><td>" . (isset($v[2]) ? $v[2] : '') . "</td></tr>\n";
                                                }
                                            } ?>
                                        </tbody>
                                    </table>
                                <?php } else if ($base != "META") {
                                    echo "<strong><font color=red>" . $msgstr["missing"] . " $fst_file_path</font></strong>";
                                } else {
                                    echo $msgstr["fst_not_applicable"];
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script>
function moveRow(btn, direction) {
    var row = btn.closest("tr");
    var tbody = row.parentNode;
    if (direction === -1 && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
    } else if (direction === 1 && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
    }
}

function deleteRow(btn) {
    if (confirm("<?php echo $msgstr['are_you_sure'] ?? 'Tem certeza?'; ?>")) {
        btn.closest("tr").remove();
    }
}

function duplicateRow(btn) {
    var row = btn.closest("tr");
    var clone = row.cloneNode(true);
    var selects = row.querySelectorAll("select");
    var cloneSelects = clone.querySelectorAll("select");
    for (var i = 0; i < selects.length; i++) {
        cloneSelects[i].value = selects[i].value;
    }
    row.parentNode.insertBefore(clone, row.nextSibling);
}

function addRowFacets(tbodyId) {
    var tbody = document.getElementById(tbodyId);
    var tr = document.createElement("tr");
    
    tr.innerHTML = `
        <td><input type="text" name="conf_base_0_0" value="" class="col"></td>
        <td><input type="text" name="conf_base_0_1" value="" class="col"></td>
        <td><input type="text" name="conf_base_0_2" value="" class="col"></td>
        <td>
            <select name="conf_base_0_3">
                <option value="Q"><?php echo $msgstr["cfg_quantity"] ?? "Quantity"; ?> (Q)</option>
                <option value="A"><?php echo $msgstr["cfg_alphabetically"] ?? "Alphabetically"; ?> (A)</option>
            </select>
        </td>
        <td style="text-align: center; white-space: nowrap;">
            <button type="button" class="bt bt-gray" onclick="moveRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="bt bt-gray" onclick="moveRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="bt bt-blue" onclick="duplicateRow(this)"><i class="far fa-copy"></i></button>
            <button type="button" class="bt bt-red" onclick="deleteRow(this)"><i class="fas fa-trash-alt"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
}

function reindexTable(form) {
    var rows = form.querySelectorAll("tbody tr");
    rows.forEach(function(row, index) {
        var inputs = row.querySelectorAll("input[type='text'], select, input[type='hidden']");
        inputs.forEach(function(input) {
            var name = input.getAttribute("name");
            if (name && name.startsWith("conf_base_")) {
                var parts = name.split("_");
                // parts[0]="conf", parts[1]="base", parts[2]=row, parts[3]=col
                parts[2] = index + 1;
                input.setAttribute("name", parts.join("_"));
            }
        });
    });
}

function Copiarde(db, db_name, lang, file) {
    // Required if you keep the "copiar de" functional 
    var ln = document.getElementById('lang_copy');
    var form = document.createElement("form");
    form.method = "post";
    
    var f1 = document.createElement("input"); f1.type="hidden"; f1.name="Opcion"; f1.value="copiarde";
    var f2 = document.createElement("input"); f2.type="hidden"; f2.name="db"; f2.value=db;
    var f3 = document.createElement("input"); f3.type="hidden"; f3.name="archivo"; f3.value=file;
    var f4 = document.createElement("input"); f4.type="hidden"; f4.name="lang_copiar"; f4.value=ln.options[ln.selectedIndex].value;
    var f5 = document.createElement("input"); f5.type="hidden"; f5.name="lang"; f5.value="<?php echo $_REQUEST['lang'] ?? 'en'; ?>";
    
    form.appendChild(f1); form.appendChild(f2); form.appendChild(f3); form.appendChild(f4); form.appendChild(f5);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include("../../common/footer.php"); ?>