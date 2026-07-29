<?php

/**
 * -------------------------------------------------------------------------
 * Helper: Construtor de Expressão de Busca
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */


// =========================================================
// START OF THE NEW BLOCK 'YOU MEANT TO SAY'
// =========================================================
if ($total_registros == 0 && ($Expresion != '$' || !empty($Expr_facetas))) {
    $termo_pesquisado_original = isset($_REQUEST["Sub_Expresion"]) ? trim(urldecode($_REQUEST["Sub_Expresion"])) : '';
    $sugestao_frase = "";

    if (!empty($termo_pesquisado_original)) {
        // Check for truncation in the original search
        $tem_truncagem = (strpos($termo_pesquisado_original, '$') !== false);
        $termo_limpo_para_dic = removeacentos(mb_strtolower(str_replace('$', '', $termo_pesquisado_original), 'UTF-8'));

        $dicionario_unificado = [];
        if (isset($bd_list) && is_array($bd_list)) {
            foreach ($bd_list as $nome_base => $info_base) {
                // ... (Lógica de codificação mantida) ...
                $dr_path = $db_path . $nome_base . "/dr_path.def";
                $def_db = file_exists($dr_path) ? parse_ini_file($dr_path) : [];
                $cset_db = (!isset($def_db['UNICODE']) || $def_db['UNICODE'] != "1") ? "ANSI" : "UTF-8";

                $caminho_dicionario = $db_path . $nome_base . "/opac/$nome_base.dic";
                if (is_readable($caminho_dicionario)) {
                    $linhas_dicionario = file($caminho_dicionario, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($linhas_dicionario as $linha) {
                        if ($cset_db == "ANSI") $linha = mb_convert_encoding($linha, "UTF-8", "ISO-8859-1");
                        if (strpos($linha, '_') === false) continue;
                        list($prefixo, $termo_valido) = explode('_', $linha, 2);

                        // Optimisation: Only add to the array if it is relevant, to save memory
                        $termo_valido_norm = removeacentos(mb_strtolower($termo_valido, 'UTF-8'));

                        // If the text is truncated, we are only interested in what begins with the term
                        if ($tem_truncagem) {
                            if (strpos($termo_valido_norm, $termo_limpo_para_dic) === 0) {
                                $dicionario_unificado[] = ['termo' => $termo_valido];
                            }
                        } else {
                            // If there’s no truncation, use the full text for the Levenshtein distance
                            $dicionario_unificado[] = ['termo' => $termo_valido];
                        }
                    }
                }
            }
        }

        if (!empty($dicionario_unificado)) {

            // CENÁRIO 1: TRUNCAGEM ($) - Sua sugestão
            if ($tem_truncagem) {
                // Pegamos os primeiros 5 termos que deram match no dicionário
                $sugestoes_encontradas = [];
                $count = 0;
                foreach ($dicionario_unificado as $ent) {
                    if ($count >= 5) break;
                    // Evita duplicatas na sugestão
                    if (!in_array($ent['termo'], $sugestoes_encontradas)) {
                        $sugestoes_encontradas[] = $ent['termo'];
                        $count++;
                    }
                }

                if (!empty($sugestoes_encontradas)) {
                    // Exibimos como links separados
                    echo '<div class="alert alert-warning" role="alert"><h5 class="alert-heading">' . $msgstr["front_no_rf"] . " para '" . htmlspecialchars($termo_pesquisado_original) . "'</h5><hr>";
                    echo "<p class=\"mb-0\">" . ($msgstr["did_you_mean"] ?? "Você quis dizer:") . " ";

                    foreach ($sugestoes_encontradas as $sug) {
                        $parametros_link = $_GET;
                        unset($parametros_link['Sub_Expresion'], $parametros_link['Expresion'], $parametros_link['prefijo'], $parametros_link['Opcion'], $parametros_link['facetas']);
                        $parametros_link['Sub_Expresion'] = $sug;
                        $parametros_link['Opcion'] = 'libre';
                        $link = "?" . http_build_query($parametros_link);
                        echo "<a href='" . $link . "' class='me-3'><strong>" . htmlspecialchars($sug) . "</strong></a>";
                    }
                    echo "</p></div>";
                    // Marcamos que já exibimos sugestão para não cair no bloco de baixo
                    $sugestao_frase = "EXIBIDA";
                }
            }
            // CENÁRIO 2: LEVENSHTEIN (Sem $) - Lógica original melhorada
            else {

                $entrada_normalizada = removeacentos(mb_strtolower($termo_pesquisado_original, 'UTF-8'));
                $qualquer_mudanca = false;

                // --- ETAPA 1: TENTAR ACHAR A FRASE INTEIRA ---
                $melhor_frase_match = null;
                $distancia_min_frase = 1000;
                $len_entrada = mb_strlen($entrada_normalizada, 'UTF-8');
                $limite_dist_frase = max(1, min(5, floor($len_entrada / 3))); // Limite de 33%, máx 5

                foreach ($dicionario_unificado as $ent) {
                    // Dicionário agora está 100% UTF-8, podemos comparar
                    $termo_dic_norm = removeacentos(mb_strtolower($ent['termo'], 'UTF-8'));
                    $distancia_frase = levenshtein($entrada_normalizada, $termo_dic_norm);

                    if ($distancia_frase > 0 && $distancia_frase <= $limite_dist_frase) {
                        if ($distancia_frase < $distancia_min_frase) {
                            $distancia_min_frase = $distancia_frase;
                            $melhor_frase_match = $ent['termo'];
                        }
                    }
                }

                if ($melhor_frase_match !== null) {
                    // ETAPA 1 SUCESSO
                    $sugestao_frase = $melhor_frase_match;
                    $qualquer_mudanca = true;
                } else {
                    // ETAPA 2 FALHA: Nenhuma frase encontrada. Tenta palavra por palavra
                    $tokens_originais = preg_split('/\s+/', $termo_pesquisado_original, -1, PREG_SPLIT_NO_EMPTY);
                    $tokens_norm = preg_split('/\s+/', $entrada_normalizada, -1, PREG_SPLIT_NO_EMPTY);
                    $tokens_sugeridos = [];

                    foreach ($tokens_norm as $i => $token) {
                        $melhor_token_match = null;
                        $distancia_min_token = 1000;
                        $len_token = mb_strlen($token, 'UTF-8');

                        // CORREÇÃO: Previne o erro "Undefined array key" caso a limpeza de caracteres 
                        // tenha gerado uma contagem desigual entre a string limpa e a original
                        $token_orig = isset($tokens_originais[$i]) ? $tokens_originais[$i] : $token;

                        if ($len_token <= 2) { // Não tenta corrigir "do", "de", etc.
                            $tokens_sugeridos[] = $token_orig;
                            continue;
                        }

                        $limite_dist_token = max(1, floor($len_token / 3));

                        foreach ($dicionario_unificado as $ent) {
                            $termo_dic_norm = removeacentos(mb_strtolower($ent['termo'], 'UTF-8'));

                            // Compara o token com termos do dicionário
                            $distancia_token = levenshtein($token, $termo_dic_norm);

                            if ($distancia_token > 0 && $distancia_token <= $limite_dist_token) {
                                if ($distancia_token < $distancia_min_token) {
                                    $distancia_min_token = $distancia_token;
                                    $melhor_token_match = $ent['termo'];
                                }
                            }
                        }

                        if ($melhor_token_match !== null) {
                            $tokens_sugeridos[] = $melhor_token_match;
                            if (mb_strtolower($melhor_token_match, 'UTF-8') !== mb_strtolower($token_orig, 'UTF-8')) {
                                $qualquer_mudanca = true;
                            }
                        } else {
                            $tokens_sugeridos[] = $token_orig;
                        }
                    }

                    if ($qualquer_mudanca) {
                        $sugestao_frase = implode(' ', $tokens_sugeridos);
                    }
                }
                // Bloco de exibição (agora só executa se uma sugestão válida foi gerada)
                if ($qualquer_mudanca && !empty($sugestao_frase)) {
                    $parametros_link = $_GET;
                    unset($parametros_link['Sub_Expresion'], $parametros_link['Expresion'], $parametros_link['prefijo'], $parametros_link['Opcion'], $parametros_link['facetas']);
                    $parametros_link['Sub_Expresion'] = $sugestao_frase;
                    $parametros_link['prefijo'] = 'TW_';
                    $parametros_link['Opcion'] = 'libre';
                    $link = "?" . http_build_query($parametros_link);
                    $mensagem = $msgstr["you_expression"];
                    echo '<div class="alert alert-warning" role="alert"><h5 class="alert-heading">' . $msgstr["front_no_rf"] . " para '" . htmlspecialchars($termo_pesquisado_original) . "'</h5><hr><p class=\"mb-0\">" . $mensagem . " <a href='" . $link . "'><strong>" . htmlspecialchars($sugestao_frase) . "</strong></a>?</p></div>";
                }
            }
        }
    }

    // Mensagem de "Nenhum resultado" (se nenhuma sugestão foi feita)
    if (empty($sugestao_frase)) {

        // Pega o termo de busca original, se for uma busca livre
        $termo_original = isset($_REQUEST["Sub_Expresion"]) ? trim(urldecode($_REQUEST["Sub_Expresion"])) : '';

        // Verifica se a busca estava restrita por uma coleção ou faceta
        $tem_filtro_ativo = (isset($expr_coleccion) || !empty($Expr_facetas));

        echo '<div class="alert alert-info mt-4" role="alert">';
        echo '<h5 class="alert-heading">' . $msgstr["front_no_rf"] . '</h5>'; // "Nenhum resultado encontrado"

        // Informa ao usuário *porque* pode não ter achado (se estava filtrado)
        if (isset($expr_coleccion)) {
            echo "<p class='mb-0'>" . $msgstr["en"] . " <strong>" . $expr_coleccion . "</strong></p>";
        } elseif (!empty($Expr_facetas)) {
            echo "<p class='mb-0'>" . (isset($msgstr["front_filters_active"]) ? $msgstr["front_filters_active"] : "Your search is restricted by filters.") . "</p>";
        }

        echo "<hr>";
        echo "<p class='mb-3'>" . $msgstr["front_p_refine"] . "</p>"; // "Tente refinar sua busca"
        echo "<div>";

        // Oportunidade 1: Pesquisar em todo o catálogo (Botão secundário)
        if ($tem_filtro_ativo && !empty($termo_original)) {

            $parametros_link_all = $_GET;

            // Remove os filtros e parâmetros de expressão que os recriam
            unset($parametros_link_all['facetas'], $parametros_link_all['coleccion'], $parametros_link_all['Expresion'], $parametros_link_all['Opcion'], $parametros_link_all['alcance']);

            // Recria a busca livre original
            $parametros_link_all['Sub_Expresion'] = $termo_original;
            $parametros_link_all['Opcion'] = 'libre';

            $link_sem_filtros = "?" . http_build_query($parametros_link_all);

            // (Recomendo msgstr 'front_search_all_catalog_btn' => 'Buscar "%s" em todo o catálogo')
            $msg_search_all = isset($msgstr["front_search_all_catalog_btn"]) ? $msgstr["front_search_all_catalog_btn"] : 'Buscar "%s" em todo o catálogo';

            // Botão sutil (btn-light) com um ícone de "expandir busca"
            echo "<a href='" . htmlspecialchars($link_sem_filtros) . "' class='btn btn-light border me-2 mb-2'>";
            echo '<i class="fas fa-search-plus"></i> ' . sprintf($msg_search_all, htmlspecialchars($termo_original));
            echo "</a>";
        }

        // Oportunidade 2: Botão "Nova Busca" (Botão principal, com a lupinha)
        $msg_new_search = isset($msgstr["front_search_new"]) ? $msgstr["front_search_new"] : 'Nova busca';

        // Botão principal (btn-primary) com a lupinha que você mencionou
        echo "<a href='index.php' class='btn btn-primary mb-2'>";
        echo '<i class="fas fa-search"></i> ' . $msg_new_search;
        echo "</a>";

        echo "</div>";
        echo '</div>';
    }
}
// =========================================================
// END OF THE NEW BLOCK 'YOU MEANT TO SAY'
// =========================================================
