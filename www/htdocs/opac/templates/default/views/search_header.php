<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   search_header.php
 *  Purpose:  Displays the header for search results in the OPAC
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2025-10-22 rogercgui Created
 *  2025-11-05 rogercgui Added details per database
 * -------------------------------------------------------------------------
 */

function renderSearchResultsHeader($total_registros, $total_por_base, $bd_list, $msgstr, $termo_pesquisado_limpo)
{
    $link_nova_pesquisa = "index.php";

    // Monta a string de detalhes compacta
    $detalhes_html = "";
    $detalhes_array = [];
    if (isset($total_por_base) && is_array($total_por_base) && count($total_por_base) > 0) {
        foreach ($total_por_base as $base_key => $total) {
            $nome_base = isset($bd_list[$base_key]['titulo']) ? $bd_list[$base_key]['titulo'] : $base_key;
            if ($total > 0) {
                $detalhes_array[] = $nome_base . ": <strong>" . $total . "</strong>";
            }
        }
    }

    if (!empty($detalhes_array)) {
        $detalhes_html = '<p class="text-muted mb-0 mt-1" style="font-size: 0.9em;">'
            . ($msgstr["front_detalhes"] ?? "Detalhes") . ": " . implode(' | ', $detalhes_array)
            . '</p>';
    }

    // Protege o termo para o atributo data do HTML
    $termo_escapado = htmlspecialchars($termo_pesquisado_limpo, ENT_QUOTES, 'UTF-8');

    // HTML Final Limpo e Compacto
    $html = '
    <div class="alert alert-light shadow-sm mb-4 py-3" role="alert" style="border-left: 5px solid #007bff;">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            
            <div class="col-12 col-md-8">
                <h5 class="alert-heading mb-1 text-primary">
                    <i class="fas fa-search"></i> ' . $total_registros . " " . ($msgstr["front_registros_encontrados"] ?? "registros encontrados") . '
                </h5>
                
                <p class="text-muted mb-1" style="font-size: 0.9em;">
                    ' . ($msgstr["front_termo_pesquisado"] ?? "Termo pesquisado") . ': 
                    <code class="text-dark bg-light px-2 py-1 border rounded" style="cursor: copy; text-transform: uppercase; transition: all 0.2s ease;" data-termo="' . $termo_escapado . '" onclick="copiarTermoExperto(this)" title="Clique para copiar a expressão">
                        ' . $termo_pesquisado_limpo . ' <i class="far fa-copy text-secondary ms-1"></i>
                    </code>
                </p>
                
                ' . $detalhes_html . '
            </div>

            <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2">
            <button class="btn btn-primary btn-sm" type="button" onclick="toggleSearchPanels()">
                <i class="fas fa-sliders-h"></i> ' . ($msgstr["front_nova_pesquisa"] ?? "Detalhes da pesquisa") . '
            </button>
                <!--<a href="' . MontarUrlOpac($link_nova_pesquisa) . '" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> ' . ($msgstr["front_nova_pesquisa"] ?? "Nova Pesquisa") . '
                </a>-->
            </div>

        </div>
    </div>
    
    <script>
    function copiarTermoExperto(el) {
        var termo = el.getAttribute("data-termo");
        navigator.clipboard.writeText(termo).then(function() {
            var icone = el.querySelector("i");
            if (icone) {
                // Feedback visual de sucesso
                icone.classList.remove("fa-copy", "far", "text-secondary");
                icone.classList.add("fa-check", "fas", "text-success");
                el.classList.replace("bg-light", "bg-white");
                
                // Restaura o ícone original após 2 segundos
                setTimeout(function() {
                    icone.classList.remove("fa-check", "fas", "text-success");
                    icone.classList.add("fa-copy", "far", "text-secondary");
                    el.classList.replace("bg-white", "bg-light");
                }, 2000);
            }
        }).catch(function(err) {
            console.error("Erro ao copiar expressão: ", err);
        });
    }
    </script>
    ';

    return $html;
}
