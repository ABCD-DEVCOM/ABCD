<?php
/**
 * -------------------------------------------------------------------------
 * ABCD - Automação de Bibliotecas e Centros de Documentação
 * https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 * Script:   www/htdocs/opac/get_record_details.php
 * Purpose:  Endpoint AJAX to fetch record details for the modal.
 * Author:   Roger C. Guilherme
 *
 * Changelog:
 * -----------------------------------------------------------------------
 * 2025-10-22 rogercgui Initial version
 * 2025-11-09 rogercgui Added detailed logging for debugging
 * 2025-11-11 rogercgui Fixed cache key to include requested format
 * 2026-09-29 rogercgui Added a hook for additional action buttons if the function exists.
 * -------------------------------------------------------------------------
 */


// --- Essential Configuration and Includes ---

include("../central/config_opac.php");
include("functions.php");

// --- Validation and Parameter Acquisition ---
$base = isset($_REQUEST['base']) ? trim(strip_tags($_REQUEST['base'])) : null;
$mfn  = isset($_REQUEST['mfn'])  ? trim(strip_tags($_REQUEST['mfn']))  : null;
$lang = isset($_REQUEST['lang']) ? trim(strip_tags($_REQUEST['lang'])) : $lang;
$requested_format = isset($_REQUEST['Formato']) ? trim(strip_tags($_REQUEST['Formato'])) : null;

// --- START: CACHE CHECK ---
$cache_key = "record_details_" . $base . "_" . $mfn . "_" . ($requested_format ?? 'full') . "_" . $lang;
$cached_json = opac_cache_get($cache_key);

if ($cached_json !== false) {
    // Enviamos o JSON cacheado e encerramos o script.
    header('Content-Type: application/json; charset=UTF-8');
    echo $cached_json;
    exit;
}

// --- END: CACHE CHECK ---

$response = [
    'recordHtml' => '',
    'availableFormats' => [],
    'actionButtonsHtml' => '',
    'error' => null
];

// --- SECURITY CHECK: AUTHORISED BASE ---
if (empty($base)) {
    $response['error'] = "Parâmetro 'base' não fornecido.";
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
    exit;
}

// Includes the script that reads opac_conf/bases.dat and populates $bd_list
include_once($Web_Dir . 'includes/leer_bases.php'); //

// Checks whether the requested database exists in the list of permitted databases ($bd_list)

if (!isset($bd_list[$base])) {
    // If the base is not in the list, returns an unauthorised access error
    $response['error'] = "Unauthorised access to the requested database.";
    // You may wish to log this unauthorised access attempt here.
    // error_log(‘Attempted unauthorised access to database “$base” via get_record_details.php’);
    header('Content-Type: application/json; charset=UTF-8');
    // Consider sending an HTTP 403 Forbidden status as well:
    // header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit;
}

// --- START: LOAD RESTRICTION FUNCTIONS ---
// $base is already defined in this script.
opac_load_restriction_settings(); // Load restriction settings
global $OPAC_RESTRICTION, $msgstr;
// --- END: LOAD RESTRICTION FUNCTIONS ---

// Basic validation
if (empty($base) || empty($mfn) || !ctype_digit($mfn)) {
    $response['error'] = "Parâmetros 'base' ou 'mfn' inválidos.";
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
    exit;
}


// ==================================================================
// START: RESTRICTION CHECK (PRE-CHECK)
// ==================================================================

// $base and $mfn are already defined in this script.

// 1. Sets the global $base and loads the settings
$GLOBALS['base'] = $base;
opac_load_restriction_settings();
global $OPAC_RESTRICTION, $msgstr;

// 2. Call our new verification function (from Step 2)
$permission = opac_precheck_record($base, $mfn);

// 3. Applies the decision
if ($permission === 'hidden') {
    $response['error'] = $msgstr["front_restricted_record_hidden"] ?? "Registro não encontrado.";
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
    exit;
}



if ($permission === 'auth_message') {
    $response['error'] = $msgstr["front_restricted_record_auth_title"] ?? "Registro Restrito";
    $response['recordHtml'] = '<div class="alert alert-danger m-3"><i class="fas fa-eye-slash"></i> ' . ($msgstr["front_restricted_record_auth"] ?? "Este registro é restrito. Por favor, autentique-se com o nível de permissão adequado para visualizar.") . '</div>';
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
    exit;
}

// ==================================================================
// END: RESTRICTION CHECK
// ==================================================================

// --- Lógica Principal ---

try {
    // 1. Ler os formatos disponíveis e encontrar o padrão
    $formatos_file = $db_path . $base . "/opac/" . $lang . "/" . $base . "_formatos.dat";
    if (!file_exists($formatos_file)) {
        $formatos_file = $db_path . $base . "/opac/" . $base . "_formatos.dat";
    }

    $default_format_Y = null;
    $first_format_list = null;

    if (file_exists($formatos_file)) {
        $lines = file($formatos_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            $format_name = trim($parts[0]);
            if (substr($format_name, -4) == ".pft") $format_name = substr($format_name, 0, -4);

            $label = isset($parts[1]) && trim($parts[1]) != "" ? trim($parts[1]) : $format_name;
            $is_default = isset($parts[3]) && strtoupper(trim($parts[3])) === 'Y';
            $response['availableFormats'][] = ['name' => $format_name, 'label' => $label, 'is_default' => $is_default];

            if ($is_default) {
                $default_format_Y = $format_name;
            }

            if ($first_format_list === null) {
                $first_format_list = $format_name;
            }
        }
    } else {
        $response['availableFormats'][] = ['name' => $base, 'label' => $base]; // Fallback
        $first_format_list = $base;
    }

    $pft_path_dc = $db_path . $base . "/pfts/dcxml.pft";
    if (!file_exists($pft_path_dc)) $pft_path_dc = $db_path . $base . "/pfts/dcxml.pft";
    if (file_exists($pft_path_dc)) {
        $response['availableFormats'][] = ['name' => 'xml_dc', 'label' => 'XML (DC)', 'is_default' => false];
    } else {
        error_log("get_record_details: PFT dcxml.pft não encontrado para base '$base'");
    }



    $pft_path_marcxml = $db_path . $base . "/pfts/" . $lang . "/marcxml.pft";
    if (!file_exists($pft_path_marcxml)) $pft_path_marcxml = $db_path . $base . "/pfts/marcxml.pft";
    if (file_exists($pft_path_marcxml)) {
        $response['availableFormats'][] = ['name' => 'xml_marc', 'label' => 'XML (MARC)', 'is_default' => false];
    } else {
        error_log("get_record_details: PFT marcxml.pft não encontrado para base '$base'");
    }

    // 2. Determinar o formato a ser usado
    $active_format = null;
    $format_found = false;
    if ($requested_format !== null) {
        foreach ($response['availableFormats'] as $fmt) {
            if ($fmt['name'] == $requested_format) {
                $active_format = $requested_format;
                $format_found = true;
                break;
            }
        }
    }



    // Se não foi solicitado ou o solicitado não existe, usar o padrão
    if (!$format_found) {
        if ($default_format_Y !== null) {
            $active_format = $default_format_Y;
        } else {
            $active_format = $first_format_list;
        }
    }

    // Garante que temos um formato, mesmo que seja apenas o nome da base
    if ($active_format === null) $active_format = $base;

    // 3. Buscar o HTML do registro usando wxisLlamar e imprimir.xis
    $cipar = $db_path . $actparfolder . $base . ".par";
    $resultado = null;
    $IsisScript = $xWxis;
    $formato_pft_final = null; // Guarda o nome do PFT a ser usado



    if ($active_format == 'xml_dc') {
        $pft_path = $db_path . $base . "/pfts/" . $lang . "/dublincore.pft";
        if (!file_exists($pft_path)) $pft_path = $db_path . $base . "/pfts/dublincore.pft";
        if (file_exists($pft_path)) {
            $formato_pft_final = "@" . $pft_path;
            $IsisScript .= "opac/unique.xis"; // ou unique.xis
        } else {
            $response['error'] = "Formato XML (DC) indisponível.";
        }
    } elseif ($active_format == 'xml_marc') {
        $pft_path = $db_path . $base . "/pfts/" . $lang . "/marcxml.pft";
        if (!file_exists($pft_path)) $pft_path = $db_path . $base . "/pfts/marcxml.pft";
        if (file_exists($pft_path)) {
            $formato_pft_final = "@" . $pft_path;
            $IsisScript .= "opac/unique.xis"; // ou unique.xis
        } else {
            $response['error'] = "Formato XML (MARC) indisponível.";
        }
    } else {

        // Lógica para formatos PFT normais

        $pft_path = $db_path . $base . "/pfts/" . $lang . "/" . $active_format . ".pft";
        if (!file_exists($pft_path)) $pft_path = $db_path . $base . "/pfts/" . $active_format . ".pft";
        if (file_exists($pft_path)) {
            $formato_pft_final = "@" . $pft_path;
            $IsisScript .= "opac/unique.xis";
        } else {
            $response['error'] = "Formato '$active_format' indisponível.";
        }
    }



    // Executa a chamada WXIS SE um formato válido foi encontrado E não há erro prévio
    $record_html_raw = "";
    if ($formato_pft_final !== null && $response['error'] === null) {
        $query = "&base=$base&cipar=$cipar&Mfn=$mfn&Formato=" . urlencode($formato_pft_final) . "&lang=" . $lang;
        $resultado = wxisLlamar($base, $query, $IsisScript);

        if (is_array($resultado)) {
            foreach ($resultado as $line) {
                if (substr(trim($line), 0, 8) != '[TOTAL:]') {
                    if (substr($line, 0, 6) == '$$REF:') {
                        $ref = substr($line, 6);
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
                        $query = "&cipar=" . $db_path . $actparfolder . "/$bd_ref.par&Expresion=" . $expr_ref . "&Opcion=buscar&base=" . $bd_ref . "&Formato=$pft_ref&count=90000&lang=" . $_REQUEST["lang"];
                        if ($reverse != "") {
                            $query .= "&reverse=On";
                        }
                        $relacion = wxisLlamar($bd_ref, $query, $IsisScript);
                        foreach ($relacion as $linea_alt) {
                            if (substr(trim($linea_alt), 0, 8) != "[TOTAL:]") $record_html_raw .= $linea_alt . "\n";
                        }
                    } else {
                        $record_html_raw .= $line . "\n"; // Adiciona nova linha para XML
                    }
                }
            }
        } else {
            $response['error'] = "Erro ao buscar registro ($base/$mfn) com formato $active_format.";
        }
    } elseif ($response['error'] === null) {
        // Define erro se $formato_pft_final for null mas não havia erro antes
        $response['error'] = "Erro interno ao determinar o formato PFT.";
    }



    // 4. Tratar Encoding (ISO->UTF-8) primeiro, salvando em $record_html_processed
    $dr_path = $db_path . $base . "/dr_path.def";
    $def_db = file_exists($dr_path) ? parse_ini_file($dr_path) : [];
    $cset_db = (!isset($def_db['UNICODE']) || $def_db['UNICODE'] != "1") ? "ANSI" : "UTF-8";

    $record_html_processed = ""; // Inicializa

    if ($cset_db == "ANSI" && mb_detect_encoding($record_html_raw, 'UTF-8', true) === false) {
        $record_html_processed = mb_convert_encoding($record_html_raw, "UTF-8", "ISO-8859-1");
    } else {
        $record_html_processed = $record_html_raw;
    }

    // Formats the final output in $response['recordHtml']

    if ($active_format == 'xml_dc' || $active_format == 'xml_marc') {
        if (strpos(trim($record_html_processed), '<?xml') !== 0) {
            // Use \strpos se a função normal der erro
            $record_html_processed = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $record_html_processed;
        }

        $response['recordHtml'] = '<pre><code class="language-xml">' . htmlspecialchars($record_html_processed, ENT_QUOTES, 'UTF-8') . '</code></pre>';
        //error_log("get_record_details: Formatando saída como XML para exibição.");
    } else {
        $response['recordHtml'] = $record_html_processed; // Atribui HTML/PFT normal
        //error_log("get_record_details: Mantendo saída como HTML normal para formato '$active_format'.");
    }


    // Generate Action Buttons

    $toolButtons = new ToolButtons([]); // Instancia sem contexto específico, se não precisar
    $botoes_nativos = $toolButtons->generateButtonsHtmlForRecord($db_path, $base, $lang, $mfn);

    if (function_exists('abcd_run_hook')) {
        // Passes the native buttons as a filter, allowing the plugin to add to or modify the HTML
        $response['actionButtonsHtml'] = abcd_run_hook('opac_record_toolbar', $botoes_nativos);
    } else {
        $response['actionButtonsHtml'] = $botoes_nativos;
    }

} catch (Exception $e) {
    $response['error'] = "Erro interno no servidor: " . $e->getMessage();
    // LOG DENTRO DO CATCH
    //error_log("get_record_details: EXCEPTION CAPTURADA: " . $e->getMessage());
    //error_log("get_record_details: Response ANTES de enviar (dentro do catch): " . print_r($response, true));
}

// --- LOG FINAL ANTES DA SAÍDA JSON ---
//error_log("get_record_details: Response FINAL ANTES de enviar: " . print_r($response, true));
// ------------------------------------

// --- Saída JSON ---
header('Content-Type: application/json; charset=UTF-8');
// --- INÍCIO: GRAVAÇÃO EM CACHE ---

// Converte o array de resposta em JSON
$json_response = json_encode($response);

// (Não queremos cachear "Acesso Restrito" ou "Registro não encontrado")
if (!isset($response['error'])) {
    // Usa a mesma $cache_key definida no topo do script
    opac_cache_set($cache_key, $json_response);
    //error_log("get_record_details: SUCESSO. Gravando no cache com a chave: $cache_key");
}

// Envia o JSON para o usuário
echo $json_response;

// --- FIM: GRAVAÇÃO EM CACHE ---
exit;
