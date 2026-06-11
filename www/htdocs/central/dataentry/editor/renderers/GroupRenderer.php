<?php

/**
 * Name: GroupRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * Description: Renderer for group fields in the ABCD data entry editor.
 * Updated to natively support 'IND' tags for correct MARC21 formatting.
 * 
 * Change Log:
 * - 2026-04-19: Initial creation of the GroupRenderer class with methods for rendering group fields and handling subfield definitions.
 * - 2026-05-01: Added support for rendering subfields inline within the group field, based on configuration settings.
 * - 2026-06-10: Refactored the render method to separate traditional and inline rendering logic, and added a method to extract subfield definitions from the FDT or local variables.
 * - 2026-06-11: Implemented JavaScript functions to handle dynamic addition and removal of subfield rows in inline mode, and to update the hidden input field with the correct MARC21 string format whenever changes are made.
 */
class GroupRenderer
{

    private static $jsInjected = false;

    private static function sanitize($text)
    {
        $search = ['&', '"', "'", '<', '>'];
        $replace = ['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'];
        return str_replace($search, $replace, $text);
    }

    private static function loadPicklist($file)
    {
        global $db_path, $arrHttp, $lang_db;
        $options = [];
        if (empty($file)) return $options;
        $lang = isset($_SESSION["lang"]) ? $_SESSION["lang"] : $lang_db;
        $path = $db_path . (isset($arrHttp["base"]) ? $arrHttp["base"] : "") . "/def/" . $lang . "/" . $file;

        if (!file_exists($path)) {
            $path = $db_path . (isset($arrHttp["base"]) ? $arrHttp["base"] : "") . "/def/" . $lang_db . "/" . $file;
        }
        if (file_exists($path)) {
            $fp = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($fp) {
                foreach ($fp as $line) {
                    $l = explode('|', trim($line));
                    $val = $l[0];
                    $label = (isset($l[1]) && trim($l[1]) !== '') ? $l[1] : $val;
                    $options[$val] = $label;
                }
            }
        }
        return $options;
    }

    public static function render($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $vars, &$ivars, $nsc): void
    {
        $useInline = ConfigHelper::isInlineSubfieldsEnabled();
        $subfieldsDefLines = self::extractSubfields($tag, $vars, $ivars, $nsc);

        if ($useInline && empty($ver)) {
            self::renderInline($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines);
        } else {
            self::renderTraditional($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines);
        }
    }

    private static function extractSubfields($tag, $vars, &$ivars, $nsc): array
    {
        global $base_fdt;
        $filas = [];

        $sfld = isset($vars[$ivars + 1]) ? rtrim($vars[$ivars + 1]) : "";
        $sf = explode('|', $sfld);

        // If the next line is NOT S or IND, look up the structure in the global FDT definition
        if ($sf[0] != "S" && $sf[0] != "IND") {
            if (!empty($base_fdt) && is_array($base_fdt)) {
                for ($nx = 0; $nx < count($base_fdt); $nx++) {
                    $ll = $base_fdt[$nx];
                    $ll_a = explode('|', $ll);
                    if (isset($ll_a[1]) && $ll_a[1] == $tag) {
                        $nx++;
                        // Read indefinitely until the S or IND values in the FDT are exhausted
                        while (isset($base_fdt[$nx])) {
                            $check = explode('|', $base_fdt[$nx]);
                            if ($check[0] == "S" || $check[0] == "IND") {
                                $filas[] = rtrim($base_fdt[$nx]);
                                $nx++;
                            } else {
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        } else {
            // Read indefinitely the local lines of the form (Ignores $nsc!)
            while (isset($vars[$ivars + 1])) {
                $check_s = explode('|', $vars[$ivars + 1]);

                // While it's S or IND, put it inside the blue box
                if ($check_s[0] == "S" || $check_s[0] == "IND") {
                    $ivars++; 
                    $filas[] = rtrim($vars[$ivars]);
                } else {
                    break; // If you hit another field, the reading is interrupted
                }
            }
        }

        return $filas;
    }

    private static function renderTraditional($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines): void
    {
        TextRenderer::render($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda);
        echo "\n<tr style='display:none'><td colspan=4>";
        echo "<input type=hidden name=eti$tag value=\"" . self::sanitize($linea01) . "\">\n";
        foreach ($subfieldsDefLines as $lin) {
            echo "<input type=hidden name=eti$tag value=\"" . self::sanitize($lin) . "\">\n";
        }
        echo "\n</td></tr>\n";
    }

    private static function renderInline($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines): void
    {
        global $valortag, $arrHttp;

        $subfield_defs = [];
        foreach ($subfieldsDefLines as $line) {
            $parts = explode('|', $line);
            $tipo_linha = trim(isset($parts[0]) ? $parts[0] : '');
            $code = trim(isset($parts[5]) ? $parts[5] : '');

            if ($code !== '' && ($tipo_linha == 'S' || $tipo_linha == 'IND')) {
                $len_str = trim(isset($parts[9]) ? $parts[9] : '50');
                $size = 50;
                $maxlength = "";
                if (strpos($len_str, '/') !== false) {
                    $lparts = explode('/', $len_str);
                    $size = (int)$lparts[0];
                    $maxlength = "maxlength='" . (int)$lparts[1] . "'";
                } else {
                    $size = (int)$len_str ?: 50;
                }

                $indexType = trim(isset($parts[10]) ? $parts[10] : '');
                $picklistFile = trim(isset($parts[11]) ? $parts[11] : '');
                $options = [];

                if ($picklistFile !== '' && $indexType !== 'D' && $indexType !== 'T') {
                    $options = self::loadPicklist($picklistFile);
                }

                // Create a composite key (e.g., IND_1, S_1, S_a) to prevent overwriting when there are multiple subfields or indicators in the same tag
                $chave_composta = $tipo_linha . "_" . $code;

                $subfield_defs[$chave_composta] = [
                    'is_ind'    => ($tipo_linha == 'IND'), // Special mark to indicate whether it is an indicator
                    'title'     => self::sanitize(isset($parts[2]) ? $parts[2] : ''),
                    'type'      => trim(isset($parts[7]) ? $parts[7] : 'X'),
                    'size'      => $size,
                    'maxlength' => $maxlength,
                    'index'     => $indexType,
                    'base_alfa' => $picklistFile !== '' ? $picklistFile : (isset($arrHttp["base"]) ? $arrHttp["base"] : ""),
                    'prefix'    => trim(isset($parts[12]) ? $parts[12] : ''),
                    'format'    => trim(isset($parts[13]) ? $parts[13] : ''),
                    'options'   => $options
                ];
            }
        }

        echo "<td colspan='2' class='table-fdt-four' style='padding: 15px 10px 20px 0;'>";

        echo "<div style='font-weight:bold; font-size:14px; color:#0056b3; margin-bottom:10px; border-bottom:1px solid #e0e0e0; padding-bottom:5px;'>";
        echo self::sanitize($titulo);
        echo "</div>";

        $campo_valor = isset($valortag[$tag]) ? $valortag[$tag] : "";
        echo "<input type='hidden' id='tag{$tag}' name='tag{$tag}' value='" . self::sanitize($campo_valor) . "'>";

        echo "<div id='inline_container_{$tag}' class='abcd-inline-subfields' style='border: 1px solid #c0c0c0; border-left: 4px solid #0056b3; background-color: #fafcfc; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>";
        echo "<table class='table-fdt' style='width:100%; margin:0; border:none; background: transparent; border-collapse: collapse;'>";
        echo "<tbody id='tbody_{$tag}'>";

        $occurrences = explode("\n", $campo_valor);
        if (empty(trim($campo_valor))) $occurrences = [""];

        foreach ($occurrences as $idx => $occ_val) {
            self::renderRow($tag, $idx, $occ_val, $subfield_defs);
        }

        self::renderRow($tag, 999, "", $subfield_defs, true);

        echo "</tbody></table></div></td></tr>";

        self::injectJS();
    }

    // Smart extraction depending on whether it is IND or S
    private static function renderRow($tag, $idx, $occ_val, $subfield_defs, $isTemplate = false)
    {
        $display = $isTemplate ? "id='template_{$tag}' style='display:none'" : "class='inline-occ-row'";
        $bgColor = ($idx % 2 == 0) ? '#ffffff' : '#eaf4f4';

        echo "<tr $display style='background:{$bgColor};'>";

        echo "<td class='row-num' style='width:30px; color:#777; font-size:11px; text-align:center; vertical-align:middle; font-weight:bold; border-right: 1px solid #eee; border-bottom: 2px solid #b9d8d9;'>" . ($idx + 1) . "</td>";

        echo "<td style='padding: 10px 15px; border-bottom: 2px solid #b9d8d9;'>";
        echo "<div style='display: grid; grid-template-columns: minmax(120px, auto) 1fr; gap: 8px 15px; align-items: center;'>";

        // Ensure that we capture the first two characters if there are indicators in the database, but only if it's not a template row (which should start empty)
        $ind_string = "";
        if (!$isTemplate && strlen($occ_val) > 0) {
            // The MARC standard specifies that, if there are indicators, they consist of the first two characters.
            if (substr($occ_val, 0, 1) !== '^') {
                $pos_delimitador = strpos($occ_val, '^');
                if ($pos_delimitador !== false && $pos_delimitador <= 2) {
                    $ind_string = substr($occ_val, 0, $pos_delimitador);
                } elseif ($pos_delimitador === false && strlen($occ_val) == 2) {
                    $ind_string = $occ_val; // Exceção: Campo só com indicadores
                }
            }
        }

        foreach ($subfield_defs as $chave_composta => $def) {
            // It strips the actual code from the subfield (e.g., "IND_1" becomes "1" again, 'S_a' becomes "a" again)
            $parts = explode('_', $chave_composta, 2);
            $code = isset($parts[1]) ? $parts[1] : $chave_composta;

            $val = "";
            $label_sufixo = "";

            if ($def['is_ind']) {
                $label_sufixo = " <b>(IND{$code})</b>";
                if (!$isTemplate) {
                    if ($code == '1' && strlen($ind_string) >= 1) $val = substr($ind_string, 0, 1);
                    if ($code == '2' && strlen($ind_string) >= 2) $val = substr($ind_string, 1, 1);
                }
            } else {
                $label_sufixo = " (<b>^{$code}</b>)";
                if (!$isTemplate) {
                    $val = self::sanitize(SubfieldHelper::extract($occ_val, $code));
                }
            }

            echo "<div style='font-size:12px; color:#444; white-space: nowrap;'>{$def['title']}{$label_sufixo}</div>";

            echo "<div class='subfield-input-container' style='display:flex; align-items:center; width:100%;'>";

            // Added a 'data-is-ind' attribute to the HTML so that JavaScript can tell the difference between indicators and subfields, which is crucial for constructing the correct MARC21 string.
            $data_is_ind = $def['is_ind'] ? "data-is-ind='true'" : "";

            if (!empty($def['options']) || $def['type'] == 'S') {
                echo "<select class='inline-sub-input td' data-subcode='{$code}' {$data_is_ind} style='padding: 4px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px; flex-grow:1;' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>";
                echo "<option value=''></option>";
                foreach ($def['options'] as $optVal => $optLabel) {
                    $selected = ($val === $optVal) ? "selected" : "";
                    echo "<option value='" . self::sanitize($optVal) . "' $selected>" . self::sanitize($optLabel) . "</option>";
                }
                echo "</select>";
            } elseif ($def['type'] == 'U') {
                $unique_etq = $tag . "_" . $idx . "_" . $code;

                echo "<input type='text' class='inline-sub-input td' id='tag{$unique_etq}' data-subcode='{$code}' {$data_is_ind} value='{$val}' size='{$def['size']}' {$def['maxlength']} style='padding: 4px 6px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px; flex-grow: 1;' onkeyup='ABCD_updateHiddenTag(\"{$tag}\")' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>";

                echo "<div style='display:flex; gap:4px; margin-left:6px;'>";
                echo "<button type='button' class='abcd-btn-upload' style='padding: 5px 10px;' onclick=\"ABCD_openUploadModal('tag{$unique_etq}')\" title='Enviar Arquivo'><i class='fas fa-cloud-upload-alt'></i></button>";
                echo "<button type='button' class='abcd-btn-explore' style='padding: 5px 10px;' onclick=\"SelectArchivo('tag{$unique_etq}','forma1')\" title='Explorar Servidor'><i class='far fa-folder-open'></i></button>";
                echo "</div>";

                if (method_exists('UploadRenderer', 'injectAssets')) {
                    UploadRenderer::injectAssets();
                }
            } else {
                // Restores the original behavior: Type 'X' is Textarea, Type 'XF' is Input Text
                if (strtoupper($def['type']) === 'X') {
                    $rows = 1;
                    if ($def['maxlength'] !== '') {
                        $rows = $def['size'];
                    }
                    echo "<textarea class='inline-sub-input td' data-subcode='{$code}' {$data_is_ind} rows='{$rows}' style='padding: 4px 6px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px; flex-grow: 1; resize: vertical; min-height: 28px; line-height: 1.4;' onkeyup='ABCD_updateHiddenTag(\"{$tag}\")' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>{$val}</textarea>";
                } else {
                    echo "<input type='text' class='inline-sub-input td' data-subcode='{$code}' {$data_is_ind} value='{$val}' size='{$def['size']}' {$def['maxlength']} style='padding: 4px 6px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px; flex-grow: 1;' onkeyup='ABCD_updateHiddenTag(\"{$tag}\")' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>";
                }
            }

            echo "</div>";
        }

        echo "</div></td>";

        echo "<td style='width:50px; vertical-align:middle; text-align:center; border-left: 1px solid #eee; border-bottom: 2px solid #b9d8d9;'>";
        echo "<a href='javascript:void(0)' onclick='ABCD_addInlineRow(\"{$tag}\", this)' title='Adicionar' style='display:inline-block; margin-bottom: 8px;'><i class='fas fa-plus' style='color:#28a745; font-size: 14px;'></i></a><br>";
        
        echo "<a href='javascript:void(0)' onclick='ABCD_removeInlineRow(\"{$tag}\", this)' title='Remover'><i class='fas fa-trash-alt' style='color:#dc3545; font-size: 14px;'></i></a>";
        echo "</td></tr>";
    }

    // MARC21-Compliant JavaScript Builder
    private static function injectJS()
    {
        if (self::$jsInjected) return;
        self::$jsInjected = true;
        echo "<script>
        function ABCD_updateHiddenTag(tag) {
            var tbody = document.getElementById('tbody_' + tag);
            var rows = tbody.querySelectorAll('.inline-occ-row');
            var isisStringArray = [];
            
            rows.forEach(function(row) {
                var inputs = row.querySelectorAll('.inline-sub-input');
                var occString = '';
                var ind1 = ' '; // Padrão vazio MARC
                var ind2 = ' ';
                var hasSubfieldsData = false;
                var hasIndicators = false;

                // Primeiro Passamos lendo os indicadores
                inputs.forEach(function(input) {
                    if (input.hasAttribute('data-is-ind')) {
                        var code = input.getAttribute('data-subcode');
                        var val = input.value;
                        if (val === '') val = ' '; // Um espaço em branco se estiver vazio
                        
                        if (code == '1') { ind1 = val; hasIndicators = true; }
                        if (code == '2') { ind2 = val; hasIndicators = true; }
                    }
                });

                // Agora lemos os subcampos reais
                inputs.forEach(function(input) {
                    if (!input.hasAttribute('data-is-ind')) {
                        var code = input.getAttribute('data-subcode');
                        var val = input.value.trim();
                        if (val !== '') { 
                            if (code === '_') {
                                implicitSubfield = val;
                                hasSubfieldsData = true;
                            } else {
                                explicitSubfields += '^' + code + val; 
                                hasSubfieldsData = true; 
                            }
                        }
                    }
                });
                
                // Se houver dados no subcampo, montamos a string final: ind1 + ind2 + subcampos
                if (hasSubfieldsData) {
                    var finalString = (hasIndicators ? (ind1 + ind2) : '') + occString;
                    isisStringArray.push(finalString);
                }
            });
            
            var hiddenInput = document.getElementById('tag' + tag);
            if(hiddenInput && hiddenInput.value !== isisStringArray.join('\\n')) {
                hiddenInput.value = isisStringArray.join('\\n');
                if(typeof CheckInventory === 'function') CheckInventory();
            }
        }

        function ABCD_addInlineRow(tag, btn) {
            var tbody = document.getElementById('tbody_' + tag);
            var template = document.getElementById('template_' + tag);
            var clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'table-row';
            clone.className = 'inline-occ-row';
            
            var uniqueId = new Date().getTime();

            clone.querySelectorAll('.inline-sub-input').forEach(function(input) {
                input.removeAttribute('name'); 
                input.value = '';
                
                var code = input.getAttribute('data-subcode');
                
                if (input.id && input.id.indexOf('tag' + tag) === 0) {
                    var newId = 'tag' + tag + '_' + uniqueId + '_' + code;
                    input.id = newId;
                    
                    var container = input.closest('.subfield-input-container');
                    if (container) {
                        var btnUp = container.querySelector('.abcd-btn-upload');
                        if (btnUp) btnUp.setAttribute('onclick', 'ABCD_openUploadModal(\"' + newId + '\")');
                        
                        var btnEx = container.querySelector('.abcd-btn-explore');
                        if (btnEx) btnEx.setAttribute('onclick', 'SelectArchivo(\"' + newId + '\",\"forma1\")');
                    }
                }
            });

            var currentRow = btn.closest('tr');
            currentRow.parentNode.insertBefore(clone, currentRow.nextSibling);
            ABCD_updateRowNumbers(tag);
        }

        function ABCD_removeInlineRow(tag, btn) {
            var tbody = document.getElementById('tbody_' + tag);
            var rows = tbody.querySelectorAll('.inline-occ-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                ABCD_updateRowNumbers(tag);
                ABCD_updateHiddenTag(tag);
            } else {
                tbody.querySelectorAll('.inline-sub-input').forEach(i => i.value = '');
                ABCD_updateHiddenTag(tag);
            }
        }

        function ABCD_updateRowNumbers(tag) {
            document.querySelectorAll('#tbody_' + tag + ' .inline-occ-row').forEach((row, idx) => {
                var cell = row.querySelector('.row-num');
                if (cell) cell.textContent = idx + 1;
                // Mantém o efeito zebra perfeito mesmo ao adicionar/remover linhas
                row.style.background = (idx % 2 === 0) ? '#ffffff' : '#eaf4f4';
            });
        }

        function ABCD_abrirIndice(btn, tag, code, prefix, base_alfa, format) {
            var container = btn.closest('.subfield-input-container');
            var input = container.querySelector('.inline-sub-input');
            
            if (!input.name) {
                input.name = 'tag_' + tag + '_' + code + '_' + new Date().getTime();
            }
            
            if (typeof AbrirIndiceAlfabetico === 'function') {
                AbrirIndiceAlfabetico(input, prefix, code, ';', base_alfa, base_alfa + '.par', tag, '1', '', format);
            } else {
                alert('A função AbrirIndiceAlfabetico não está disponível.');
            }
        }

        if (!window.abcdInlineInterval) {
            window.abcdInlineInterval = setInterval(function() {
                var containers = document.querySelectorAll('.abcd-inline-subfields');
                containers.forEach(function(c) {
                    var tag = c.id.replace('inline_container_', '');
                    ABCD_updateHiddenTag(tag);
                });
            }, 1000);
        }
        </script>";
    }
}
