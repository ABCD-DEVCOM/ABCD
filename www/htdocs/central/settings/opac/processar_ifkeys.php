<?php
/*
* processar_ifkeys.php
*
* @author Roger C. Guilherme
* @date 2025-10-02
* @description Script to process the ifkeys utility output and generate a clean .dic file for OPAC.
*   
* CHANGE LOG:
* 2025-10-05 rogercgui Added a direct link to view_dic.php after successful generation of the .dic file.
*/

include("conf_opac_top.php");

if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";

$lang = $_SESSION["lang"];


$base = $_REQUEST["base"];
$dic_final_path = $db_path . $base . "/opac/" . $base . ".dic";

?>
<script>
    function confirmarGeracao() {
        document.getElementById('confirm_buttons').style.display = 'none';
        document.getElementById('processing_message').style.display = 'block';
        document.continuar.confirmcount.value = "1"; // Define para 1 para indicar confirmação
        document.continuar.submit();
    }

    function regresar() {
        window.location.href = '<?php echo $backtoscript; ?>';
    }
</script>

<div class="middle form row m-0">
    <div class="formContent col-2 m-2 p-0">
        <?php include("conf_opac_menu.php"); ?>
    </div>
    <div class="formContent col-9 m-2">
        <?php include("menu_dbbar.php"); ?>
        <h3><?php echo $msgstr["dict_generate_fast"] . " - " . htmlspecialchars($base); ?></h3>

        <?php
        if (!isset($_POST["confirmcount"]) || $_POST["confirmcount"] != "1") {
        ?>
            <div align="center">
                <h4><?php echo $msgstr["dict_generate_confirm"] . " para a base: " . htmlspecialchars($base); ?></h4>
                <p><?php echo $msgstr["dict_generate_warning"]; ?></p>
            </div>

            <form name="continuar" action="processar_ifkeys.php" method="post">
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($base); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
                <input type="hidden" name="confirmcount" value="0">
            </form>

            <div id="confirm_buttons" align="center">
                <br><br>
                <button type="button" class="bt-green" onclick="confirmarGeracao()">
                    <i class="fas fa-check"></i> <?php echo $msgstr["procesar"]; ?>
                </button>
                &nbsp; &nbsp;
                <button type="button" class="bt-red" onclick="regresar()">
                    <i class="fas fa-ban"></i> <?php echo $msgstr["cancelar"]; ?>
                </button>
            </div>

            <div id="processing_message" style="display:none;" align="center">
                <h4><i class="fas fa-spinner fa-spin"></i> <?php echo $msgstr["processing"]; ?>...</h4>
                <p><?php echo $msgstr["dict_processing_wait"]; ?></p>
            </div>

        <?php
        } else {
            echo "<h4>" . $msgstr["processing"] . ": " . htmlspecialchars($base) . "</h4>";

            $ifkeys_path = $cisis_path . "ifkeys" . $exe_ext;
            $base_path = $db_path . $base . "/data/" . $base;
            $temp_output_file = $db_path . $base . "/opac/" . $base . "_bruto.tmp";

            $erros = [];
            if (!is_executable($ifkeys_path)) {
                $erros[] = $msgstr["error"] . ": The ifkeys utility was not found or is not executable in: <strong>$ifkeys_path</strong>";
            }
            if (!file_exists($base_path . ".mst")) {
                $erros[] = $msgstr["error"] . ": The main database file (.mst) was not found in: <strong>$base_path.mst</strong>";
            }

            if (!empty($erros)) {
                echo "<h4><font color='red'>" . $msgstr["process_failed"] . "</font></h4>";
                foreach ($erros as $erro) {
                    echo "<div class='alert error'>$erro</div>";
                }
            } else {
                $strINV = "\"$ifkeys_path\" $base_path > \"$temp_output_file\"";
                echo "<p><strong>" . $msgstr["command_executing"] . ":</strong></p>";
                echo "<pre style='background-color:#eee; padding:10px; border:1px solid #ccc; white-space: pre-wrap; word-break: break-all;'>$strINV</pre>";

                $output = [];
                exec($strINV, $output, $status);

                if (!file_exists($temp_output_file) || filesize($temp_output_file) === 0) {
                    echo "<h4><font color='red'>" . $msgstr["process_failed"] . "</font></h4>";
                    echo "<div class='alert error'>" . $msgstr["dict_error_temp"] . " (pode ser um erro de permissão ou o comando `ifkeys` falhou silenciosamente).</div>";
                } else {
                    echo "<h4>" . $msgstr["dict_cleaning_file"] . "</h4>";

                    $handleIn = fopen($temp_output_file, "r");
                    $termosUnicos = [];
                    $totalLinhas = 0;

                    while (($line = fgets($handleIn)) !== false) {
                        $totalLinhas++;
                        if (preg_match('/([A-Z]{2,3}_.*)/', $line, $matches)) {
                            $termoComPrefixo = $matches[1];
                            $termoSemPipes = str_replace('|', '', $termoComPrefixo);
                            $termoLimpo = trim($termoSemPipes, " \t\n\r\0\x0B();:/");
                            if (!empty($termoLimpo)) {
                                $termosUnicos[$termoLimpo] = true;
                            }
                        }
                    }
                    fclose($handleIn);

                    ksort($termosUnicos);
                    $final_content = implode("\n", array_keys($termosUnicos));

                    if (file_put_contents($dic_final_path, $final_content) === false) {
                        echo "<div class='alert error'>" . $msgstr["error"] . ": The final file could not be saved. Check the write permissions.</div>";
                    } else {
                        echo "<div class='alert success'>";
                        echo "<h3><i class='fas fa-check-circle'></i> <strong>" . $msgstr["process_completed"] . "</strong></h3>";
                        echo $msgstr["dict_lines_read"] . ": " . $totalLinhas . "<br>";
                        echo $msgstr["dict_unique_terms"] . ": " . count($termosUnicos) . "<br>";
                        echo $msgstr["file_saved_at"] . ": " . htmlspecialchars($dic_final_path);
                        echo "</div>";

                        echo '<br><a href="view_dic.php?base='.$base.'&lang='.$lang.'" class="bt bt-blue">'.$msgstr["adm_list"].'</a>';
                    }
                    @unlink($temp_output_file);
                }
            }
        }
        ?>
    </div>
</div>

<?php include("../../common/footer.php"); ?>