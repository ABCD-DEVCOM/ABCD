<?php

/**
 * OPAC Security - Input Validation
 * Implements Parameter Whitelisting and Malicious Pattern Detection
 */

// Obtain the customer's actual IP address
function get_client_ip()
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            // In case of multiple IPs (proxies), take the first one
            $ip_array = explode(',', $_SERVER[$key]);
            return trim($ip_array[0]);
        }
    }
    return 'IP_desconhecido';
}

// Checks for dangerous patterns in VALUE (SQL Injection, XSS, Path Traversal)
function is_malicious($str)
{
    // If it is an array, do not check here (it is already handled in the validation loop).
    if (is_array($str)) return false;

    $patterns = [
        '/<script\b/i',             // XSS
        '/(and|or)\s+\d+=\d+/i',    // Classic SQLi (1=1)
        '/[\'"`]\s*(or|and)?\s*\d+=\d+/i', // SQLi with quotation marks
        '/union\s+select/i',        // SQLi Union
        '/information_schema/i',    // SQLi Recon
        '/into\s+outfile/i',        // SQLi Arquivo
        '/\.\.\//',                 // Path Traversal (../)
        '/\.\.\\%2F/i',             // Path Traversal URL Encoded
        '/etc\/passwd/i',           // Access to system files
        '/cmd\.exe/i'               // Command execution
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $str)) return true;
    }
    return false;
}

/**
 * Validates all inputs (GET/POST) against a whitelist of known parameters.
 * Blocks unknown keys and malicious values.
 */
function validate_inputs($inputs, $source = 'INPUT')
{
    $ip = get_client_ip();

    // 1. WHITE LIST: Only these parameters are accepted in OPAC.
    // Anything outside of this will be considered suspicious.
    $allowed_params = [
        // Infrastructure
        'base',
        'cipar',
        'ctx',
        'lang',
        'db_path',
        'modo',
        'integrada',
        'lista_bases',
        'target_db',
        'indice_base',
        'open_index',

        // Search and Navigation
        'Opcion',
        'Expresion',
        'Sub_Expresion',
        'Sub_Expresiones',
        'camp',
        'oper',
        'pagina',
        'desde',
        'count',
        'resaltar',
        'Formato',
        'coleccion',
        'alcance',
        'prefijo',
        'prefijoindice',
        'indice',
        'campo',
        'id',
        'Diccio',
        'letra',
        'ira',
        'posting',
        'columnas',
        'facetas',
        'termosLivres',
        'search_form',
        'Campos',
        'Operadores',

        // Actions and User
        'cookie',
        'Accion',
        'sendto',
        'mfn',
        'k', // k = permalink
        'login',
        'password',
        'conf_level',
        'redirect',
        'existencias',
        'login_error',
        'IR_A',
        'sort',

        // Preview
        'titulo',
        'titulo_c',
        'submenu',
        'mostrar_exp',
        'home',
        'Pft',
        'decodificar',
        'page',
        'llamado_desde',
        'Navegacion',
        'LastKey',
        'Seleccionados',

        // Session / Admin (if applicable)
        'sid',
        'token',
        'cf-turnstile-response'
    ];

    foreach ($inputs as $key => $value) {

        // 2. KEY VERIFICATION (PARAMETER NAME)
        if (!in_array($key, $allowed_params)) {
            // Silently attempts or blocks
            //error_log("⚠️ [OPAC SEC] IP $ip tried unknown parameter: [$key]");

            // To be rigid and block:
            die("Access Blocked: Invalid parameter detected ($key).");

            // If you prefer to simply ignore the parameter (softer approach):
            // unset($_REQUEST[$key]); 
            // continue;
        }

        // 3. VALUE VERIFICATION (CONTENT)
        if (is_array($value)) {
            // Recursive validation for arrays (ex: camp[], Sub_Expresiones[])
            foreach ($value as $subval) {
                if (is_malicious($subval)) {
                    error_log("⚠️ [OPAC SEC] IP $ip ataque detectado em array [$key]: $subval");
                    die("Conteúdo inválido detectado.");
                }
            }
        } else {
            if (is_malicious($value)) {
                error_log("⚠️ [OPAC SEC] IP $ip ataque detectado em [$key]: $value");
                die("Conteúdo inválido detectado.");
            }
        }
    }
}

// Automatic execution on inclusion
// If you want to enable it automatically, uncomment the lines below.
// Otherwise, call validate_inputs($_REQUEST) at the beginning of the main scripts.
/*
if (!empty($_GET)) validate_inputs($_GET, 'GET');
if (!empty($_POST)) validate_inputs($_POST, 'POST');
*/
