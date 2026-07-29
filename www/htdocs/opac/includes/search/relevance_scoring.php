<?php

/**
 * -------------------------------------------------------------------------
 * Helper:  Relevance Scoring
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */

function buildRelevancePft($base, $db_path)
{
    $caminho_def = $db_path . $base . "/opac/relevance.def";
    $campos_titulo = "245^a,245^b,10,20";
    $campos_autor = "100^a, 700^a, 110^a, 111^a, 2";
    $campos_assunto = "650,653,30,40";
    $campos_geral = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,20,22,30,35,40,50,60,70,80,90,100,200,300,400,500,600,700,800,900";
    if (file_exists($caminho_def)) {
        $config = parse_ini_file($caminho_def, true);
        if (isset($config['title']['fields'])) $campos_titulo = $config['title']['fields'];
        if (isset($config['author']['fields'])) $campos_autor = $config['author']['fields'];
        if (isset($config['subject']['fields'])) $campos_assunto = $config['subject']['fields'];
        if (isset($config['general']['fields']) && strtoupper($config['general']['fields']) != 'ALL') $campos_geral = $config['general']['fields'];
    }
    return "'<mfn>',mfn,'</mfn>', '<f_title>',(" . prefixFieldsWithV($campos_titulo) . "),'</f_title>', '<f_author>',(" . prefixFieldsWithV($campos_autor) . "),'</f_author>', '<f_subject>',(" . prefixFieldsWithV($campos_assunto) . "),'</f_subject>', '<f_general>',(" . prefixFieldsWithV($campos_geral) . "),'</f_general>', '##RECORD_SEPARATOR##'";
}