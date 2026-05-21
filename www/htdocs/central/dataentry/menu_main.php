<?php

/**
 * Name: menu_main.php
 * Author: Roger C. Guilherme
 * Created: 2026-05-02
 * 
 * Description: This file generates the HTML and JavaScript for the main menu toolbar in the data entry interface of ABCD. It includes buttons for various actions (search, edit, import, etc.) and dropdowns for selecting display formats and navigation modes. The toolbar is designed to be responsive and user-friendly, with icons and tooltips for better usability.
 *
 * Modifications
 * 2021-01-05 fho4abcd Modified comment for button with incorrect reference. This restores button bar.
 * 2021-05-03 fho4abcd Correct header. Ensures that encoding fits with db encoding+header with DOCTYPE
 * 2021-05-03 fho4abcd Rewrite html: standardized & improved layout
 * 2021-08-02 fho4abcd Import PDF in menu bar
 * 2021-08-29 fho4abcd Modified Import PDF into Upload Document
 * 2021-12-08 fho4abcd Quick search layout + some translations + translation/modify edit pft button+removed some dividers.Code layout more readable
 * 2023-01-19 fho4abcd Menu "default" to buttons + removed unused size parameters
 * 2023-01-20 fho4abcd Use better buttons for "default".
 * 2023-01-27 fho4abcd Layout improvements+more titles. Moved code for field dropdown to inline
 * 2023-01-29 fho4abcd quick fix to make browse by menu work again
 * 2023-02-03 fho4abcd Improve browse by, add code for selected records
 * 2024-04-02 fho4abcd More translations, layout
 * 2024-06-06 fho4abcd Free search->Search, result in list
 * 2025-02-12 rogercgui Check if the.fst file exists before trying to open it
 * 2025-08-05 fho4abcd Store fst data in array (corrupted by last change), add minimal comment
 * 2026-05-07 rogercgui Replace the structure in dhtmlX with a more standard HTML/CSS layout, improving readability and maintainability. This also includes the addition of FontAwesome icons for better visual cues on the buttons. The JavaScript functions are kept inline for simplicity, but could be further modularized if needed.
 */


session_start();

if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
	die;
}

global $arrHttp, $valortag, $xFormato;
$valortag = array();
$arrHttp  = array();

include("../common/get_post.php");
require_once("../config.php");

if (!isset($_SESSION["lang"])) {
	$_SESSION["lang"] = $lang;
}

include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/lang.php");

// Determinar base de dados ativa
$db       = "";
$db_path_db = "";

if (isset($arrHttp["base"]) && $arrHttp["base"] != "") {
	$db = trim($arrHttp["base"]);
} elseif (isset($_SESSION["base"])) {
	$db = $_SESSION["base"];
} else {
	// Fallback: pegar a primeira base do permiso de sessão
	foreach ($_SESSION["permiso"] as $key => $value) {
		if (substr($key, 0, 3) == "db_") {
			$db = substr($key, 3);
			break;
		}
	}
}

$db_path_db = $db_path . $db . "/";
$lang_sess  = $_SESSION["lang"];

// Carregar FST para processamento dos campos de busca rápida
$fst = array();
$fstfile = $db_path_db . "data/" . $db . ".fst";
if (file_exists($fstfile)) {
	$fst = file($fstfile);
}

// Verificar se tesauro existe para esta base 
$tesaurus = null;
$tesfile  = $db_path_db . "def/" . $lang_sess . "/tesaurus.tab";
if (file_exists($tesfile)) {
	$tesaurus = true;
}

// Carregar definição da base (def_db)
$def_db = array();
$deffile = $db_path_db . "def/" . $db . ".def";
if (!file_exists($deffile)) {
	$deffile = $db_path_db . "def/abcd.def";
}
if (file_exists($deffile)) {
	$lines = file($deffile);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line == "" || substr($line, 0, 1) == "#") continue;
		$parts = explode("=", $line, 2);
		if (count($parts) == 2) {
			$def_db[trim($parts[0])] = trim($parts[1]);
		}
	}
}


/*
 * The formats are read from prologoact.tab in the database's PFT folder.
 * File format: value|label  (one entry per line)
 * If the file does not exist, the select will default to “ALL”.
 */

// --- READING THE FORMATS (.dat) ---
$fp_formatos = array();
$path_fmt = $db_path . $arrHttp["base"] . "/pfts/" . $_SESSION["lang"] . "/formatos.dat";
if (!file_exists($path_fmt)) $path_fmt = $db_path . $arrHttp["base"] . "/pfts/" . $lang_db . "/formatos.dat";
if (file_exists($path_fmt)) $fp_formatos = file($path_fmt);

// --- READING THE WORKSHEETS (.wks) ---
$fp_wks = array();
$path_wks = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/formatos.wks";
if (!file_exists($path_wks)) $path_wks = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/formatos.wks";
if (file_exists($path_wks)) $fp_wks = file($path_wks);

// --- VERIFICAR TIPOS DE REGISTRO (typeofrecord.tab) ---
$typeofrecord = "";
$path_tor = $db_path_db . "def/" . $_SESSION["lang"] . "/typeofrecord.tab";
if (!file_exists($path_tor)) {
	$path_tor = $db_path_db . "def/" . $lang_db . "/typeofrecord.tab";
}
if (file_exists($path_tor)) {
	$typeofrecord = "S"; // Sinaliza que o arquivo existe
}

if (empty($formatos)) {
	// Fallback: at least “ALL” is always available (default value in inicio_main.php)
	$formatos[] = array("val" => "ALL", "label" => "ALL");
}

/*
 * Worksheets são os arquivos .wkf (ou listados em worksheets.tab).
 * Se nenhum arquivo de lista existir, varre os arquivos .wkf na pasta de PFTs.
 */
$worksheets = array();
$wksfile    = $db_path_db . "pfts/" . $lang_sess . "/worksheets.tab";
if (!file_exists($wksfile)) {
	$wksfile = $db_path_db . "pfts/" . $lang_db . "/worksheets.tab";
}
if (file_exists($wksfile)) {
	foreach (file($wksfile) as $line) {
		$line = trim($line);
		if ($line == "" || substr($line, 0, 1) == "#") continue;
		$parts = explode("|", $line, 2);
		if (count($parts) == 2) {
			$worksheets[] = array("val" => trim($parts[0]), "label" => trim($parts[1]));
		}
	}
} else {
	// Fallback: varredura de arquivos .wkf na pasta de PFTs
	$wks_dir = $db_path_db . "pfts/" . $lang_sess . "/";
	if (is_dir($wks_dir)) {
		foreach (glob($wks_dir . "*.wkf") as $wkf) {
			$name = basename($wkf, ".wkf");
			$worksheets[] = array("val" => $name, "label" => $name);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_sess); ?>">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet"
		href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
		crossorigin="anonymous" referrerpolicy="no-referrer">

	<style>
		/* Reset mínimo */
		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		/*
   * Setting `overflow: hidden` on the `body` element is MANDATORY.
   * Without it, the iframe's `scrollHeight` may exceed the expected ~95px
   * when the operating system renders a scrollbar (an extra 15–17px),
   * which corrupts the `RecalculateLayout()` calculation in inicio_main.php.
   */
		body {
			overflow: hidden;
			font-family: sans-serif;
		}

		/* Main toolbar container */
		.abcd-toolbar-container {
			display: flex;
			flex-direction: column;
			background-color: #f8f9fa;
			border-bottom: 1px solid #ddd;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
			padding: 6px 12px;
		}

		/* Each horizontal line on the toolbar */
		.abcd-toolbar-row {
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 3px 0;
		}

		/* Grupo de controles: label + input/select */
		.abcd-toolbar-group {
			display: flex;
			align-items: center;
			gap: 6px;
			flex-shrink: 0;
		}

		.abcd-toolbar-group label {
			font-size: 11px;
			color: #555;
			font-weight: 600;
			white-space: nowrap;
		}

		.abcd-toolbar-group input[type="text"],
		.abcd-toolbar-group select {
			padding: 3px 7px;
			border: 1px solid #ced4da;
			border-radius: 4px;
			font-size: 11px;
			color: #333;
			height: 26px;
			background-color: #fff;
		}

		.abcd-toolbar-group input[type="text"]:focus,
		.abcd-toolbar-group select:focus {
			outline: none;
			border-color: #86b7fe;
			box-shadow: 0 0 0 2px rgba(13, 110, 253, .15);
		}

		/* Grupo de botões */
		.abcd-toolbar-buttons {
			display: flex;
			align-items: center;
			gap: 2px;
			flex-wrap: nowrap;
			/* Nunca quebra linha — mantém altura estável */
		}

		/* Botão individual */
		.btn-toolbar {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			background: transparent;
			border: 1px solid transparent;
			border-radius: 4px;
			padding: 4px 7px;
			cursor: pointer;
			color: #495057;
			transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
			min-width: 32px;
			height: 32px;
			flex-shrink: 0;
			/* Nunca encolhe — evita deformação em telas estreitas */
		}

		.btn-toolbar:hover:not(:disabled) {
			background-color: #e2e6ea;
			border-color: #adb5bd;
			color: #0056b3;
		}

		.btn-toolbar:active:not(:disabled) {
			background-color: #d3d9df;
			transform: scale(0.96);
		}

		.btn-toolbar i {
			font-size: 15px;
			pointer-events: none;
			/* Evita que o ícone intercepte o onclick do botão */
		}

		/* Botão azul (links de ação secundária) */
		.btn-toolbar-blue {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			color: #0d6efd;
			background: transparent;
			border: 1px solid transparent;
			border-radius: 4px;
			height: 26px;
			padding: 0 7px;
			cursor: pointer;
			text-decoration: none;
			transition: background-color 0.15s ease, border-color 0.15s ease;
			flex-shrink: 0;
		}

		.btn-toolbar-blue:hover {
			background-color: #e7f0ff;
			border-color: #86b7fe;
		}

		.btn-toolbar-blue i {
			font-size: 14px;
			pointer-events: none;
		}

		/* Separador vertical entre grupos de botões */
		.toolbar-divider {
			width: 1px;
			height: 20px;
			background-color: #ced4da;
			margin: 0 4px;
			flex-shrink: 0;
		}

		/* Select de modo de navegação (browseby) */
		#browseby {
			height: 26px;
			font-size: 11px;
			border: 1px solid #ced4da;
			border-radius: 4px;
			padding: 0 4px;
			background-color: #fff;
			cursor: pointer;
			margin: 0 4px;
			flex-shrink: 0;
		}

		/*
   * Em monitores com largura < 900px, esconder os rótulos de texto dos
   * grupos de controles preserva o layout sem scroll horizontal,
   * mantendo a altura do iframe estável.
   */
		@media (max-width: 900px) {
			.abcd-toolbar-group label {
				display: none;
			}
		}
	</style>
</head>

<body>

	<script src="js/lr_trim.js?<?php echo time(); ?>"></script>
	<script>
		/*
		 *================================================================================
		 *  LOCAL FUNCTIONS IN menu_main.php
		 *
		 *  All of the functions below operate INSIDE the iframe#menu.
		 *  To communicate with the parent frame (inicio_main.php), they use the prefix “top.”.
		 *================================================================================
		 */

		/**
		 * FocoEn(field)
		 * Updates the “ep” (entry point) variable in the parent frame, indicating
		 * which search field is active. Used by search_words and blibre.
		 * @param {string} field — ‘ira’, ‘blibre’, or another identifier.
		 */

		function FocoEn(campo) {
			try {
				top.ep = campo;
			} catch (e) {
				/* Seguro se o pai ainda não carregou */
			}
		}

		/**
		 * GenerarDespliegue()
		 * Called when the “format” dropdown changes.
		 * Updates top.Format and reloads the current record with the new format,
		 * but only if a record is already being displayed.
		 */
		function GenerarDespliegue() {
			var sel = document.forma1.formato;
			if (!sel || sel.selectedIndex < 0) return;
			var novoFormato = sel.options[sel.selectedIndex].value;
			try {
				top.Formato = novoFormato;
				/* Recarrega somente se há um registro ativo (mfn > 0 ou busca ativa) */
				if (top.mfn > 0 || top.Mfn_Search > 0) {
					top.Menu('ver');
				}
			} catch (e) {}
		}

		/**
		 * GenerarWks()
		 * Called when the “wks” (worksheet) selection changes.
		 * Updates top.wks, which is read by Menu() in inicio_main.php.
		 */
		function GenerarWks() {
			var sel = document.forma1.wks;
			if (!sel || sel.selectedIndex < 0) return;
			try {
				top.wks = sel.options[sel.selectedIndex].value;
			} catch (e) {}
		}

		/**
		 * onButtonClick(tipo, valor)
		 * Manages the browseby mode selection.
		 * “undo_selected” is a pseudo-option: clears the selection and returns to MFN.
		 * @param {string} type  — always ‘browseby’ in the current toolbar.
		 * @param {string} value — ‘mfn’ | ‘search’ | ‘selected_records’ | 'undo_selected'
		 */
		function onButtonClick(tipo, valor) {
			if (tipo !== 'browseby') return;
			try {
				if (valor === 'undo_selected') {
					top.RegistrosSeleccionados = '';
					top.browseby = 'mfn';
					/* Devolve o select visualmente para "Mfn" */
					document.getElementById('browseby').value = 'mfn';
				} else {
					top.browseby = valor;
				}
			} catch (e) {}
		}

		/**
		 * Diccionario()
		 * Abre a janela de índice/dicionário para o campo de busca rápida selecionado.
		 * Extrai o prefixo do campo selecionado em "blibre" e delega ao Menu('alfa').
		 */
		function Diccionario() {
			try {
				var sel = document.forma1.blibre;
				if (sel && sel.selectedIndex >= 0) {
					var rawVal = sel.options[sel.selectedIndex].value;
					/* Remove sufixo '|W' ou '|' que indicam tipo de índice no FST */
					var prefijo = rawVal.replace(/\|W$/, '').replace(/\|$/, '');
					top.prefijo_indice = prefijo;
				}
				top.Menu('alfa');
			} catch (e) {}
		}

		/**
		 * AbrirAyuda()
		 * Delega para a função de ajuda definida em inicio_main.php.
		 * NÃO chama window.open diretamente — evita duplicação de lógica.
		 */
		function AbrirAyuda() {
			try {
				top.AbrirVentanaAyuda();
			} catch (e) {}
		}

		function EditarFormato() {
			i = document.forma1.formato.selectedIndex
			if (i == -1) {} else {
				pft = document.forma1.formato.options[i].value
				descripcion = document.forma1.formato.options[i].text
				if (pft != 'ALL') {
					document.editpft.base.value = top.base
					document.editpft.cipar.value = top.base + ".par";
					document.editpft.archivo.value = pft
					document.editpft.descripcion.value = descripcion
					msgwin = window.open("", "editpft", "width=800, height=400, scrollbars, resizable")
					document.editpft.submit()
					msgwin.focus()
				} else {

				}
			}
		}
	</script>

	<form name="forma1" method="post" onsubmit="return false">

		<div class="abcd-toolbar-container">
			<div class="abcd-toolbar-row">

				<div class="abcd-toolbar-group">
					<label for="ir_a_field"><?php echo $msgstr["m_ir"]; ?></label>

					<input type="text"
						id="ir_a_field"
						name="ir_a"
						size="10"
						value=""
						title="<?php echo htmlspecialchars($msgstr["m_typerecno"] . " → " . $msgstr["src_enter"]); ?>"
						onfocus="FocoEn('ira')"
						onclick="this.value=''"
						onkeypress="if(event.keyCode==13||event.which==13){top.Menu('ira');return false;}">
				</div>

				<?php
				$camposbusqueda_file = $db_path_db . "pfts/" . $lang_sess . "/camposbusqueda.tab";
				if (!file_exists($camposbusqueda_file)) {
					$camposbusqueda_file = $db_path_db . "pfts/" . $lang_db . "/camposbusqueda.tab";
				}
				if (file_exists($camposbusqueda_file)):
					$fpb = file($camposbusqueda_file);
				?>
					<div class="abcd-toolbar-group">
						<label for="blibre"><?php echo $msgstr["m_searchby"]; ?></label>
						<select id="blibre"
							name="blibre"
							onchange="document.forma1.busqueda_palabras.value=''"
							title="<?php echo htmlspecialchars($msgstr["selcampob"]); ?>">
							<?php
							foreach ($fpb as $value) {
								$value = trim($value);
								if ($value == "") continue;
								$y = explode('|', $value);
								$y[2] = isset($y[2]) ? trim($y[2]) : "";
								/* Detecta se o campo tem indexação por palavras (tipo 8 no FST) */
								foreach ($fst as $linea) {
									if ($y[2] != "" && stripos($linea, $y[2]) !== false) {
										$y[2] = $y[2] . '|';
										$linea = str_replace("  ", " ", $linea);
										$it = explode(" ", trim($linea));
										if (isset($it[1]) && $it[1] == 8) {
											$y[2] .= 'W';
										}
										break;
									}
								}
								$label = isset($y[0]) ? trim($y[0]) : "";
								$val   = trim($y[2]);
								echo "<option value=\"{$val}\">{$label}</option>\n";
							}
							unset($fpb);
							?>
						</select>

						<!-- Ícone de abertura do dicionário/índice -->
						<a href="javascript:Diccionario()"
							class="btn-toolbar-blue"
							title="<?php echo htmlspecialchars($msgstr["m_quicksrcwith"]); ?>">
							<i class="fab fa-searchengin"></i>
						</a>

						<!-- Campo de termos para busca rápida -->
						<input type="text"
							name="busqueda_palabras"
							style="width:180px"
							value=""
							onfocus="FocoEn('blibre')"
							title="<?php echo htmlspecialchars($msgstr["m_enterterms"]); ?>"
							onkeypress="if(event.keyCode==13||event.which==13){top.Menu('ejecutarbusqueda');return false;}">
					</div>
				<?php endif; /* camposbusqueda.tab */ ?>

				<!-- Formato de exibição + botão de edição de formato (se com permissão) ─ -->
				<div class="abcd-toolbar-group" style="margin-left:auto;">
					<label for="fmt_select"><?php echo $msgstr["displaypft"]; ?></label>
					<select name="formato" onchange="GenerarDespliegue()">
						<?php
						foreach ($fp_formatos as $line) {
							$line = trim($line);
							if ($line != "") {
								$f = explode('|', $line);
								$cod = $f[0];
								$nom = $f[1];
								$selected = (isset($arrHttp["formato"]) && $arrHttp["formato"] == $cod) ? " selected" : "";
								echo "<option value=\"$cod\"$selected>$nom</option>\n";
							}
						}
						?>
						<option value=""><?php echo $msgstr["all"] ?></option>
						<option value="ALL"><?php echo $msgstr["noformat"] ?></option>
					</select>

					<?php
					/* Botão de editar formato: visível apenas para usuários com permissão */
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])      ||
						isset($_SESSION["permiso"]["CENTRAL_EDPFT"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])  ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_EDPFT"])
					): ?>
						<a class="btn-toolbar-blue" href="javascript:EditarFormato()">
							<i class="fas fa-edit" alt="edit display format" title="<?php echo $msgstr["m_editdispform"]; ?>"></i>
						</a>
					<?php endif; ?>
				</div>

			</div><!-- /.abcd-toolbar-row (linha 1) -->


			<div class="abcd-toolbar-row" style="justify-content:space-between;">

				<div class="abcd-toolbar-buttons">

					<!-- Navegação de registros -->
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('primero')"
						title="<?php echo htmlspecialchars($msgstr["m_primero"]); ?>">
						<i class="fas fa-step-backward"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('anterior')"
						title="<?php echo htmlspecialchars($msgstr["m_anterior"]); ?>">
						<i class="fas fa-backward"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('proximo')"
						title="<?php echo htmlspecialchars($msgstr["m_siguiente"]); ?>">
						<i class="fas fa-forward"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('ultimo')"
						title="<?php echo htmlspecialchars($msgstr["m_ultimo"]); ?>">
						<i class="fas fa-step-forward"></i>
					</button>

					<!-- Modo de navegação (browseby) -->
					<select id="browseby"
						onchange="onButtonClick('browseby', this.value)"
						title="<?php echo htmlspecialchars($msgstr["m_browseby"] ?? "Modo de navegação"); ?>">
						<option value="mfn" selected>Mfn</option>
						<option value="search"><?php echo htmlspecialchars($msgstr["busqueda"]); ?></option>
						<option value="selected_records"><?php echo htmlspecialchars($msgstr["selected_records"]); ?></option>
						<option value="undo_selected"><?php echo htmlspecialchars($msgstr["undo_selected"]); ?></option>
					</select>

					<div class="toolbar-divider"></div>

					<!-- Busca -->
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('buscar')"
						title="<?php echo htmlspecialchars($msgstr["m_buscar"]); ?>">
						<i class="fas fa-search"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.SearchHistory()"
						title="<?php echo htmlspecialchars($msgstr["m_history"]); ?>">
						<i class="fas fa-clipboard-list"></i>
					</button>
					<?php if (isset($tesaurus)): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Tesaurus()"
							title="<?php echo htmlspecialchars($msgstr["m_tesaurussrc"]); ?>">
							<i class="fas fa-book"></i>
						</button>
					<?php endif; ?>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('busquedalibre')"
						title="<?php echo htmlspecialchars($msgstr["freesearch_title"]); ?>">
						<i class="fas fa-database"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('alfa')"
						title="<?php echo htmlspecialchars($msgstr["m_indiceaz"]); ?>">
						<i class="fas fa-sort-alpha-down"></i>
					</button>

					<div class="toolbar-divider"></div>

					<!-- Criação de registros (com verificação de permissão) -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])     ||
						isset($_SESSION["permiso"]["CENTRAL_CREC"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_CREC"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('nuevo')"
							title="<?php echo htmlspecialchars($msgstr["m_crear"]); ?>">
							<i class="fas fa-file-medical"></i>
						</button>
					<?php endif; ?>

					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])        ||
						isset($_SESSION["permiso"]["CENTRAL_CAPTURE"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_CAPTURE"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('capturar_bd')"
							title="<?php echo htmlspecialchars($msgstr["m_capturar"]); ?>">
							<i class="fas fa-copy"></i>
						</button>
					<?php endif; ?>

					<?php
					/* Importar documento: só aparece se a base tem COLLECTION definida */
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])     ||
						isset($_SESSION["permiso"]["CENTRAL_CREC"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_CREC"])
					):
						$collection = isset($def_db["COLLECTION"]) ? trim($def_db["COLLECTION"]) : "";
						if ($collection != ""): ?>
							<button type="button" class="btn-toolbar"
								onclick="top.Menu('importarDoc')"
								title="<?php echo htmlspecialchars($msgstr["dd_upload"]); ?>">
								<i class="fas fa-file-upload"></i>
							</button>
					<?php endif;
					endif; ?>

					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])          ||
						isset($_SESSION["permiso"]["CENTRAL_Z3950CAT"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"])   ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_Z3950CAT"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('z3950')"
							title="<?php echo htmlspecialchars($msgstr["m_z3950"]); ?>">
							<i class="fas fa-download"></i>
						</button>
					<?php endif; ?>

					<div class="toolbar-divider"></div>

					<!-- Valores padrão -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])        ||
						isset($_SESSION["permiso"]["CENTRAL_VALDEF"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_VALDEF"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('editdv')"
							title="<?php echo htmlspecialchars($msgstr["editar"] . ' ' . $msgstr["valdef"]); ?>">
							<i class="fas fa-tasks"></i>
						</button>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('deletedv')"
							title="<?php echo htmlspecialchars($msgstr["eliminar"] . ' ' . $msgstr["valdef"]); ?>">
							<i class="fas fa-eraser"></i>
						</button>
						<div class="toolbar-divider"></div>
					<?php endif; ?>

					<!-- Código de barras -->
					<?php
					if ((isset($_SESSION["permiso"]["CENTRAL_ALL"])       ||
							isset($_SESSION["permiso"]["CENTRAL_BARCODE"])   ||
							isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
							isset($_SESSION["permiso"][$db . "_CENTRAL_BARCODE"])) &&
						(isset($_SESSION["BARCODE"]) || isset($_SESSION["BARCODE_SIMPLE"]))
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('barcode')"
							title="Barcode">
							<i class="fas fa-barcode"></i>
						</button>
					<?php endif; ?>

					<!-- Imprimir / Relatórios -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])      ||
						isset($_SESSION["permiso"]["CENTRAL_PREC"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_PREC"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('imprimir')"
							title="<?php echo htmlspecialchars($msgstr["m_reportes"]); ?>">
							<i class="fas fa-print"></i>
						</button>
					<?php endif; ?>

					<!-- Administrar -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])       ||
						isset($_SESSION["permiso"]["CENTRAL_UTILS"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_UTILS"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('administrar')"
							title="<?php echo htmlspecialchars($msgstr["mantenimiento"]); ?>">
							<i class="fas fa-cogs"></i>
						</button>
					<?php endif; ?>

					<!-- Atualizar base -->
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('refresh_db')"
						title="<?php echo htmlspecialchars($msgstr["refresh_db"]); ?>">
						<i class="fas fa-sync-alt"></i>
					</button>

					<div class="toolbar-divider"></div>

					<!-- Estatísticas -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])        ||
						isset($_SESSION["permiso"]["CENTRAL_STATGEN"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_STATGEN"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('stats')"
							title="<?php echo htmlspecialchars($msgstr["estadisticas"]); ?>">
							<i class="fas fa-chart-bar"></i>
						</button>
					<?php endif; ?>

					<!-- Ajuda -->
					<button type="button" class="btn-toolbar"
						onclick="AbrirAyuda()"
						title="<?php echo htmlspecialchars($msgstr["m_ayuda"]); ?>">
						<i class="fas fa-question-circle"></i>
					</button>

					<!-- Home -->
					<button type="button" class="btn-toolbar"
						style="color:#005aa9;"
						onclick="top.Menu('home')"
						title="<?php echo htmlspecialchars($msgstr["inicio"]); ?>">
						<i class="fas fa-home"></i>
					</button>

				</div><!-- /.abcd-toolbar-buttons -->



				<!-- Planilha (worksheet) — agora lado a lado com os botões -->
				<div class="abcd-toolbar-group">
					<label><?php echo $msgstr["fmt"] ?> </label>
					<select name="wks" onchange="GenerarWks()">
						<option value=""></option>
						<?php
						foreach ($fp_wks as $line) {
							$line = trim($line);
							if ($line != "") {
								$f = explode('|', $line);
								$cod = $f[0];
								$nom = $f[1];
								echo "<option value=\"$cod\">$nom</option>\n";
							}
						}
						?>
					</select>
				</div>





			</div><!-- /.abcd-toolbar-row (linha 2) -->

		</div>
	</form>

	<script>
		// Synchronizes the main frame when the toolbar finishes loading
		window.addEventListener('load', function() {
			top.typeofrecord = "<?php echo $typeofrecord; ?>";

			<?php if (isset($arrHttp["inicio"]) && $arrHttp["inicio"] == "s"): ?>
				top.main.location.href = "inicio_base.php?inicio=s&base=" + top.base + "&cipar=" + top.cipar + "&per=" + top.db_permiso;
			<?php elseif (!isset($arrHttp["reload"])): ?>
				if (top.main && top.main.location) {
					var currentUrl = top.main.location.href;
					if (currentUrl && currentUrl.indexOf('about:blank') === -1) {
						top.main.location.href = currentUrl;
					}
				}
			<?php endif; ?>
		});
	</script>

	<form name="editpft" method="post" action="../dbadmin/leertxt.php" target="editpft">
		<input type="hidden" name="desde" value="dataentry">
		<input type="hidden" name="base">
		<input type="hidden" name="cipar">
		<input type="hidden" name="archivo">
		<input type="hidden" name="descripcion">
	</form>

</body>

</html>