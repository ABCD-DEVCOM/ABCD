<?php

/**
 * -------------------------------------------------------------------------
 * Helper: Construtor de Expressão de Busca 
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */


// --- CENTRAL SEARCH FUNCTION (WITH RELEVANCE CORRECTIONS) ---
function searchAndOrganizeResults($bd_list, $db_path, $Expresion, $termo_livre, $Expr_facetas)
{
    global $actparfolder, $lang, $xWxis, $meta_encoding, $opac_gdef;
    $todos_os_registros = [];

    // --- READ OPAC.DEF PARAMETERS (With Safe Fallback) ---
    $gate_enabled = isset($opac_gdef['RELEVANCE_GATE_ENABLED']) ? strtoupper(trim($opac_gdef['RELEVANCE_GATE_ENABLED'])) : 'Y';
    $max_scored = isset($opac_gdef['RELEVANCE_MAX_SCORED']) ? (int)$opac_gdef['RELEVANCE_MAX_SCORED'] : 3500;
    $cache_enabled = isset($opac_gdef['RELEVANCE_CACHE_ENABLED']) ? strtoupper(trim($opac_gdef['RELEVANCE_CACHE_ENABLED'])) : 'Y';
    $cache_ttl = isset($opac_gdef['RELEVANCE_CACHE_TTL']) ? (int)$opac_gdef['RELEVANCE_CACHE_TTL'] : 300;

    // --- CACHE KEY ---
    $sort_key = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "relevance";
    $alcance = isset($_REQUEST["alcance"]) ? $_REQUEST["alcance"] : "and";
    $cache_hash = md5(json_encode(array_keys($bd_list)) . $Expresion . $termo_livre . $Expr_facetas . $sort_key . $alcance . $lang);
    $cache_key = 'busca_relevancia_' . $cache_hash;

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

    $busqueda = montarExpressaoBusca($Expresion, $Expr_facetas);

    // STEP 1: GET THE TOTAL
    $total_base = [];
    $busqueda_decode_arr = [];
    foreach ($bd_list as $base => $value) {
        $cipar = $base;
        $dr_path = $db_path . $base . "/dr_path.def";
        $def_db = file_exists($dr_path) ? parse_ini_file($dr_path) : [];
        $cset_db = (!isset($def_db['UNICODE']) || $def_db['UNICODE'] != "1") ? "ANSI" : "UTF-8";
        $cset = strtoupper($meta_encoding);

        $busqueda_decode = $busqueda;
        if ($cset == "UTF-8" && $cset_db == "ANSI") {
            if (mb_check_encoding($busqueda_decode, 'UTF-8')) {
                $busqueda_decode = mb_encode_numericentity($busqueda_decode, [0x80, 0xffff, 0, 0xffff], 'UTF-8');
            }
        }

        $busqueda_decode_arr[$base] = $busqueda_decode;

        $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $cipar . ".par&Expresion=" . urlencode($busqueda_decode) . "&from=1&count=1&Opcion=buscar&lang=" . $lang;
        $resultado = wxisLlamar($base, $query, $xWxis . "opac/buscar.xis");

        if (is_array($resultado)) {
            foreach ($resultado as $value_res) {
                if (substr(trim($value_res), 0, 8) == '[TOTAL:]') {
                    $total = trim(substr($value_res, 8));
                    if ($total > 0) $total_base[$base] = $total;
                }
            }
        }
    }
    if (empty($total_base)) return [];

    $total_geral = array_sum($total_base);

    // EXPORT THE ACTUAL TOTALS TO THE INTERFACE
    $GLOBALS['REAL_TOTAL_GERAL'] = $total_geral;
    $GLOBALS['REAL_TOTAL_BASE']  = $total_base;

    // --- VOLUME CAP ---
    if ($gate_enabled === 'Y' && $total_geral > $max_scored) {
        $GLOBALS['RELEVANCE_GATE_TRIGGERED'] = $total_geral; // Sinalizador para interface
        return searchDirect($bd_list, $db_path, $Expresion, $termo_livre, $Expr_facetas);
    }

    // STEP 2: SEARCH FOR CONTENT AND CALCULATE RELEVANCE
    foreach ($total_base as $base => $total) {
        $pft_relevancia = buildRelevancePft($base, $db_path);
        $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $base . ".par&Expresion=" . urlencode($busqueda_decode_arr[$base]) . "&desde=1&count=" . $total . "&Formato=" . urlencode($pft_relevancia) . "&lang=" . $lang;
        $resultado_completo = wxisLlamar($base, $query, $xWxis . "opac/buscar.xis");

        if (is_array($resultado_completo)) {
            $conteudo_junto = implode("", $resultado_completo);
            $registros_separados = explode("##RECORD_SEPARATOR##", $conteudo_junto);

            if (!function_exists('clean_for_scoring')) {
                function clean_for_scoring($text)
                {
                    $text = mb_strtolower($text, 'UTF-8');
                    if (function_exists('removeacentos')) $text = removeacentos($text);

                    $text = preg_replace('/[[:punct:]]/u', ' ', $text);
                    $text = preg_replace('/\s+/', ' ', $text);
                    return trim($text);
                }
            }

            foreach ($registros_separados as $registro_str) {
                if (trim($registro_str) === "") continue;
                preg_match('/<mfn>(\d+)<\/mfn>/', $registro_str, $mfn_arr);
                preg_match('/<f_title>(.*?)<\/f_title>/s', $registro_str, $titulo_arr);
                preg_match('/<f_author>(.*?)<\/f_author>/s', $registro_str, $autor_arr);
                preg_match('/<f_subject>(.*?)<\/f_subject>/s', $registro_str, $assunto_arr);
                preg_match('/<f_general>(.*?)<\/f_general>/s', $registro_str, $geral_arr);

                if (isset($mfn_arr[1])) {
                    $mfn = $mfn_arr[1];
                    $pontuacao = 0;

                    $titulo_texto = clean_for_scoring(isset($titulo_arr[1]) ? $titulo_arr[1] : '');
                    $autor_texto = clean_for_scoring(isset($autor_arr[1]) ? $autor_arr[1] : '');
                    $assunto_texto = clean_for_scoring(isset($assunto_arr[1]) ? $assunto_arr[1] : '');
                    $geral_texto = clean_for_scoring(isset($geral_arr[1]) ? $geral_arr[1] : '');
                    $texto_completo = $titulo_texto . ' ' . $autor_texto . ' ' . $assunto_texto . ' ' . $geral_texto;

                    $frase_exata = clean_for_scoring(trim($termo_livre));
                    $termos_separados = explode(' ', $frase_exata);

                    $term_count = 0;
                    foreach ($termos_separados as $termo) {
                        if (!empty($termo)) {
                            $term_count += mb_substr_count($texto_completo, $termo, 'UTF-8');
                        }
                    }
                    $pontuacao += $term_count * 10;

                    if (!empty($frase_exata)) {
                        if (str_contains($titulo_texto, $frase_exata)) $pontuacao += 100;
                        if (str_contains($autor_texto, $frase_exata)) $pontuacao += 90;
                        if (str_contains($assunto_texto, $frase_exata)) $pontuacao += 80;
                        if (str_contains($geral_texto, $frase_exata)) $pontuacao += 25;
                    }

                    if (count($termos_separados) > 1) {
                        $todos_no_titulo = true;
                        $todos_no_autor = true;
                        $todos_no_assunto = true;
                        foreach ($termos_separados as $termo) {
                            if (!empty($termo)) {
                                if (!str_contains($titulo_texto, $termo)) $todos_no_titulo = false;
                                if (!str_contains($autor_texto, $termo)) $todos_no_autor = false;
                                if (!str_contains($assunto_texto, $termo)) $todos_no_assunto = false;
                            }
                        }
                        if ($todos_no_titulo) $pontuacao += 50;
                        if ($todos_no_autor) $pontuacao += 45;
                        if ($todos_no_assunto) $pontuacao += 40;
                    }
                    // If $termo_livre (source of score) was empty,
                    // this means it was not a 'libre' search for scoring (e.g. it was a 'direct' search).
                    // In such cases, the score is 0, but the record MUST be included.
                    // If the local score was 0 (e.g. accent conflict),
                    // we set it to 1 so that the record is NOT discarded.
                    if ($pontuacao == 0) {
                        $pontuacao = 1;
                    }

                    // Agora qualquer registro vindo do WXIS entrará aqui
                    $todos_os_registros[] = [
                        'mfn' => $mfn,
                        'base' => $base,
                        'pontuacao' => $pontuacao,
                        'sort_title' => $titulo_texto,
                        'sort_author' => $autor_texto,
                        'sort_subject' => $assunto_texto
                    ];
                }
            }
        }
    }

    // Retrieve the "sort" parameter from the URL, with "relevance" as the default
    $sort_key = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "relevance";

    $sort_field = 'pontuacao'; // Padrão é relevância
    $sort_direction = SORT_DESC;  // Relevância é do Maior para o Menor

    switch ($sort_key) {
        case 'title_asc':
            $sort_field = 'sort_title';
            $sort_direction = SORT_ASC; // Title is A-Z
            break;
        case 'title_desc':
            $sort_field = 'sort_title';
            $sort_direction = SORT_DESC; // Title is Z-A
            break;
        case 'author_asc':
            $sort_field = 'sort_author';
            $sort_direction = SORT_ASC; // Author is A-Z
            break;
        case 'author_desc':
            $sort_field = 'sort_author';
            $sort_direction = SORT_DESC; // Author is Z-A
            break;
        case 'mfn_asc': // Oldest (lowest MFN first)
            $sort_field = 'mfn';
            $sort_direction = SORT_ASC;
            $sort_flags = SORT_NUMERIC; // Numerical ordering
            break;
        case 'mfn_desc': // Most Favoured Nation (MFN highest first)
            $sort_field = 'mfn';
            $sort_direction = SORT_DESC;
            $sort_flags = SORT_NUMERIC;
            break;
        case 'relevance':
        default:
            // It is already set as default (scoring, DESC).
            break;
    }

    // Extract the column we want to sort into array_multisort
    $sort_array = [];
    foreach ($todos_os_registros as $key => $row) {
        // Use 'strtolower' to ensure correct alphabetical ordering
        $sort_array[$key] = strtolower($row[$sort_field]);
    }

    // Sort the main array ($all_records) using the extracted column.
    array_multisort($sort_array, $sort_direction, $todos_os_registros);

    if ($cache_enabled === 'Y') {
        opac_cache_set($cache_key, serialize($todos_os_registros), $cache_ttl);
    }

    return $todos_os_registros;
} 