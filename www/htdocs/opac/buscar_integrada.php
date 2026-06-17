<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   www/htdocs/opac/buscar_integrada.php
 *  Purpose:  Integrated search page for OPAC
 *  Author:   Roger C. Guilherme
 *  Author:   Guilda Ascencio
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2022-03-23 rogercgui change the folder /par to the variable $actparfolder
 *  2025-10-16 rogercgui Correção final de relevância, ranking, proximidade e prefixos.
 *  2025-10-10 rogercgui Correção de relevância, ranking e proximidade.
 *  2024-06-12 rogercgui Implementa seleção automática de formato padrão por base de dados.
 *  2024-05-30 rogercgui Implementa verificação de CAPTCHA Cloudflare.
 *  2024-05-15 rogercgui Refatora a função de busca para melhorar a relevância e o ranking dos resultados.
 *  2024-05-01 rogercgui Adiciona prefixo 'v' para campos numéricos na função de construção de PFT de relevância.
 *  2024-04-20 rogercgui Refatora a função SelectFormato para melhorar a seleção do formato padrão.
 *  2025-03-15 rogercgui added hidden input target_db to search_free.php and set its value in dropdown_db.php
 *  2025-03-10 rogercgui changed dropdown db to use data-value and data-text instead of javascript function
 *  2026-04-12 rogercgui Prevents errors if pagination ('from') exceeds the total due to bots
 *  2026-04-10 rogercgui Adds support for multiple values in URL parameters (e.g., facets) when changing format
 *  2026-06-17 rogercgui Final corrections and code cleanup after testing with various databases and configurations. This includes fixing edge cases in relevance calculation, ensuring proper encoding handling, and improving the robustness of the format selection logic.
 * -------------------------------------------------------------------------
 */

if (!function_exists('str_contains')) {
	function str_contains($haystack, $needle)
	{
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

// --- 1. ESSENTIAL CONFIGURATION ---
if (isset($_REQUEST["db_path"])) $_REQUEST["db_path"] = urldecode($_REQUEST["db_path"]);

// --- 2. USER ACTION CONTROLLER ---
if (isset($_REQUEST['Accion']) && !empty($_REQUEST['Accion'])) {

	$acao = $_REQUEST['Accion'];

	switch ($acao) {
		case 'reserve':
		case 'reserve_one': // Captures both actions (from the card and the modal)

			// 2a. Is the user logged in?
			if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
				// YOU ARE NOT LOGGED IN.
				$url_origem = "buscar_integrada.php?" . $_SERVER['QUERY_STRING']; // Fallback é a própria pág. de busca
				if (isset($_SERVER['HTTP_REFERER'])) {
					$url_origem = $_SERVER['HTTP_REFERER'];
				}

				// Adds error "2" to the login modal
				$separator = (strpos($url_origem, '?') !== false) ? '&' : '?';
				header('Location: ' . $url_origem . $separator . 'login_error=2');
				exit;
			} else {
				// YES, YOU ARE LOGGED IN.
				$cookie_data = isset($_REQUEST['cookie']) ? $_REQUEST['cookie'] : '';
				header('Location: myabcd/reserve.php?lang=' . $lang . '&cookie=' . urlencode($cookie_data));
				exit;
			}
			break;
	}
}
// --- END OF CONTROLLER ---



include("../central/config_opac.php");
include($Web_Dir . 'head.php');
include get_template_file("views/record_card.php");
include get_template_file("views/nav_pages.php");


// --- CAPTCHA VERIFICATION ---
if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y' && isset($opac_gdef['CAPTCHA_SECRET_KEY'])) {
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (!validarCaptchaCloudflare($opac_gdef['CAPTCHA_SECRET_KEY'])) {
			echo "<h1>Validation Error</h1><p>The CAPTCHA verification failed. Please try again.</p>";
			echo "<a href='javascript:history.back()'>Back</a>";
			die();
		}
	}
}

// --- AUXILIARY FUNCTIONS ---
function prefixFieldsWithV($fields_string)
{
	if (empty($fields_string)) return "";
	$tags = explode(',', $fields_string);
	$prefixed_tags = [];
	foreach ($tags as $tag) {
		$tag = trim($tag);
		if (is_numeric($tag) || strpos($tag, '^') !== false) {
			$prefixed_tags[] = 'v' . $tag;
		} else {
			$prefixed_tags[] = $tag;
		}
	}
	return implode(", ", $prefixed_tags);
}

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

// DETECTS AVAILABLE FORMATS BY BASE AND SELECTS THE DEFAULT FORMAT
function getDefaultFormatForBase($base, $db_path, $lang)
{
	$formatos_file = $db_path . $base . "/opac/" . $lang . "/" . $base . "_formatos.dat";
	if (!file_exists($formatos_file)) $formatos_file = $db_path . $base . "/opac/" . $base . "_formatos.dat";

	$default_format = null;
	$first_format = null; // Save the first format as a fallback

	if (file_exists($formatos_file)) {
		$lines = file($formatos_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			if (trim($line) != "") {
				$parts = explode('|', $line);
				$format_name = trim($parts[0]);
				if (substr($format_name, -4) == ".pft") $format_name = substr($format_name, 0, -4);

				// Save the first format found
				if ($first_format === null) {
					$first_format = $format_name;
				}

				// Checks whether the third column exists and is "Y" (case-insensitive)
				if (isset($parts[2]) && strtoupper(trim($parts[2])) === 'Y') {
					$default_format = $format_name;
					break; // Once you've found the pattern, you can stop searching.
				}
			}
		}
	}

	// If you find one marked with Y, return it.
	if ($default_format !== null) {
		return $default_format;
	}
	// If you did not find Y, but found some format, return the first one.
	elseif ($first_format !== null) {
		return $first_format;
	}
	// Final fallback: returns the base name if the file was empty or does not exist.
	else {
		return $base;
	}
}

function SelectFormato($base, $db_path, $msgstr)
{
	global $lang;
	$archivo = $base . "_formatos.dat";
	$fp = null;

	// Tenta ler o arquivo de formatos no idioma atual ou no padrão
	if (file_exists($db_path . $base . "/opac/" . $lang . "/" . $archivo))
		$fp = file($db_path . $base . "/opac/" . $lang . "/" . $archivo);
	elseif (file_exists($db_path . $base . "/opac/" . $archivo))
		$fp = file($db_path . $base . "/opac/" . $archivo);

	// Se não houver arquivo de formatos, retorna vazio (usa padrão interno)
	// Mas para não quebrar o list(), retornamos array com erro ou vazio
	if (!$fp) return array("", "");

	$formatos_disponiveis = [];
	$formato_padrao_Y = null;
	$primeiro_formato_lista = null;

	// --- Etapa 1: Ler todos os formatos e identificar o padrão 'Y' ---
	foreach ($fp as $linea) {
		if (trim($linea) != "") {
			$f = explode('|', $linea);
			$format_name = trim($f[0]);
			if (substr($format_name, -4) == ".pft") $format_name = substr($format_name, 0, -4);

			$label = isset($f[1]) && trim($f[1]) != "" ? trim($f[1]) : $format_name;
			$is_default = isset($f[2]) && strtoupper(trim($f[2])) === 'Y';

			$formatos_disponiveis[] = ['name' => $format_name, 'label' => $label, 'is_default' => $is_default];

			if ($is_default) {
				$formato_padrao_Y = $format_name;
			}
			if ($primeiro_formato_lista === null) {
				$primeiro_formato_lista = $format_name;
			}
		}
	}

	// --- Etapa 2: Determinar qual formato deve estar ativo ---
	$formato_ativo = null;
	if (isset($_REQUEST["Formato"])) {
		foreach ($formatos_disponiveis as $fmt) {
			if ($fmt['name'] == $_REQUEST["Formato"]) {
				$formato_ativo = $_REQUEST["Formato"];
				break;
			}
		}
	}

	// Fallback se não vier na URL
	if ($formato_ativo === null) {
		if ($formato_padrao_Y !== null) {
			$formato_ativo = $formato_padrao_Y;
		} else {
			$formato_ativo = $primeiro_formato_lista;
		}
	}

	// --- Etapa 3: Construir o HTML do Dropdown ---

	// Adiciona campos hidden para submeter o form ao trocar o formato
	$hidden_fields = "";
	$parametros = $_GET;
	unset($parametros['Formato'], $parametros['desde'], $parametros['pagina']);

	// [CORREÇÃO CRÍTICA] Loop que suporta Arrays
	foreach ($parametros as $key => $value) {
		if (is_array($value)) {
			foreach ($value as $item) {
				$hidden_fields .= '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($item) . '">';
			}
		} else {
			$hidden_fields .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
		}
	}

	// Reseta paginação
	$hidden_fields .= '<input type="hidden" name="desde" value="1">';
	$hidden_fields .= '<input type="hidden" name="pagina" value="1">';

	// Monta o HTML final
	$salida = "<div class='d-inline-block'>"; // Container para alinhar
	$salida .= "<form name='cambio_formato' method='get' action='./' class='m-0 d-flex align-items-center'>";
	$salida .= $hidden_fields;

	// Label (opcional, pode remover se ocupar muito espaço)
	$label_fmt = isset($msgstr["front_formato_exibicao"]) ? $msgstr["front_formato_exibicao"] : "Formato";
	$salida .= "<label class='me-2 text-nowrap'>" . $label_fmt . ":</label>";

	$salida .= "<select name='Formato' onchange='this.form.submit()' class='form-select form-select-sm'>";

	foreach ($formatos_disponiveis as $fmt) {
		$selected = ($fmt['name'] == $formato_ativo) ? "selected" : "";
		$salida .= "<option value='" . $fmt['name'] . "' $selected>" . $fmt['label'] . "</option>";
	}

	$salida .= "</select>";
	$salida .= "</form>";
	$salida .= "</div>";

	// Retorna o HTML e o formato ativo para o script principal usar
	return array($salida, $formato_ativo);
}

// Usada quando há truncagem ($) ou quando a relevância não é desejada.
// --- FUNÇÃO DE BUSCA DIRETA (CORRIGIDA COM TRUNCAGEM FORÇADA) ---
function searchDirect($bd_list, $db_path, $Expresion, $termo_livre, $Expr_facetas)
{
	global $actparfolder, $lang, $xWxis, $meta_encoding;
	$todos_os_registros = [];

	// Verifica se o usuário digitou $ (truncagem)
	// Se sim, vamos forçar a adição desse caractere na URL do WXIS,
	// pois a função construir_expresion() ou urlencode() pode estar removendo-o.
	$tem_truncagem = (strpos($termo_livre, '$') !== false);
	$sufixo_truncagem = $tem_truncagem ? '$' : '';

	// 1. Prepara a expressão de busca
	$busqueda = $Expresion;
	if (isset($_REQUEST["coleccion"]) and $_REQUEST["coleccion"] != "") {
		$coleccion = explode('|', urldecode($_REQUEST["coleccion"]));

		// CORREÇÃO: Garante que os índices existam antes de concatená-los
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

	// 2. Passo 1: Obter totais
	$total_base = [];
	$busqueda_decode_arr = [];

	foreach ($bd_list as $base => $value) {
		$cipar = $base;
		$dr_path = $db_path . $base . "/dr_path.def";
		$def_db = file_exists($dr_path) ? parse_ini_file($dr_path) : [];
		$cset_db = (!isset($def_db['UNICODE']) || $def_db['UNICODE'] != "1") ? "ANSI" : "UTF-8";
		$cset = strtoupper($meta_encoding);
		//$busqueda_decode = ($cset == "UTF-8" && $cset_db == "ANSI") ? mb_convert_encoding($busqueda, 'ISO-8859-1', 'UTF-8') : $busqueda;
	
		// --- INÍCIO DA CODIFICAÇÃO PARA O WXIS (Sua solução do Dataentry) ---
		$busqueda_decode = $busqueda;
		if ($cset == "UTF-8" && $cset_db == "ANSI") {
			if (mb_check_encoding($busqueda_decode, 'UTF-8')) {
				$busqueda_decode = mb_encode_numericentity($busqueda_decode, [0x80, 0xffff, 0, 0xffff], 'UTF-8');
			}
		}
		// --- FIM DA CODIFICAÇÃO ---		


		$busqueda_decode_arr[$base] = $busqueda_decode;

		// AQUI ESTÁ O TRUQUE: Adicionamos $sufixo_truncagem APÓS o urlencode.
		// Se o usuário digitou $, ele será anexado à query.
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

	if (empty($total_base)) return [];

	// 3. Passo 2: Buscar conteúdo
	foreach ($total_base as $base => $total) {
		$pft_relevancia = buildRelevancePft($base, $db_path);

		// AQUI TAMBÉM: Adicionamos $sufixo_truncagem e corrigimos 'desde' para 'from'
		$query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $base . ".par&Expresion=" . urlencode($busqueda_decode_arr[$base]) . $sufixo_truncagem . "&from=1&count=" . $total . "&Formato=" . urlencode($pft_relevancia) . "&lang=" . $lang;

		$resultado_completo = wxisLlamar($base, $query, $xWxis . "opac/buscar.xis");

		if (is_array($resultado_completo)) {
			$conteudo_junto = implode("", $resultado_completo);
			$registros_separados = explode("##RECORD_SEPARATOR##", $conteudo_junto);

			foreach ($registros_separados as $registro_str) {
				if (trim($registro_str) === "") continue;

				preg_match('/<mfn>(\d+)<\/mfn>/', $registro_str, $mfn_arr);
				preg_match('/<f_title>(.*?)<\/f_title>/s', $registro_str, $titulo_arr);
				preg_match('/<f_author>(.*?)<\/f_author>/s', $registro_str, $autor_arr);
				preg_match('/<f_subject>(.*?)<\/f_subject>/s', $registro_str, $assunto_arr);

				if (isset($mfn_arr[1])) {
					$todos_os_registros[] = [
						'mfn' => $mfn_arr[1],
						'base' => $base,
						'pontuacao' => 100, // Pontuação fixa para busca direta
						'sort_title' => isset($titulo_arr[1]) ? strip_tags($titulo_arr[1]) : '',
						'sort_author' => isset($autor_arr[1]) ? strip_tags($autor_arr[1]) : '',
						'sort_subject' => isset($assunto_arr[1]) ? strip_tags($assunto_arr[1]) : ''
					];
				}
			}
		}
	}

	// 4. Ordenação Padrão
	$sort_key = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "mfn_desc";
	$sort_field = 'mfn';
	$sort_direction = SORT_DESC;
	$sort_flags = SORT_REGULAR;

	switch ($sort_key) {
		case 'title_asc':
			$sort_field = 'sort_title';
			$sort_direction = SORT_ASC;
			$sort_flags = SORT_STRING;
			break;
		case 'title_desc':
			$sort_field = 'sort_title';
			$sort_direction = SORT_DESC;
			$sort_flags = SORT_STRING;
			break;
		case 'author_asc':
			$sort_field = 'sort_author';
			$sort_direction = SORT_ASC;
			$sort_flags = SORT_STRING;
			break;
		case 'author_desc':
			$sort_field = 'sort_author';
			$sort_direction = SORT_DESC;
			$sort_flags = SORT_STRING;
			break;
		case 'mfn_asc':
			$sort_field = 'mfn';
			$sort_direction = SORT_ASC;
			$sort_flags = SORT_NUMERIC;
			break;
		case 'mfn_desc':
			$sort_field = 'mfn';
			$sort_direction = SORT_DESC;
			$sort_flags = SORT_NUMERIC;
			break;
		case 'relevance':
		default:
			$sort_field = 'mfn';
			$sort_direction = SORT_DESC;
			$sort_flags = SORT_NUMERIC;
			break;
	}

	$sort_array = [];
	foreach ($todos_os_registros as $key => $row) {
		$val = $row[$sort_field];
		if ($sort_flags == SORT_STRING) $val = strtolower($val);
		$sort_array[$key] = $val;
	}

	array_multisort($sort_array, $sort_direction, $sort_flags, $todos_os_registros);

	return $todos_os_registros;
}

// --- CENTRAL SEARCH FUNCTION (WITH RELEVANCE CORRECTIONS) ---
function searchAndOrganizeResults($bd_list, $db_path, $Expresion, $termo_livre, $Expr_facetas)
{
	global $actparfolder, $lang, $xWxis, $meta_encoding;
	$todos_os_registros = [];

	$busqueda = $Expresion;
	if (isset($_REQUEST["coleccion"]) and $_REQUEST["coleccion"] != "") {
		$coleccion = explode('|', urldecode($_REQUEST["coleccion"]));
		if ($Expresion != "" and $Expresion != '$' and $Expresion != $coleccion[2] . $coleccion[0]) {
			$busqueda = "(" . $Expresion . ") and (" . $coleccion[2] . $coleccion[0] . ")";
		} else {
			$busqueda = $coleccion[2] . $coleccion[0];
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

	// STEP 1: GET THE TOTAL
	$total_base = [];
	$busqueda_decode_arr = [];
	foreach ($bd_list as $base => $value) {
		$cipar = $base;
		$dr_path = $db_path . $base . "/dr_path.def";
		$def_db = file_exists($dr_path) ? parse_ini_file($dr_path) : [];
		$cset_db = (!isset($def_db['UNICODE']) || $def_db['UNICODE'] != "1") ? "ANSI" : "UTF-8";
		$cset = strtoupper($meta_encoding);
		//$busqueda_decode = ($cset == "UTF-8" && $cset_db == "ANSI") ? mb_convert_encoding($busqueda, 'ISO-8859-1', 'UTF-8') : $busqueda;

		// --- INÍCIO DA CODIFICAÇÃO PARA O WXIS (Sua solução do Dataentry) ---
		$busqueda_decode = $busqueda;
		if ($cset == "UTF-8" && $cset_db == "ANSI") {
			if (mb_check_encoding($busqueda_decode, 'UTF-8')) {
				$busqueda_decode = mb_encode_numericentity($busqueda_decode, [0x80, 0xffff, 0, 0xffff], 'UTF-8');
			}
		}
		// --- FIM DA CODIFICAÇÃO ---		

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
					// Se $termo_livre (fonte de pontuação) estava vazio,
					// significa que não era uma busca 'libre' para pontuar (ex: era 'directa').
					// Nesses casos, a pontuação é 0, mas o registro DEVE ser incluído.
					// Se a pontuação local foi 0 (ex: conflito de acentos), 
					// forçamos 1 para que o registro NÃO seja descartado.
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

	// Pega o parâmetro 'sort' da URL, com 'relevance' como padrão
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
		// Usa strtolower para ordenação alfabética correta
		$sort_array[$key] = strtolower($row[$sort_field]);
	}

	// Sort the main array ($all_records) using the extracted column.
	array_multisort($sort_array, $sort_direction, $todos_os_registros);

	return $todos_os_registros;
} // End of the searchAndOrganiseResults function

if (!function_exists('pc_permute')) {
	function pc_permute($items, $perms = [])
	{
		if (empty($items)) {
			return [$perms];
		} else {
			$return = [];
			for ($i = count($items) - 1; $i >= 0; --$i) {
				$newitems = $items;
				$newperms = $perms;
				list($foo) = array_splice($newitems, $i, 1);
				array_unshift($newperms, $foo);
				$return = array_merge($return, pc_permute($newitems, $newperms));
			}
			return $return;
		}
	}
}


// --- PREPARING THE SEARCH ---
if (!isset($_REQUEST["Opcion"])) die;
if (!isset($_REQUEST["indice_base"])) $_REQUEST["indice_base"] = 0;
if (isset($rec_pag)) $_REQUEST["count"] = $rec_pag;

// CORRECTION: Forces text input and prevents bots from submitting text in the pagination parameters, which could break the script. If 'desde' or 'count' are missing, empty, or not numeric, we set them to default values.
$npages_fallback = isset($npages) ? $npages : 25;

if (!isset($_REQUEST["desde"]) || trim($_REQUEST["desde"]) == "" || !is_numeric($_REQUEST["desde"])) {
	$_REQUEST["desde"] = 1;
}
if (!isset($_REQUEST["count"]) || trim($_REQUEST["count"]) == "" || !is_numeric($_REQUEST["count"])) {
	$_REQUEST["count"] = $npages_fallback;
}

// Forces conversion to an integer (required by the 'array_slice' function in PHP 8+)
$desde = (int)$_REQUEST["desde"];
$count = (int)$_REQUEST["count"];

// Extra protection: prevents negative or zero page numbers (which also break the script)
if ($desde < 1) $desde = 1;
if ($count < 1) $count = 25;

if (!isset($_REQUEST["alcance"]) || $_REQUEST["alcance"] == "") $_REQUEST["alcance"] = "and";

// --- LOG E CONSTRUÇÃO DA EXPRESSÃO ---
$termo_para_log = null;
if (isset($_REQUEST['Opcion']) && $_REQUEST['Opcion'] == 'libre' && isset($_REQUEST['Sub_Expresion']) && trim($_REQUEST['Sub_Expresion']) != "") {
	$termo_para_log = urldecode($_REQUEST['Sub_Expresion']);
} elseif (isset($_REQUEST['Opcion']) && $_REQUEST['Opcion'] == 'directa' && isset($_REQUEST['Expresion']) && trim($_REQUEST['Expresion']) != "") {
	$expressao = urldecode($_REQUEST['Expresion']);
	if (preg_match('/[A-Z]{2,3}_(.*?)\)/', $expressao, $matches)) $termo_para_log = trim($matches[1]);
	else $termo_para_log = str_replace(['(', ')'], '', $expressao);
}
if ($termo_para_log) registrar_log_busca($termo_para_log);

// --- START OF DISPLAY CORRECTION (Russian/Polish) ---
// Decodes entities retrieved from the dictionary to display the raw term on the screen
if (isset($_REQUEST['Sub_Expresion'])) {
	$Expresion = mb_decode_numericentity(htmlspecialchars_decode($_REQUEST['Sub_Expresion']), [0x80, 0xffff, 0, 0xffff], 'UTF-8');
}
if (isset($_REQUEST['Expresion'])) {
	$Expresion = mb_decode_numericentity(htmlspecialchars_decode($_REQUEST['Expresion']), [0x80, 0xffff, 0, 0xffff], 'UTF-8');
}
// --- END OF CORRECTION ---

$Expresion = construir_expresion();
$_REQUEST["Expresion"] = $Expresion;

if (isset($_REQUEST["coleccion"]) and $_REQUEST["coleccion"] != "") {
	$coleccion = explode('|', urldecode($_REQUEST["coleccion"]));
	// CORRECTION: Array key protection
	$expr_coleccion = isset($coleccion[1]) ? htmlspecialchars($coleccion[1]) : 'Coleção';
	echo "<div style='margin-top:30px;display: block;width:100%;font-size:12px;'><h3>$expr_coleccion</h3></div>";
}

// --- EXECUTION OF THE SEARCH ---
$termo_livre = isset($_REQUEST["Sub_Expresion"]) ? urldecode($_REQUEST["Sub_Expresion"]) : "";
$Expr_facetas = isset($_REQUEST["facetas"]) && $_REQUEST["facetas"] != "" ? urldecode($_REQUEST["facetas"]) : "";


// A variável $bd_list foi carregada em head.php (via leer_bases.php) e contém TODAS as bases.
// Verificamos se o JavaScript enviou um parâmetro 'base' (Caso 2 da nossa lógica JS).

$lista_para_busca = $bd_list; // Por padrão, busca em todas

if (isset($_REQUEST['base']) && !empty($_REQUEST['base'])) {
	$base_selecionada = $_REQUEST['base'];

	// Verificamos se a base selecionada (vinda da URL) realmente existe na lista de bases válidas
	if (isset($bd_list[$base_selecionada])) {

		// Se existe, criamos uma nova lista SÓ com ela.
		$lista_para_busca = [];
		$lista_para_busca[$base_selecionada] = $bd_list[$base_selecionada];
	}
}

// 1. Executa a busca e obtém APENAS os registros pontuados
//    (Modificado para usar a $lista_para_busca em vez de $bd_list)
//$resultados_ordenados = searchAndOrganizeResults($lista_para_busca, $db_path, $Expresion, $termo_livre, $Expr_facetas);
//
// =========================================================
// DECISÃO DO TIPO DE BUSCA (RELEVÂNCIA vs DIRETA)
// =========================================================

// Verifica se existe truncagem ($) no termo livre
$tem_truncagem = (strpos($termo_livre, '$') !== false);

//error_log("<!-- Truncagem detectada: " . ($tem_truncagem ? "SIM" : "NÃO") . " -->\n");

if ($tem_truncagem) {
	// SE TEM TRUNCAGEM ($): Executa Busca Direta (Tradicional)
	// Ignora algoritmos de pontuação e traz tudo que o WXIS retornar.
	$resultados_ordenados = searchDirect($lista_para_busca, $db_path, $Expresion, $termo_livre, $Expr_facetas);
	error_log("<!-- Busca Direta executada devido à truncagem -->\n");
} else {
	// SE NÃO TEM TRUNCAGEM: Executa Busca por Relevância (Google-like)
	// Limpa termos, pontua ocorrências e ordena os melhores resultados.
	$resultados_ordenados = searchAndOrganizeResults($lista_para_busca, $db_path, $Expresion, $termo_livre, $Expr_facetas);
	//error_log("<!-- Busca por Relevância executada -->\n");
}


// 2. O total de registros é a contagem final
$total_registros = count($resultados_ordenados);
$contador = $total_registros;

// Calculamos o total por base A PARTIR DOS RESULTADOS FILTRADOS ($resultados_ordenados)
$total_por_base = [];
foreach ($resultados_ordenados as $registro) {
	$base_do_registro = $registro['base'];
	if (!isset($total_por_base[$base_do_registro])) {
		$total_por_base[$base_do_registro] = 0;
	}
	$total_por_base[$base_do_registro]++; // Adiciona +1 para esta base
}
// Agora, $total_por_base somará exatamente $total_registros

// 4. Prepara a paginação
$resultados_pagina_atual = array_slice($resultados_ordenados, $desde - 1, $count);

// Isso atualiza a variável global $bd_list com as 'descripcions'
include_once($Web_Dir . 'includes/leer_bases.php'); //

// 6. Inclui o arquivo da função de renderização
include_once get_template_file("views/search_header.php");

// 1. Inclui o novo arquivo da função de ordenação
include_once get_template_file("views/sort_dropdown.php");

// --- PREPARAR TERMO PARA CABEÇALHO ---
// Chama PresentarExpresion AQUI, depois das facetas terem usado a expressão bruta
// Usa a $base principal ou a primeira base encontrada
$base_para_apresentar = isset($base) ? $base : (isset($resultados_pagina_atual[0]['base']) ? $resultados_pagina_atual[0]['base'] : key($bd_list));
$termo_pesquisado_limpo = PresentarExpresion($base_para_apresentar); // Guarda a string limpa
// --- FIM DA PREPARAÇÃO ---

if ($total_registros > 0) {

	// 2. Chamamos a função para renderizar o cabeçalho
	// Passamos as variáveis que agora temos disponíveis
	echo renderSearchResultsHeader(
		$total_registros,
		$total_por_base,
		$bd_list,
		$msgstr,
		$termo_pesquisado_limpo // <<< Passa a string limpa
	);
} else {
	$base_para_formato = !empty($bd_list) ? key($bd_list) : "";
}

?>


<div class="d-flex flex-wrap justify-content-between align-items-center">

	<div class="col-8 col-md-auto mb-2 mb-md-0">
		<?php echo renderSortDropdown($msgstr); ?>
	</div>

	<div class="col-12 col-md-auto">
		<?php NavegarPaginas($contador, $count, $desde); ?>
	</div>
</div>


<form name="continuar" action="./" method="get">

	<input type="hidden" name="page" value="startsearch">
	<input type="hidden" name="integrada" value="">
	<input type="hidden" name="existencias">
	<input type="hidden" name="Campos" value="<?php if (isset($_REQUEST["Campos"])) echo htmlspecialchars(urldecode($_REQUEST["Campos"])); ?>">
	<input type="hidden" name="Operadores" value="<?php if (isset($_REQUEST["Operadores"])) echo htmlspecialchars(urldecode($_REQUEST["Operadores"])); ?>">
	<?php

	if (isset($actual_context) && $actual_context != "") { ?>
		<input type="hidden" name="ctx" value="<?php echo htmlspecialchars($actual_context); ?>">
	<?php }

	if (isset($_REQUEST["Sub_Expresion"])) echo '<input type="hidden" name="Sub_Expresion" value="' . htmlspecialchars(urldecode($_REQUEST["Sub_Expresion"])) . '">';
	if (isset($_REQUEST["facetas"])) echo '<input type="hidden" name="facetas" value="' . htmlspecialchars(urldecode($_REQUEST["facetas"])) . '">';
	echo '<input type="hidden" name="Expresion" value="' . htmlspecialchars($Expresion) . '">';


	// --- PRESENTATION OF RESULTS ---
	$formato_solicitado = isset($_REQUEST["Formato"]) ? $_REQUEST["Formato"] : null;



	if ($total_registros > 0) {

		// Prevents errors if pagination (‘from’) exceeds the total due to bots
		if (isset($resultados_pagina_atual[0]['base'])) {
			$base_para_formato = $resultados_pagina_atual[0]['base'];
		} else {
			// Fallback seguro caso a página atual esteja vazia
			$base_para_formato = isset($base) && $base != "" ? $base : key($bd_list);
		}

		list($select_formato, $Formato) = SelectFormato($base_para_formato, $db_path, $msgstr);

		echo '<div class="results-container" id="results">';

		// ---- START OF RESTRICTION LOGIC ----

		// Counters for the footer message
		$registros_exibidos_na_pagina = 0;
		$registros_ocultados_na_pagina = 0;

		foreach ($resultados_pagina_atual as $ix => $registro) {

			$GLOBALS['base'] = $registro['base'];

			opac_load_restriction_settings();

			$permission = opac_precheck_record($registro['base'], $registro['mfn']);

			if ($permission == 'show') {
				$base_atual = $registro['base'];
				$formato_final = ($formato_solicitado !== null) ? $formato_solicitado : getDefaultFormatForBase($base_atual, $db_path, $lang);
				ApresentarRegistroIndividual($registro['base'], $registro['mfn'], $desde + $ix, $formato_final, $Expresion, $registro['pontuacao']);

				$registros_exibidos_na_pagina++;
			} elseif ($permission == 'auth_message') {
				// Show the 'restricted' card
				ApresentarRegistroRestrito(); 
				$registros_exibidos_na_pagina++;
			} elseif ($permission == 'hidden') {
				// It doesn't matter. It doesn't show, it doesn't count.
				$registros_ocultados_na_pagina++;
			}
		}
		// ---- END OF RESTRICTION LOGIC ----

		echo '</div>'; // End of #results

		if ($registros_ocultados_na_pagina > 0) {
			$mensagem_rodape = $msgstr["front_restricted_hidden_info"] ?? "Some records may not be visible on this page due to restriction settings.";
			echo '<div class="alert alert-info" role="alert"><small>' . $mensagem_rodape . '</small></div>';
		}

		// If the entire page was filtered (all were 'hidden')
		if ($registros_exibidos_na_pagina == 0 && $registros_ocultados_na_pagina > 0) {
			echo '<p class="text-center">' . ($msgstr["front_no_visible_records_page"] ?? "There are no records to display on this page.") . '</p>';
		}
	} else {
		$base_para_formato = !empty($bd_list) ? key($bd_list) : "";
		list($select_formato, $Formato) = SelectFormato($base_para_formato, $db_path, $msgstr);
	}

	NavegarPaginas($contador, $count, $desde + 1, $select_formato);
	?>
</form>

<?php
include_once 'components/total_bases_footer.php';

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


// --- HIGHLIGHT.JS --- (O restante do arquivo continua)
if ((!isset($_REQUEST["resaltar"]) or $_REQUEST["resaltar"] == "S") && isset($Expresion) && $Expresion != '$') {

	// 1. Usamos a função PresentarExpresion para obter os termos limpos
	//    Isso transforma "(TW_maria) and (PA_...)" em "maria and Rio de Janeiro"
	$termos_para_destacar = PresentarExpresion($base); //

	// 2. Removemos aspas que podem quebrar a string JS
	$termos_para_destacar = str_replace('"', '', $termos_para_destacar);

?>
	<script language="JavaScript">
		// 3. Passamos os termos limpos para a função JS
		highlightSearchTerms("<?php echo addslashes($termos_para_destacar); ?>");
	</script>
<?php
}

include("views/float_bar.php");
include get_template_file("views/footer.php");
?>