<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   facets.php
 *  Purpose:  Controls the facets of the pages in the OPAC
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2026-04-04 rogercgui Refactor visual of facets with Bootstrap 5, added collapse functionality and counts for each facet term.
 *  2026-04-10 rogercgui Added dynamic badge counts to facet terms and total counts for each facet category.
 *  2026-09-28 rogercgui Fixes the submission of characters such as apostrophes without breaking the JavaScript.
 *  2026-09-29 rogercgui Added a hook for additional facets in the sidebar if the function exists.
 * -------------------------------------------------------------------------
 */


function facetas()
{
    global $db_path, $lang, $msgstr, $actparfolder, $xWxis, $busqueda, $Expresion, $primera_base, $ABCD_scripts_path, $IsisScript, $expresion, $base, $Web_Dir;

    $facetas = "S";

    include("includes/leer_bases.php");

    if (isset($facetas) and $facetas == "S") {

        if (isset($_REQUEST['base']) && $_REQUEST['base'] != "") {
            $bases_para_processar = [$_REQUEST['base']];
        } else {
            $bases_para_processar = array_keys($bd_list);
        }

        // Retrieves the active facets from the URL (if any)
        $Expr_facetas = isset($_REQUEST["facetas"]) && $_REQUEST["facetas"] != "" ? urldecode($_REQUEST["facetas"]) : "";

        // Construct the expression using the Single Source of Truth
        require_once $Web_Dir . 'includes/search/expression_builder.php';
        $expresionOriginal = montarExpressaoBusca(construir_expresion(), $Expr_facetas);

        $termo_livre = isset($_REQUEST["Sub_Expresion"]) ? urldecode($_REQUEST["Sub_Expresion"]) : "";
        $tem_truncagem = (strpos($termo_livre, '$') !== false);

        $expresionSemAcento = removeacentos($expresionOriginal);

        $expresionClean = str_replace(['(', ')', '+and+'], ['', '', ') and ('], $expresionSemAcento);

        foreach ($bases_para_processar as $base_atual) {
            $db_facetas = $db_path . $base_atual . "/opac/" . $_REQUEST["lang"] . "/" . $base_atual . "_facetas.dat";

            if (!file_exists($db_facetas)) continue;

            $conteudo = file($db_facetas, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (empty($conteudo)) continue;

            $facet_counter = 0;
            // An array to store the HTML generated for the facets in this database
            $html_facetas_base = "";
            $total_ocorrencias_base = 0;

            foreach ($conteudo as $linha) {
                $facet_counter++;
                list($cabecalho, $formato, $pref, $ordem) = array_pad(explode("|", $linha), 4, 'Q');

                $arrHttp["base"] = $base_atual;
                $arrHttp["cipar"] = $base_atual . ".par";
                $arrHttp["Opcion"] = "buscar";
                $Formato = trim($formato);

                $query_param = "&cipar=" . $db_path . $actparfolder . $arrHttp["cipar"];

                $expr_final = $expresionSemAcento;
                if ($tem_truncagem && substr($expr_final, -1) != '$') {
                    $expr_final .= '$';
                }
                $query_param .= "&Expresion=" . $expr_final;
                $query_param .= "&Opcion=" . $arrHttp["Opcion"];
                $query_param .= "&base=" . $base_atual;
                $query_param .= "&from=1";
                $query_param .= "&Formato=" . $Formato;

                $IsisScript = $xWxis . "opac/facetas.xis";
                $query = $query_param;

                include($ABCD_scripts_path . "central/common/wxis_llamar.php");

                $ocorrencias = [];

                foreach ($contenido as $value) {
                    $value_tratado = trim($value);
                    if (!empty($value_tratado)) {
                        $ocorrencias[$value_tratado] = ($ocorrencias[$value_tratado] ?? 0) + 1;
                        $total_ocorrencias_base++; // Incrementa o total geral da base
                    }
                }

                if (!empty($ocorrencias)) {
                    if (strtoupper(trim($ordem)) === 'A') {
                        ksort($ocorrencias);
                    } else {
                        arsort($ocorrencias);
                    }

                    $collapse_id = "collapseFacet_" . $base_atual . "_" . $facet_counter;

                    // Count how many different types of filters there are in this facet
                    $total_termos_faceta = count($ocorrencias);

                    // Builds the facet’s HTML in memory
                    $html_facetas_base .= "<div class='faceta-box mb-2'>";

                    $html_facetas_base .= "<a class='d-flex justify-content-between align-items-center text-decoration-none pb-2 pt-2 border-bottom facet-toggle text-secondary' data-bs-toggle='collapse' href='#" . $collapse_id . "' role='button' aria-expanded='true' aria-controls='" . $collapse_id . "' style='font-size: 0.9rem;'>";

                    // Facet title (on the left). 'text-truncate' has been added to prevent line breaks if the title is very long.
                    $html_facetas_base .= "<span class='fw-bold text-truncate pe-2'>" . trim($cabecalho) . "</span>";

                    // Right-aligned group (dot + arrow) using ‘gap-2’ to maintain a fixed, elegant spacing.
                    $html_facetas_base .= "<div class='d-flex align-items-center gap-2'>";
                    $html_facetas_base .= "<span class='badge bg-secondary text-white rounded-pill' style='font-size: 0.7rem; font-weight: normal;'>" . $total_termos_faceta . "</span>";
                    $html_facetas_base .= "<i class='fas fa-chevron-down transition-icon' style='font-size: 0.8rem;'></i>";
                    $html_facetas_base .= "</div>";

                    $html_facetas_base .= "</a>";

                    $html_facetas_base .= "<div class='collapse show' id='" . $collapse_id . "'>";
                    $html_facetas_base .= '<ul class="list-group list-group-flush facet-scroll-list pt-1">';

                    foreach ($ocorrencias as $termo => $quantidade) {
                        $faceta_atual = $pref . removeacentos($termo);
                        $negrito = (stripos($expresionClean, $faceta_atual) !== false) ? 'fw-bold text-primary' : 'text-body';
                        $termoFaceta = trim(preg_replace(['/^[^_]*_/', '/[:\/.]/'], '', $termo), " )(");

                        // Remove the quotation marks around the term and the expression so as not to break the JavaScript onclick event.
                        $js_faceta = htmlspecialchars(addslashes($faceta_atual), ENT_QUOTES, 'UTF-8');
                        $js_expr   = htmlspecialchars(addslashes($expresionClean), ENT_QUOTES, 'UTF-8');
                        $js_base   = htmlspecialchars(addslashes($base_atual), ENT_QUOTES, 'UTF-8');

                        $html_facetas_base .= '<li class="list-group-item p-0" style="border: none; border-bottom: 1px dashed #f0f0f0;">';
                        $html_facetas_base .= '<a href="javascript:RefinF(\'' . $js_faceta . '\', \'' . $js_expr . '\',\'' . $js_base . '\')" class="d-flex justify-content-between align-items-center py-2 px-1 text-decoration-none faceta-link ' . $negrito . '">';

                        $html_facetas_base .= '<span class="text-truncate pe-2" style="font-size: 0.9rem;">' . htmlspecialchars($termoFaceta) . '</span>';

                        $html_facetas_base .= '<span class="badge bg-light text-secondary rounded-pill border" style="font-weight: 500;">' . $quantidade . '</span>';
                        $html_facetas_base .= '</a>';
                        $html_facetas_base .= '</li>';
                    }

                    $html_facetas_base .= '</ul>';
                    $html_facetas_base .= "</div>"; // /.collapse
                    $html_facetas_base .= "</div>"; // /.faceta-box
                }
            }

            // It only prints the database header and the facets if there is at least one valid occurrence
            if ($total_ocorrencias_base > 0) {
                // Database header with visual highlights (bg-light and padding)
                echo "<h6 class='mt-4 mb-2 p-2 bg-light border rounded text-dark fw-bold text-uppercase' style='font-size: 0.85rem; letter-spacing: 0.5px;'>";
                echo "<i class='fas fa-database me-2 text-secondary'></i>" . $bd_list[$base_atual]['descripcion'];
                echo "</h6>";

                // Prints all the facets that have been stored in memory
                echo $html_facetas_base;
            }

            if (function_exists('abcd_run_hook')) {
                echo abcd_run_hook('opac_sidebar_facets', '', $expresionOriginal);
            }

        }
    }
}


if (function_exists('PresentarExpresion')) {

    // Find the cleaned-up expression (without prefixes) to display in H5; $cleanedResult will be something like: 'maria and Rio de Janeiro'
    $resultadoLimpo = PresentarExpresion($base);
?>

    <h5 class="mt-4"><?php echo $msgstr["front_su_consulta"]; ?>: </h5>

    <div id="termosAtivos" class="mb-3" data-link-inicial="<?php echo htmlspecialchars($link_logo); ?>">
        <?php
        // Search for the raw expression.
        $expBruta = construir_expresion(); // Ex: "(TW_maria) and (PA_Rio de Janeiro :)"
            $expFormatada = str_replace('"', '', $expBruta);

        // Divide the raw expression
        $termosBrutos = preg_split('/\s+and\s+/i', $expFormatada);

        // Set up a truncation check for the display ---
         $termo_livre_req = isset($_REQUEST["Sub_Expresion"]) ? urldecode($_REQUEST["Sub_Expresion"]) : "";
        $tem_truncagem_req = (strpos($termo_livre_req, '$') !== false);
        $termo_raiz_req = $tem_truncagem_req ? str_replace('$', '', strtolower($termo_livre_req)) : '';
        // -------------------------------------------------------------------

        foreach ($termosBrutos as $termo) {

            // $termoRaw is the technical term (for the function)
            // Ex: "(TW_maria)" ou "(PA_Rio de Janeiro :)"
            $termoRaw = trim($termo);
            if (empty($termoRaw)) continue;

            // 4. $termoDisplay É O TERMO LIMPO (para o usuário ver)
            // Removemos o prefixo (ex: TW_), os parênteses e outros caracteres
            $termoDisplay = strtolower(trim(preg_replace('/^[^_]*_/', '', $termoRaw), " )("));
            $termoDisplay = str_replace([':', '/', '.'], '', $termoDisplay); // Limpeza final

            // --- CORREÇÃO 4: Adicionar o $ visualmente se corresponder à busca original ---
            if ($tem_truncagem_req) {
                // Compara se o termo exibido é igual à raiz digitada pelo usuário
                if (removeacentos($termoDisplay) == removeacentos($termo_raiz_req)) {
                    $termoDisplay .= '$';
                }
            }

            // The onclick="" attribute uses the term TECHNICAL ($termoRaw) to work with the JavaScript function removerTermo(), which will remove the term from the search expression.
            echo "<button type='button' class='btn btn-outline-primary btn-sm mr-1 mb-1 termo' onclick='removerTermo(\"" . htmlspecialchars($termoRaw, ENT_QUOTES, 'UTF-8') . "\")'>";

            // The button’s display text uses the term CLEAN ($termoDisplay)
            echo $termoDisplay;
            echo " <span aria-hidden='true'>&times;</span></button>";
        }
        ?>
    </div>

    <h4 class="mt-4"><?php echo $msgstr['front_afinar'] ?></h4>
    <form id="facetasForm" method="GET" class="form-inline mt-3 mb-3" onsubmit="event.preventDefault(); processarTermosLivres();">
        <input type="hidden" name="page" value="startsearch">
        <input type="hidden" name="desde" value="1">
        <input type="hidden" name="pagina" value="1">
        <?php
        // Insert the $ here too if necessary, so that the hidden input field remains consistent
        $expresion = construir_expresion();
        if ($tem_truncagem_req && strpos($expresion, '$') === false) {
            $expresion .= '$';
        }
        ?>
        <input type="hidden" name="Expresion" id="Expresion" value="<?php echo htmlspecialchars($expresion); ?>">
        <input type="hidden" name="Opcion" value="directa">
        <?php if (isset($_REQUEST['base'])) { ?>
            <input type="hidden" name="base" id="base" value="<?php echo htmlspecialchars($_REQUEST['base'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <input type="hidden" name="lang" value="<?php echo htmlspecialchars($_REQUEST['lang'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($_REQUEST['indice_base'])) { ?>
            <input type="hidden" name="indice_base" value="<?php echo $_REQUEST['indice_base']; ?>">
        <?php } ?>
        <input type="hidden" name="modo" value="1B">
        <input type="hidden" name="resaltar" value="S">

        <div class="form-group mr-2 mb-2">
            <label for="termosLivres" class="mr-2"><?php echo $msgstr['free_terms'] ?></label>
            <input type="text" class="form-control" name="termosLivres" id="termo-busca" placeholder="<?php echo $msgstr['type_terms'] ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $msgstr['add_terms'] ?></button>
    </form>

<?php
    facetas();
} else {
    echo "";
}
?>