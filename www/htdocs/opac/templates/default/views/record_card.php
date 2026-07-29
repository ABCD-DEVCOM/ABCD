<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   record_card.php
 *  Purpose:  Displays individual record cards in the OPAC
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2025-10-22 rogercgui Created
 * -------------------------------------------------------------------------
 */

function ApresentarRegistroIndividual($base, $mfn, $num_registro, $Formato, $Expresion, $pontuacao)
{
    global $db_path, $lang, $xWxis, $actparfolder, $bd_list, $msgstr;

    // 1. Gera a string de botões a partir da classe (ela contém as expressões PFT)
    $AllButtons = "";
    //if (file_exists("classes/ToolButtons.php")) {
    //	include_once("classes/ToolButtons.php");
    $ToolButtons = new ToolButtons([]);
    $AllButtons = $ToolButtons->ShowFromTab($db_path, $base, $lang);
    //}

    // 2. Constrói o caminho completo para o PFT do conteúdo do registro
    $caminho_pft_conteudo = $db_path . $base . "/pfts/" . $lang . "/" . $Formato . ".pft";
    if (!file_exists($caminho_pft_conteudo)) {
        $caminho_pft_conteudo = $db_path . $base . "/pfts/" . $Formato . ".pft";
    }

    // 3. Monta a "Super String" PFT que une o layout, os botões e o conteúdo
    $pft_dinamico = "
        '
        <div class=\"card-header bg-light p-2\">
            <div class=\"d-flex justify-content-between align-items-center\">
                <div class=\"d-flex align-items-center\">
                <div class=\"form-check me-3\">" . $num_registro . "
                        <input class=\"form-check-input\" type=\"checkbox\" name=\"c_=',mstname,'_='f(mfn,1,0)'\" id=\"c_=',mstname,'_='f(mfn,1,0)'\" onclick=\"javascript:Seleccionar(this)\">
                        <label class=\"form-check-label\" for=\"c_=',mstname,'_='f(mfn,1,0)'\">&nbsp;</label>
                    </div>
                    @@@
                </div>
                <div>
                    <div class=\"btn-group d-none d-md-block\">
                    " . $AllButtons . "
                    </div>
                    <div class=\"btn-group d-block d-md-none\">
                        <button type=\"button\" class=\"btn btn-secondary dropdown-toggle btn-sm\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\"><i class=\"fas fa-ellipsis-v\"></i></button>
                        <ul class=\"dropdown-menu dropdown-menu-end\">" . $AllButtons . "</ul>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"card-body\">'
        ,
        @" . $caminho_pft_conteudo . "
        ,
        '
            <div class=\"card-footer bg-transparent\">
                <div style=\"font-size: 0.6em; color: #666;\">
                " . $msgstr["front_base"] . ": " . (isset($bd_list[$base]['titulo']) ? $bd_list[$base]['titulo'] : $base) . ": MFN'mfn' |  pts: " . $pontuacao . "
                </div>
            </div>
        </div>
        <script>
            var cookie = getCookie(\"ABCD\");
            if (cookie && cookie.indexOf(\"c_',mstname,'_'f(mfn,1,0),'|\")!=-1){
                document.getElementById(\"c_',mstname,'_'f(mfn,1,0)'\").setAttribute(\"checked\", \"checked\");
            }
        </script>
        '
    ";

    // 4. Executa a chamada ao WXIS usando o seu `unique.xis`
    $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $base . ".par&Mfn=" . $mfn . "&Opcion=directa&Expresion=" . urlencode($Expresion) . "&Formato=" . urlencode($pft_dinamico) . "&lang=" . $lang;
    $resultado = wxisLlamar($base, $query, $xWxis . "opac/unique.xis");

    // 5. Gera o HTML do botão "Ver Detalhes"
    $detailButtonHtml = '
        <button 
            type="button" 
            class="btn btn-sm btn-outline-primary me-2 open-detail-modal" 
            data-base="' . htmlspecialchars($base) . '" 
            data-mfn="' . htmlspecialchars($mfn) . '" 
            data-bs-toggle="modal" 
            data-bs-target="#recordDetailModal"
            title="' . $msgstr["front_ver_detalhes"] . '">
            <i class="fas fa-search-plus"></i> ' . $msgstr["front_ver_detalhes"] . ' 
        </button>';



    // 6. Imprime o card completo, FILTRANDO a linha [TOTAL:]
    echo "<div class='registro-item card mb-4'>";
    if (is_array($resultado)) {
        foreach ($resultado as $linha) {
            // Se a linha não contiver '[TOTAL:]', então ela será impressa.
            if (strpos(trim($linha), '[TOTAL:]') === false) {
                $linha = str_replace("@@@", $detailButtonHtml, $linha);

                if (substr($linha, 0, 6) == '$$REF:') {
                    $ref = substr($linha, 6);
                    $f = explode(",", $ref);
                    $bd_ref = $f[0];
                    $pft_ref = $f[1];
                    $a = $pft_ref;
                    $pft_ref = "@" . $a . ".pft";
                    $expr_ref = $f[2];
                    $reverse = "";
                    if (isset($f[3]))
                        $reverse = "ON";
                    $IsisScript = $xWxis . "opac/buscar.xis";
                    $query = "&cipar=" . $db_path . $actparfolder . "/$bd_ref.par&Expresion=" . $expr_ref . "&Opcion=buscar&base=" . $bd_ref . "&Formato=$pft_ref&count=100&lang=" . $_REQUEST["lang"];
                    if ($reverse != "") {
                        $query .= "&reverse=On";
                    }
                    $relacion = wxisLlamar($bd_ref, $query, $IsisScript);
                    foreach ($relacion as $linea_alt) {
                        if (substr(trim($linea_alt), 0, 8) != "[TOTAL:]") echo $linea_alt . "\n";
                    }
                } else {
                    echo $linha . "\n";
                }
            }
        }
    }
    echo "</div>";
}


/**
 * Exibe um card genérico para registros restritos.
 */
function ApresentarRegistroRestrito()
{
    global $msgstr;
    $message = $msgstr["front_restricted_record_auth"] ?? "Este registro é restrito. Por favor, autentique-se com o nível de permissão adequado para visualizar.";

    echo '<div class="card overflow-auto bg-white my-2">
            <div class="card-body">
                <p class="text-danger m-0">
                    <i class="fas fa-eye-slash"></i> ' . $message . '
                
             <a class="nav-link text-dark custom-top-link  mx-2" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="fas fa-sign-in-alt"></i>'.$msgstr["front_login"]. '
            </a></p>
            </div>
          </div>';
}
