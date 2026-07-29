<?php
/**
 * -------------------------------------------------------------------------
 * Helper: Contagem de Totais WXIS (Etapa 1)
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */

function get_wxis_totals($bd_list, $db_path, $busqueda, $sufixo_truncagem = '') {
    global $actparfolder, $lang, $xWxis, $meta_encoding;
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

        $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $cipar . ".par&Expresion=" . urlencode($busqueda_decode) . $sufixo_truncagem . "&from=1&count=1&Opcion=buscar&lang=" . $lang;
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
    return [
        'total_base' => $total_base,
        'busqueda_decode_arr' => $busqueda_decode_arr
    ];
}