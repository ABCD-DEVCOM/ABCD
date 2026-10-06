<?php
/*
 * Name: HashRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-10-01
 * Description: Renderer for HASH security fields in the ABCD data entry editor.
 */

class HashRenderer
{
    public static function render($linea, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda): void
    {
        global $valortag;

        $t = explode('|', $linea);
        $campo = isset($valortag[$tag]) ? trim($valortag[$tag]) : "";

        // HASH GENERATION: If not in read mode ($ver) and the field is empty (new record)
        if (!$ver && $campo === "") {
            $campo = bin2hex(random_bytes(32));
            $valortag[$tag] = $campo; // Atualiza a global para ser salva corretamente no .mst
        }

        echo "<td class=\"table-fdt-three\">";
        echo trim($titulo);
        echo "</td>\n";
        echo "<td class='table-fdt-four input-fdt'>\n";

        if ($ver) {
            // View (Read-only mode)
            echo "<span style='font-family: monospace; word-break: break-all; color: #495057;'>" . htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') . "</span>";
        } else {
            // Edit (Read-only with copy UX)
            echo "<div style='display: flex; align-items: center; gap: 8px; width: 100%; max-width: 600px;'>";
            echo "<i class='fas fa-shield-alt' style='color: #28a745;' title='Hash de Segurança'></i>";

            echo "<input type='text' name='tag$tag' id='tag$tag' value='" . htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') . "' readonly ";
            echo "style='box-sizing:border-box; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-family: monospace; font-size: 13px; flex-grow: 1; background-color: #e9ecef; color: #6c757d; cursor: not-allowed;' onfocus='blur()'>";

            if (!empty($campo)) {
                echo "<a href='javascript:void(0)' onclick='navigator.clipboard.writeText(document.getElementById(\"tag$tag\").value)' class='bt-fdt' title='Copiar Hash'><i class='far fa-copy'></i></a>";
            }
            echo "</div>";
        }
        echo "</td></tr>\n";
    }
}
