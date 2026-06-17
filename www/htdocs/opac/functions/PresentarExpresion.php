<?php
/*
20220307 rogercgui fixed index in line $camposbusqueda[$l[1]]=$l[0]; 
*/

// =============================================================
// Função: PresentarExpresion($base)
// -------------------------------------------------------------
// Objetivo: Montar e exibir de forma legível a expressão de busca,
// removendo prefixos técnicos (como TI_, AU_, TW_) e símbolos internos.
// É usada no arquivo buscar_integrada.php, principalmente para facetas.
// =============================================================
function PresentarExpresion($base)
{
    // Variáveis globais do sistema ABCD
    global $yaidentificado, $db_path, $msgstr, $actparfolder;

    // ---------------------------------------------------------
    // Se existe uma Sub_Expresion (busca composta, com facetas)
    // ---------------------------------------------------------
    if (isset($_REQUEST["Sub_Expresion"])) {

        // ======================================================
        // Determina qual arquivo de campos avançados usar
        // (exemplo: /bases/opac_conf/pt/meubase_avanzada.tab)
        // ======================================================
        if (isset($_REQUEST["coleccion"]) and $_REQUEST["coleccion"] != "") {
            $col = explode('|', $_REQUEST["coleccion"]);
            $archivo = $db_path . "opac_conf/" . $_REQUEST["lang"] . "/" . $base . "_avanzada_" . $col[0] . ".tab";
            // Se o arquivo específico da coleção não existir, usa o padrão
            if (!file_exists($archivo))
                $archivo = $db_path . "opac_conf/" . $_REQUEST["lang"] . "/$base" . "_avanzada.tab";
        } else {
            // Caso não haja coleção, usa o arquivo genérico
            if ($base != "")
                $archivo = $db_path . "opac_conf/" . $_REQUEST["lang"] . "/$base" . "_avanzada.tab";
            else
                $archivo = $db_path . "opac_conf/" . $_REQUEST["lang"] . "/avanzada.tab";
        }

        // ======================================================
        // Lê o arquivo .tab e monta o mapa de prefixos → nomes legíveis
        // Exemplo:
        //   ti_|Título
        //   au_|Autor
        //   tw_|Palavras do texto
        // vira:
        //   $camposbusqueda["ti_"] = "Título";
        // ======================================================
        $camposbusqueda = array();
        if (file_exists($archivo)) {
            $fp = file($archivo);
            foreach ($fp as $linea) {
                if (trim($linea) != "") {
                    $l = explode('|', $linea);
                    $camposbusqueda[$l[1]] = $l[0];
                }
            }
        }

        // ======================================================
        // Dependendo da opção da busca, processa diferente
        // ======================================================
        switch ($_REQUEST["Opcion"]) {

            // --------------------------------------------------
            // Caso 1: "prepararbusqueda"
            // Exibe os termos, campos e operadores (AND/OR)
            // --------------------------------------------------
            case "prepararbusqueda":

                // Divide os parâmetros recebidos
                $expresion = explode(" ~~~ ", urldecode($_REQUEST["Expresion"]));
                $campos = explode(" ~~~ ", $_REQUEST["Campos"]);
                $operadores = explode(" ~~~ ", $_REQUEST["Operadores"]);

                $OPER = "";
                $Exp_b = "";

                // Percorre cada termo e exibe campo + termo + operador
                for ($i = 0; $i < count($expresion); $i++) {
                    if (trim($expresion[$i]) != "") {
                        // Exibe algo como: "Título democracia"
                        echo $camposbusqueda[trim($campos[$i])] . " " . $expresion[$i] . "<br>";

                        // Exibe o operador seguinte (AND, OR, etc.)
                        $OPER = " <font color=darkred><strong>" . strtoupper($operadores[$i]) . "</strong></font><br>";

                        // Monta expressão interna para controle
                        if ($Exp_b == "")
                            $Exp_b = $campos[$i] . trim($expresion[$i]);
                        else
                            $Exp_b .= " " . $operadores[$i - 1] . " " . trim($expresion[$i]);
                    }
                }
                break;

            // --------------------------------------------------
            // Caso 2: Busca livre, detalhada ou avançada
            // Apenas remove prefixos e caracteres técnicos
            // --------------------------------------------------
            case "free":
            case "detalle":
            case "avanzada":
                if (isset($_REQUEST["Expresion"])) $Exp_b = urldecode($_REQUEST["Expresion"]);
                if (isset($_REQUEST["Sub_Expresion"])) $Exp_b = urldecode($_REQUEST["Sub_Expresion"]);
                if (isset($_REQUEST["prefijoindice"])) $Exp_b = str_replace($_REQUEST["prefijoindice"], '', $_REQUEST["Expresion"]);

                // Remove todos os prefixos de campo encontrados
                foreach ($camposbusqueda as $key => $value) {
                    if (isset($_REQUEST["prefijoindice"]))
                        $Exp_b = str_replace($_REQUEST["prefijoindice"], "", $Exp_b);

                    $Exp_b = str_replace(trim($key), "", $Exp_b);
                }

                // Remove o caractere “|” usado como separador
                $Exp_b = str_replace("|", " ", $Exp_b);
                break;

            // --------------------------------------------------
            // Caso 3: Padrão (usado quando não há Sub_Expresion)
            // --------------------------------------------------
            default:
                if (isset($_REQUEST["Expresion"])) {
                    $Exp_b = $_REQUEST["Expresion"];
                } else {
                    $Exp_b = $_REQUEST["Sub_Expresion"];
                }

                // Remove prefixos genéricos (prefijoindice, prefijo)
                if (isset($_REQUEST["prefijoindice"]))
                    $Exp_b = str_replace($_REQUEST["prefijoindice"], '', $_REQUEST["Expresion"]);

                if (isset($_REQUEST["prefijo"]))
                    $Exp_b = str_replace($_REQUEST["prefijo"], '', $Exp_b);

                // Remove os códigos de campo (como ti_, au_)
                foreach ($camposbusqueda as $key => $value) {

                    if (isset($_REQUEST["prefijo_col"]))
                        $Exp_b = str_replace($_REQUEST["prefijo_col"] . trim($key), "", $Exp_b);

                    $Exp_b = str_replace(trim($key), "", $Exp_b);
                }

                // Limpeza final de prefixos
                if (isset($_REQUEST["prefijo"]))
                    $Exp_b = str_replace($_REQUEST["prefijo"], '', $Exp_b);

                break;
        }

        // ---------------------------------------------------------
        // If there is no Sub_Expression, just use a simple Expression
        // ---------------------------------------------------------
    } else {
        // FIX: Prevents an "Undefined array key" error if the parameter does not exist
        $req_expresion = isset($_REQUEST["Expresion"]) ? $_REQUEST["Expresion"] : '';

        if (isset($_REQUEST["prefijo"]))
            $Exp_b = str_replace($_REQUEST["prefijo"], '', $req_expresion);
        else
            $Exp_b = $req_expresion;
    }

    // FIX: Prevents the "Passing null to parameter" error in PHP 8.1+
    if ($Exp_b === null) {
        $Exp_b = '';
    }

    // =========================================================
    // Final cleanup: removes internal markers used by OPAC
    // =========================================================
    $Exp_b = str_replace('$#$C_', '', $Exp_b);
    $Exp_b = str_replace('$#$', '', $Exp_b);
    $Exp_b = str_replace('~', '', $Exp_b);

    // Remove prefixos técnicos restantes, se houver
    if (isset($_REQUEST["prefijoindice"])) $Exp_b = str_replace($_REQUEST["prefijoindice"], '', $Exp_b);
    if (isset($_REQUEST["prefijo"])) $Exp_b = str_replace($_REQUEST["prefijo"], '', $Exp_b);

    // =========================================================
    // Returns the final expression in a readable format for display to the user
    // =========================================================
    return $Exp_b;
}
