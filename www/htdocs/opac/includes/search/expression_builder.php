<?php

/**
 * -------------------------------------------------------------------------
 * Helper: Search Expression Builder
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * Description: Build the final expression by combining the base expression, the collection and the active facets.
 * A single source is used by buscar_integrada.php and facets.php to ensure consistency.
 * -------------------------------------------------------------------------
 */
function montarExpressaoBusca($Expresion, $Expr_facetas = '')
{
    $busqueda = $Expresion;

    if (isset($_REQUEST["coleccion"]) && $_REQUEST["coleccion"] != "") {
        $coleccion = explode('|', urldecode($_REQUEST["coleccion"]));
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
            $busqueda = ($busqueda == "" || $busqueda == '$')
                ? $exFacetas
                : "(" . $busqueda . ") and (" . $exFacetas . ")";
        }
    }

    return empty($busqueda) ? '$' : $busqueda;
}
