<?php
/**
 * Name: TabRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * 
 * Description: Renderer for tab fields in the ABCD data entry editor.
 * This class generates the HTML for rendering a tab container based on the field configuration and options provided. It manages the opening and closing of divs and table rows to ensure proper structure for the tabbed interface.
 */


class TabRenderer
{
    /**
     * Renders the container for a MARC tab or another metadata standard that works best in tabs.
     */
    public static function render($linea, &$wrapperopen, &$ixant): void
    {
        $t = explode('|', $linea);
        $titulo = trim($t[2]);

        // If there is a previous tab or header open, we close the divs and the cell
        if ($wrapperopen) {
            echo "</div></td></tr>\n";
        }

        $ixant++;

        // We create a row that will span all columns
        // The "abcd-tab-pane" class will be used by JavaScript to identify the tabs
        echo "<tr class='abcd-tab-row'><td colspan='4' class='abcd-tab-container-cell' style='padding:0;'>\n";
        echo "<div class='abcd-tab-pane' data-tab-title='" . htmlspecialchars($titulo, ENT_QUOTES) . "' style='display:none;'>\n";
        echo "<div id='wrapper_$ixant' class='group-fields' style='display:block; border:none;'>\n";

        $wrapperopen = true;
    }
}
