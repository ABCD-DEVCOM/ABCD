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

// --- INCLUSÃO DOS MÓDULOS DE BUSCA (FASE 2) ---
require_once $Web_Dir . 'includes/search/expression_builder.php';
require_once $Web_Dir . 'includes/search/wxis_totals.php';
require_once $Web_Dir . 'includes/search/format_selector.php';
require_once $Web_Dir . 'includes/search/relevance_scoring.php';
require_once $Web_Dir . 'includes/search/direct_search.php';
require_once $Web_Dir . 'includes/search/organized_search.php';


// --- CAPTCHA VERIFICATION ---
if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y' && isset($opac_gdef['CAPTCHA_SECRET_KEY'])) {
	// Verifica o POST, mas isenta requisições internas de UI (como a troca para a Busca Avançada)
	if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST['search_form'])) {
		if (!validarCaptchaCloudflare($opac_gdef['CAPTCHA_SECRET_KEY'])) {
			echo "<h1>Validation Error</h1><p>The CAPTCHA verification failed. Please try again.</p>";
			echo "<a href='javascript:history.back()'>Back</a>";
			die();
		}
	}
}

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
	//error_log("<!-- Busca Direta executada devido à truncagem -->\n");
} else {
	// SE NÃO TEM TRUNCAGEM: Executa Busca por Relevância (Google-like)
	// Limpa termos, pontua ocorrências e ordena os melhores resultados.
	$resultados_ordenados = searchAndOrganizeResults($lista_para_busca, $db_path, $Expresion, $termo_livre, $Expr_facetas);
	//error_log("<!-- Busca por Relevância executada -->\n");
}


// 2. O total de registros é a contagem real vinda do WXIS (Etapa 1)
$total_registros = isset($GLOBALS['REAL_TOTAL_GERAL']) ? $GLOBALS['REAL_TOTAL_GERAL'] : count($resultados_ordenados);
$contador = $total_registros;

// 3. Calculamos o total por base usando os valores reais do WXIS
if (isset($GLOBALS['REAL_TOTAL_BASE']) && !empty($GLOBALS['REAL_TOTAL_BASE'])) {
	$total_por_base = $GLOBALS['REAL_TOTAL_BASE'];
} else {
	// Fallback de segurança se as variáveis globais não existirem
	$total_por_base = [];
	foreach ($resultados_ordenados as $registro) {
		$base_do_registro = $registro['base'];
		if (!isset($total_por_base[$base_do_registro])) {
			$total_por_base[$base_do_registro] = 0;
		}
		$total_por_base[$base_do_registro]++;
	}
}

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

<!-- INÍCIO DO COLLAPSE DOS RESULTADOS -->
<div class="collapse show" id="collapseSearchResults">

	<div class="d-flex flex-wrap justify-content-between align-items-center">

		<div class="col-8 col-md-auto mb-2 mb-md-0">
			<?php echo renderSortDropdown($msgstr); ?>
		</div>

		<div class="col-12 col-md-auto">
			<?php NavegarPaginas($contador, $count, $desde); ?>
		</div>
	</div> <!--align-items-center-->


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

			if (isset($GLOBALS['RELEVANCE_GATE_TRIGGERED']) && $GLOBALS['RELEVANCE_GATE_TRIGGERED'] > 0) {
				// 1. Formata o número primeiro
				$numero_formatado = number_format($GLOBALS['RELEVANCE_GATE_TRIGGERED'], 0, ',', '.');

				// 2. Usa sprintf para injetar o número formatado no lugar do "%s"
				$msg_aviso_teto = isset($msgstr["front_relevance_gate_warning"])
					? sprintf($msgstr["front_relevance_gate_warning"], $numero_formatado)
					: sprintf("Results sorted by MFN/Date due to high volume (%s records). Refine your search with keywords to enable relevance ranking.", $numero_formatado);

				echo '<div class="alert alert-info mt-2" role="alert"><small><i class="fas fa-info-circle"></i> ' . $msg_aviso_teto . '</small></div>';
			}

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

			// Abre a sanfona automaticamente se a busca retornar 0 resultados
			echo "<script>
						document.addEventListener('DOMContentLoaded', function() { 
							var sf = document.getElementById('collapseSearchForm'); 
							if(sf) sf.classList.add('show'); 
						});
					</script>";
		}

		NavegarPaginas($contador, $count, $desde + 1, $select_formato);
		?>
	</form>

</div> <!-- FIM DO COLLAPSE DOS RESULTADOS -->

<script>
	document.addEventListener('DOMContentLoaded', function() {
		var totalRegistros = <?php echo $total_registros; ?>;
		var formEl = document.getElementById('collapseSearchForm');
		var resultsEl = document.getElementById('collapseSearchResults');

		// Detecta se o usuário clicou no botão "Búsqueda avanzada" (que dispara um POST na troca de forms)
		var isSwitchingMode = <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_form'])) ? 'true' : 'false'; ?>;

		// Se a busca deu 0 ou o usuário acabou de trocar para a busca avançada, exibe o form e oculta os resultados
		if (totalRegistros === 0 || isSwitchingMode) {
			if (formEl) formEl.classList.add('show');
			if (resultsEl) resultsEl.classList.remove('show');
		}
	});

	// Função que alterna perfeitamente entre exibir o Formulário ou exibir os Resultados
	function toggleSearchPanels() {
		var formEl = document.getElementById('collapseSearchForm');
		var resultsEl = document.getElementById('collapseSearchResults');

		if (!formEl || !resultsEl) return;

		var isFormOpen = formEl.classList.contains('show');

		if (isFormOpen) {
			// Se o Form está aberto, esconde o Form e mostra os Resultados
			new bootstrap.Collapse(formEl, {
				toggle: false
			}).hide();
			new bootstrap.Collapse(resultsEl, {
				toggle: false
			}).show();
		} else {
			// Se o Form está fechado, mostra o Form e esconde os Resultados
			new bootstrap.Collapse(formEl, {
				toggle: false
			}).show();
			new bootstrap.Collapse(resultsEl, {
				toggle: false
			}).hide();
		}
	}
</script>

<?php
include_once 'components/total_bases_footer.php';

// Chama o módulo procedural de Sugestões de Busca ("Você quis dizer")
include $Web_Dir . 'includes/search/suggestions.php';


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