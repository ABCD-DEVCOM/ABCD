<?php
/**
 * -------------------------------------------------------------------------
 * Helper: Construtor de Expressão de Busca
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */

function build_search_expression($Expresion, $coleccion_str, $Expr_facetas) {
    $busqueda = $Expresion;
    if (isset($coleccion_str) and $coleccion_str != "") {
        $coleccion = explode('|', urldecode($coleccion_str));
        $col0 = isset($coleccion[0]) ? $coleccion[0] : '';
        $col2 = isset($coleccion[2]) ? $coleccion[2] : '';

        if ($Expresion != "" and $Expresion != '$' and $Expresion != $col2 . $col0) {
            $busqueda = "(" . $Expresion . ") and (" . $col2 . $col0 . ")";
        } else {
            $busqueda = $col2 . $col0;
        }
    }
    if (!empty($Expr_facetas)) {
        $f = explode('|', $Expr_facetas);
        if (isset($f[1]) && !empty(trim($f[1]))) {
            $exFacetas = trim($f[1]);
            if ($busqueda == "" || $busqueda == '$') $busqueda = $exFacetas;
            else $busqueda = "(" . $busqueda . ") and (" . $exFacetas . ")";
        }
    }
    if (empty($busqueda)) $busqueda = '$';
    
    return $busqueda;
}