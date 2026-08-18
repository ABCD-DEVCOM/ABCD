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

        // 1. Obtém as facetas ativas da URL (se houver)
        $Expr_facetas = isset($_REQUEST["facetas"]) && $_REQUEST["facetas"] != "" ? urldecode($_REQUEST["facetas"]) : "";

        // 2. Constrói a expressão usando a Fonte Única de Verdade
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
            // Array para armazenar o HTML gerado para as facetas desta base
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

                    // Conta quantos tipos diferentes de filtros existem nesta faceta
                    $total_termos_faceta = count($ocorrencias);

                    // Constrói o HTML da faceta em memória
                    $html_facetas_base .= "<div class='faceta-box mb-2'>";

                    $html_facetas_base .= "<a class='d-flex justify-content-between align-items-center text-decoration-none pb-2 pt-2 border-bottom facet-toggle text-secondary' data-bs-toggle='collapse' href='#" . $collapse_id . "' role='button' aria-expanded='true' aria-controls='" . $collapse_id . "' style='font-size: 0.9rem;'>";

                    // Título da faceta (à esquerda). Adicionado text-truncate para prevenir quebra de linha se o título for gigante.
                    $html_facetas_base .= "<span class='fw-bold text-truncate pe-2'>" . trim($cabecalho) . "</span>";

                    // Grupo alinhado à direita (Bolinha + Setinha) usando 'gap-2' para manter uma distância fixa e elegante.
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

                        $html_facetas_base .= '<li class="list-group-item p-0" style="border: none; border-bottom: 1px dashed #f0f0f0;">';
                        $html_facetas_base .= '<a href="javascript:RefinF(\'' . $faceta_atual . '\', \'' . $expresionClean . '\',\'' . $base_atual . '\')" class="d-flex justify-content-between align-items-center py-2 px-1 text-decoration-none faceta-link ' . $negrito . '">';
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

            // --- A MÁGICA ACONTECE AQUI ---
            // Só imprime o cabeçalho da base e as facetas se houver pelo menos 1 ocorrência válida
            if ($total_ocorrencias_base > 0) {
                // Cabeçalho da Base de Dados com destaque visual (bg-light e padding)
                echo "<h6 class='mt-4 mb-2 p-2 bg-light border rounded text-dark fw-bold text-uppercase' style='font-size: 0.85rem; letter-spacing: 0.5px;'>";
                echo "<i class='fas fa-database me-2 text-secondary'></i>" . $bd_list[$base_atual]['descripcion'];
                echo "</h6>";

                // Imprime todas as facetas que foram guardadas em memória
                echo $html_facetas_base;
            }
        }
    }
}

// ... DAQUI PARA BAIXO O ARQUIVO CONTINUA IGUAL (A partir do if (function_exists('PresentarExpresion')) ) ...


if (function_exists('PresentarExpresion')) {

    // =================================================================
    // SOLUÇÃO ELEGANTE:
    // 1. Busque a expressão LIMPA (sem prefixos) para exibir no H5
    // $resultadoLimpo terá algo como: "maria and Rio de Janeiro"
    // =================================================================
    $resultadoLimpo = PresentarExpresion($base);
?>

    <h5 class="mt-4"><?php echo $msgstr["front_su_consulta"]; ?>: </h5>

    <div id="termosAtivos" class="mb-3" data-link-inicial="<?php echo htmlspecialchars($link_logo); ?>">
        <?php
        // 1. Buscamos a expressão BRUTA (técnica)
        $expBruta = construir_expresion(); // Ex: "(TW_maria) and (PA_Rio de Janeiro :)"
        $expFormatada = str_replace('"', '', $expBruta);

        // 2. Dividimos a expressão bruta
        $termosBrutos = preg_split('/\s+and\s+/i', $expFormatada);

        // --- CORREÇÃO 3: Preparar verificação de truncagem para o display ---
        $termo_livre_req = isset($_REQUEST["Sub_Expresion"]) ? urldecode($_REQUEST["Sub_Expresion"]) : "";
        $tem_truncagem_req = (strpos($termo_livre_req, '$') !== false);
        $termo_raiz_req = $tem_truncagem_req ? str_replace('$', '', strtolower($termo_livre_req)) : '';
        // -------------------------------------------------------------------

        foreach ($termosBrutos as $termo) {

            // 3. $termoRaw É O TERMO TÉCNICO (para a função)
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
            // -----------------------------------------------------------------------------

            // =========================================================
            // AQUI ESTÁ A LÓGICA ELEGANTE:
            // =========================================================

            // O onclick="" usa o termo TÉCNICO ($termoRaw) para funcionar
            echo "<button type='button' class='btn btn-outline-primary btn-sm mr-1 mb-1 termo' onclick='removerTermo(\"" . htmlspecialchars($termoRaw, ENT_QUOTES, 'UTF-8') . "\")'>";

            // O texto visível do botão usa o termo LIMPO ($termoDisplay)
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
        // Injeta o $ aqui também se necessário, para que o input hidden mantenha a consistência
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