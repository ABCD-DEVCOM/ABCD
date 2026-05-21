<?php
/**
 * Name: HtmlAreaRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for HTML area fields in the ABCD data entry editor.
 * This class generates the HTML for rendering a textarea input for fields that are configured to accept HTML   
 * content. It also integrates the CKEditor for rich text editing capabilities.
 */

class HtmlAreaRenderer {
    public static function render($tag, $linea, $numl, $tipoH): void {
        global $valortag, $fdt, $ver, $arrHttp, $Path, $xEditor, $xUrlEditor, $FCKConfigurationsPath, $FCKEditorPath, $db_path, $msgstr;
        
        if (trim($numl) == "") $numl = 20;
        $numl = $numl * 30;
        if ($tipoH != "B") {
            if (!isset($valortag[$tag])) {
                $valortag[$tag] = "";
            } else {
                $valortag[$tag] = trim($valortag[$tag]);
            }
        } else {
            $fp = file($db_path . $arrHttp["base"] . "/html/" . trim($valortag[$tag]));
            $valortag[$tag] = "";
            foreach ($fp as $value) $valortag[$tag] .= trim($value);
        }
        $valortag[$tag] = str_replace("\r", "", $valortag[$tag]);
        $valortag[$tag] = str_replace("\n", "", $valortag[$tag]);
        
        echo '<td class="table-fdt-four">';
        echo '<textarea  id="tag' . $tag . '" name="tag' . $tag . '" rows="' . $numl . '" >';
        echo str_replace("'", "`", $valortag[$tag]);
        echo '</textarea>';
        echo '</td>';
?>
        <script>
            CKEDITOR.replace('<?php echo "tag$tag" ?>', {
                height: 260
            });
        </script>
        <?php
    }
}