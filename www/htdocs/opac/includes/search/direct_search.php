<?php

/**
 * -------------------------------------------------------------------------
 * Helper: Used when truncation ($) occurs or when relevance is not required.
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */



function searchDirect($bd_list, $db_path, $Expresion, $termo_livre, $Expr_facetas)
{
    global $actparfolder, $lang, $xWxis, $meta_encoding, $opac_gdef;
    $todos_os_registros = [];

    // --- READ OPAC.DEF PARAMETERS ---
    $gate_enabled = isset($opac_gdef['RELEVANCE_GATE_ENABLED']) ? strtoupper(trim($opac_gdef['RELEVANCE_GATE_ENABLED'])) : 'Y';
    $max_scored = isset($opac_gdef['RELEVANCE_MAX_SCORED']) ? (int)$opac_gdef['RELEVANCE_MAX_SCORED'] : 3500;
    $cache_enabled = isset($opac_gdef['RELEVANCE_CACHE_ENABLED']) ? strtoupper(trim($opac_gdef['RELEVANCE_CACHE_ENABLED'])) : 'Y';
    $cache_ttl = isset($opac_gdef['RELEVANCE_CACHE_TTL']) ? (int)$opac_gdef['RELEVANCE_CACHE_TTL'] : 300;

    // --- CACHE KEY ---
    $sort_key = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "mfn_desc";
    $alcance = isset($_REQUEST["alcance"]) ? $_REQUEST["alcance"] : "and";
    $cache_hash = md5(json_encode(array_keys($bd_list)) . $Expresion . $termo_livre . $Expr_facetas . $sort_key . $alcance . $lang);
    $cache_key = 'busca_direta_' . $cache_hash;

    if ($cache_enabled === 'Y') {
        $cached_result = opac_cache_get($cache_key);
        if ($cached_result !== false) {
            $cached_data = unserialize($cached_result);
            if (is_array($cached_data) && isset($cached_data['registros'])) {
                $GLOBALS['REAL_TOTAL_GERAL'] = $cached_data['total_geral'];
                $GLOBALS['REAL_TOTAL_BASE']  = $cached_data['total_base'];
                return $cached_data['registros'];
            }
        }
    }

    // Checks whether the user has entered $ (truncation)
    // If so, we'll force this character to be added to the WXIS URL,
    // as the 'construir_expressao()' or 'urlencode()' function may be removing it.
    $tem_truncagem = (strpos($termo_livre, '$') !== false);
    $sufixo_truncagem = $tem_truncagem ? '$' : '';

    // Uso do Helper 1
    $coleccion_str = isset($_REQUEST["coleccion"]) ? $_REQUEST["coleccion"] : "";
    $busqueda = build_search_expression($Expresion, $coleccion_str, $Expr_facetas);

    // Uso do Helper 2
    $totals_data = get_wxis_totals($bd_list, $db_path, $busqueda, $sufixo_truncagem);
    $total_base = $totals_data['total_base'];
    $busqueda_decode_arr = $totals_data['busqueda_decode_arr'];

    if (empty($total_base)) return [];
    $total_geral = array_sum($total_base);

    // EXPORTA OS TOTAIS REAIS PARA A INTERFACE
    $GLOBALS['REAL_TOTAL_GERAL'] = $total_geral;
    $GLOBALS['REAL_TOTAL_BASE']  = $total_base;

    // 3. Passo 2: Buscar conteúdo
    foreach ($total_base as $base => $total) {
        $pft_relevancia = buildRelevancePft($base, $db_path);

        $fetch_count = $total;
        if ($gate_enabled === 'Y' && $total > $max_scored) {
            $fetch_count = $max_scored; // Corta o carregamento de milhares de PFTs
            if (!isset($GLOBALS['RELEVANCE_GATE_TRIGGERED'])) {
                $GLOBALS['RELEVANCE_GATE_TRIGGERED'] = $total_geral;
            }
        }

        // Modificando o count= para carregar apenas até o limite (ou total, se menor)
        $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $base . ".par&Expresion=" . urlencode($busqueda_decode_arr[$base]) . $sufixo_truncagem . "&from=1&count=" . $fetch_count . "&Formato=" . urlencode($pft_relevancia) . "&lang=" . $lang;

        $resultado_completo = wxisLlamar($base, $query, $xWxis . "opac/buscar.xis");

        if (is_array($resultado_completo)) {
            $conteudo_junto = implode("", $resultado_completo);
            $registros_separados = explode("##RECORD_SEPARATOR##", $conteudo_junto);

            foreach ($registros_separados as $registro_str) {
                if (trim($registro_str) === "") continue;

                preg_match('/<mfn>(\d+)<\/mfn>/', $registro_str, $mfn_arr);
                preg_match('/<f_title>(.*?)<\/f_title>/s', $registro_str, $titulo_arr);
                preg_match('/<f_author>(.*?)<\/f_author>/s', $registro_str, $autor_arr);
                preg_match('/<f_subject>(.*?)<\/f_subject>/s', $registro_str, $assunto_arr);

                if (isset($mfn_arr[1])) {
                    $todos_os_registros[] = [
                        'mfn' => $mfn_arr[1],
                        'base' => $base,
                        'pontuacao' => 100, // Pontuação fixa para busca direta
                        'sort_title' => isset($titulo_arr[1]) ? strip_tags($titulo_arr[1]) : '',
                        'sort_author' => isset($autor_arr[1]) ? strip_tags($autor_arr[1]) : '',
                        'sort_subject' => isset($assunto_arr[1]) ? strip_tags($assunto_arr[1]) : ''
                    ];
                }
            }
        }
    }

    // 4. Ordenação Padrão
    $sort_key = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "mfn_desc";
    $sort_field = 'mfn';
    $sort_direction = SORT_DESC;
    $sort_flags = SORT_REGULAR;

    switch ($sort_key) {
        case 'title_asc':
            $sort_field = 'sort_title';
            $sort_direction = SORT_ASC;
            $sort_flags = SORT_STRING;
            break;
        case 'title_desc':
            $sort_field = 'sort_title';
            $sort_direction = SORT_DESC;
            $sort_flags = SORT_STRING;
            break;
        case 'author_asc':
            $sort_field = 'sort_author';
            $sort_direction = SORT_ASC;
            $sort_flags = SORT_STRING;
            break;
        case 'author_desc':
            $sort_field = 'sort_author';
            $sort_direction = SORT_DESC;
            $sort_flags = SORT_STRING;
            break;
        case 'mfn_asc':
            $sort_field = 'mfn';
            $sort_direction = SORT_ASC;
            $sort_flags = SORT_NUMERIC;
            break;
        case 'mfn_desc':
            $sort_field = 'mfn';
            $sort_direction = SORT_DESC;
            $sort_flags = SORT_NUMERIC;
            break;
        case 'relevance':
        default:
            $sort_field = 'mfn';
            $sort_direction = SORT_DESC;
            $sort_flags = SORT_NUMERIC;
            break;
    }

    $sort_array = [];
    foreach ($todos_os_registros as $key => $row) {
        $val = $row[$sort_field];
        if ($sort_flags == SORT_STRING) $val = strtolower($val);
        $sort_array[$key] = $val;
    }

    array_multisort($sort_array, $sort_direction, $sort_flags, $todos_os_registros);

    if ($cache_enabled === 'Y') {
        opac_cache_set($cache_key, serialize([
            'registros'   => $todos_os_registros,
            'total_geral' => $total_geral,
            'total_base'  => $total_base,
        ]), $cache_ttl);
    }

    return $todos_os_registros;
}