<?php
/**
 * Name: GroupRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for group fields in the ABCD data entry editor.
 * This class generates the HTML for rendering group fields, which can contain multiple subfields, based on the field configuration and options provided. It supports both traditional and inline rendering modes for subfields.
 */

class GroupRenderer {
    
    private static $jsInjected = false;

    private static function sanitize($text) {
        if (!mb_check_encoding($text, 'UTF-8')) {
            return htmlspecialchars($text, ENT_QUOTES, 'ISO-8859-1');
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private static function loadPicklist($file) {
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

    public static function render($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $vars, &$ivars, $nsc): void {
        $useInline = ConfigHelper::isInlineSubfieldsEnabled();
        $subfieldsDefLines = self::extractSubfields($tag, $vars, $ivars, $nsc);

        if ($useInline && empty($ver)) {
            self::renderInline($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines);
        } else {
            self::renderTraditional($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines);
        }
    }

    private static function extractSubfields($tag, $vars, &$ivars, $nsc): array {
        global $base_fdt;
        $filas = [];
        $sfld = isset($vars[$ivars + 1]) ? $vars[$ivars + 1] : "";
        $sf = explode('|', $sfld);
        
        if ($sf[0] != "S") {
            for ($nx = 0; $nx < count($base_fdt); $nx++) {
                $ll = $base_fdt[$nx];
                $ll_a = explode('|', $ll);
                if ($ll_a[1] == $tag) {
                    $nsc_local = strlen(trim(isset($ll_a[5]) ? $ll_a[5] : ''));
                    for ($ixsc = 1; $ixsc <= $nsc_local; $ixsc++) {
                        $nx++;
                        if (isset($base_fdt[$nx])) $filas[] = rtrim($base_fdt[$nx]);
                    }
                    break;
                }
            }
        } else {
            for ($ixsc = 1; $ixsc <= $nsc; $ixsc++) {
                $ivars++;
                if (isset($vars[$ivars])) $filas[] = rtrim($vars[$ivars]);
            }
        }
        return $filas;
    }

    private static function renderTraditional($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines): void {
        TextRenderer::render($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda);
        echo "\n<tr style='display:none'><td colspan=4>";
        echo "<input type=hidden name=eti$tag value=\"".self::sanitize($linea01)."\">\n";
        foreach ($subfieldsDefLines as $lin) {
            echo "<input type=hidden name=eti$tag value=\"".self::sanitize($lin)."\">\n";
        }
        echo "\n</td></tr>\n";
    }

    private static function renderInline($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $subfieldsDefLines): void {
        global $valortag, $arrHttp;
        
        $subfield_defs = [];
        foreach($subfieldsDefLines as $line) {
            $parts = explode('|', $line);
            $code = trim(isset($parts[5]) ? $parts[5] : '');
            
            if ($code !== '') {
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

                $subfield_defs[$code] = [
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
        echo "<span style='font-size:11px; color:#888; font-weight:normal; margin-left:10px;'>(Modo Inline)</span>";
        echo "</div>";
        
        $campo_valor = isset($valortag[$tag]) ? $valortag[$tag] : "";
        echo "<input type='hidden' id='tag{$tag}' name='tag{$tag}' value='" . self::sanitize($campo_valor) . "'>";
        
        echo "<div id='inline_container_{$tag}' class='abcd-inline-subfields' style='border: 1px solid #c0c0c0; border-left: 4px solid #0056b3; background-color: #fafcfc; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>";
        echo "<table class='table-fdt' style='width:100%; margin:0; border:none; background: transparent;'>";
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

    private static function renderRow($tag, $idx, $occ_val, $subfield_defs, $isTemplate = false) {
        $display = $isTemplate ? "id='template_{$tag}' style='display:none'" : "class='inline-occ-row'";
        $bgColor = ($idx % 2 == 0) ? '#ffffff' : '#f5f7f9';
        
        echo "<tr $display style='background:{$bgColor}; border-bottom:1px solid #e5e5e5;'>";
        
        echo "<td class='row-num' style='width:30px; color:#777; font-size:11px; text-align:center; vertical-align:middle; font-weight:bold; border-right: 1px solid #eee;'>".($idx+1)."</td>";
        
        echo "<td style='padding: 10px 15px;'>";
        echo "<div style='display: grid; grid-template-columns: minmax(120px, auto) 1fr; gap: 8px 15px; align-items: center;'>";
        
        foreach ($subfield_defs as $code => $def) {
            $val = $isTemplate ? "" : self::sanitize(SubfieldHelper::extract($occ_val, $code));
            
            echo "<div style='font-size:12px; color:#444; white-space: nowrap;'>{$def['title']} (<b>^{$code}</b>)</div>";
            
            echo "<div class='subfield-input-container' style='display:flex; align-items:center;'>";
            
            if (!empty($def['options']) || $def['type'] == 'S') {
                echo "<select class='inline-sub-input td' data-subcode='{$code}' style='padding: 4px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px;' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>";
                echo "<option value=''></option>";
                foreach ($def['options'] as $optVal => $optLabel) {
                    $selected = ($val == $optVal) ? "selected" : "";
                    echo "<option value='".self::sanitize($optVal)."' $selected>".self::sanitize($optLabel)."</option>";
                }
                echo "</select>";
            } else {
                echo "<input type='text' class='inline-sub-input td' data-subcode='{$code}' value='{$val}' size='{$def['size']}' {$def['maxlength']} style='padding: 4px 6px; border: 1px solid #ccc; font-family: monospace; border-radius: 2px;' onkeyup='ABCD_updateHiddenTag(\"{$tag}\")' onchange='ABCD_updateHiddenTag(\"{$tag}\")'>";
            }
            
            if ($def['index'] == 'D' || $def['index'] == 'T') {
                echo "&nbsp;<a href='javascript:void(0)' class='bt-fdt' onclick='ABCD_abrirIndice(this, \"{$tag}\", \"{$code}\", \"{$def['prefix']}\", \"{$def['base_alfa']}\", \"{$def['format']}\")' title='Índice'><i class='fas fa-search'></i></a>";
            }
            
            echo "</div>";
        }
        
        echo "</div></td>";
        
        echo "<td style='width:50px; vertical-align:middle; text-align:center; border-left: 1px solid #eee;'>";
        echo "<a href='javascript:void(0)' onclick='ABCD_addInlineRow(\"{$tag}\", this)' title='Adicionar' style='display:inline-block; margin-bottom: 8px;'><i class='fas fa-plus' style='color:#28a745; font-size: 14px;'></i></a><br>";
        echo "<a href='javascript:void(0)' onclick='ABCD_removeInlineRow(\"{$tag}\", this)' title='Remover'><i class='fas fa-trash-alt' style='color:#dc3545; font-size: 14px;'></i></a>";
        echo "</td></tr>";
    }

    private static function injectJS() {
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
                var hasData = false;
                inputs.forEach(function(input) {
                    var code = input.getAttribute('data-subcode');
                    var val = input.value.trim();
                    if (val !== '') { occString += '^' + code + val; hasData = true; }
                });
                if (hasData) isisStringArray.push(occString);
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
            
            clone.querySelectorAll('.inline-sub-input').forEach(function(input) {
                input.removeAttribute('name'); 
                input.value = '';
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