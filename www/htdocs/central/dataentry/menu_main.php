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
 * 2026-05-23 rogercgui Added critical fix to translate the %path_database% macro in dr_path.def to the actual system path, ensuring that folder creation works correctly regardless of the environment. This resolves a major issue where the newfolder.php script was creating directories in the wrong location due to an unresolved macro in the path definition.
 * 2026-05-28 rogercgui Added a critical fix to the onButtonClick function to prevent unintended side effects when programmatically changing the "browseby" selection. This ensures that updates to the selection made by the system (e.g., after a search) do not trigger the user's click action, which could lead to confusion or errors in navigation. The function now updates the selection without calling onButtonClick recursively, maintaining the integrity of the user experience.
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
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../lang/lang.php");

// Set active database based on GET parameter, session variable, or permissions
$db       = "";
$db_path_db = "";

if (isset($arrHttp["base"]) && $arrHttp["base"] != "") {
	$db = trim($arrHttp["base"]);
} elseif (isset($_SESSION["base"])) {
	$db = $_SESSION["base"];
} else {
	// Fallback: retrieve the primary key of the session permits that starts with "db_"
	foreach ($_SESSION["permiso"] as $key => $value) {
		if (substr($key, 0, 3) == "db_") {
			$db = substr($key, 3);
			break;
		}
	}
}

$db_path_db = $db_path . $db . "/";
$lang_sess  = $_SESSION["lang"];

// Load FST to process the quick search fields
$fst = array();
$fstfile = $db_path_db . "data/" . $db . ".fst";
if (file_exists($fstfile)) {
	$fst = file($fstfile);
}

// Check whether a thesaurus exists for this database 
$tesaurus = null;
$tesfile  = $db_path_db . "def/" . $lang_sess . "/tesaurus.tab";
if (file_exists($tesfile)) {
	$tesaurus = true;
}

// Load database definition (def_db)
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

// Load dr_path.def and merge it with the global variable $def_db
$dr_path_file = $db_path_db . "dr_path.def";
if (file_exists($dr_path_file)) {
	$dr_path_def = @parse_ini_file($dr_path_file);
	if (is_array($dr_path_def)) {
		$def_db = array_merge($def_db, $dr_path_def);
	}
}



/*
 * The formats are read from prologoact.tab in the database's PFT folder.
 * File format: value|label  (one entry per line)
 * If the file does not exist, the select will default to "ALL".
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

// --- CHECK RECORD TYPES (typeofrecord.tab) ---
$typeofrecord = "";
$path_tor = $db_path_db . "def/" . $_SESSION["lang"] . "/typeofrecord.tab";
if (!file_exists($path_tor)) {
	$path_tor = $db_path_db . "def/" . $lang_db . "/typeofrecord.tab";
}
if (file_exists($path_tor)) {
	$typeofrecord = "S"; // Sinaliza que o arquivo existe
}

if (empty($formatos)) {
	// Fallback: at least "ALL" is always available (default value in inicio_main.php)
	$formatos[] = array("val" => "ALL", "label" => "ALL");
}

/*
 * Worksheets are the .wkf files (or those listed in worksheets.tab).
 * If no list file exists, scan the .wkf files in the PFTs folder.
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

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet"
		href="/assets/css/all.min.css"
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
   * On screens wider than 900px, hiding the text labels for
   * control groups preserves the layout without horizontal scrolling,
   * keeping the iframe height consistent.
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
		 *  To communicate with the parent frame (inicio_main.php), they use the prefix "top.".
		 *================================================================================
		 */

		// ======================================================================
		// SHIM (Polyfill) for compatibility with legacy ABCD scripts that expect a global "selectobj" and "toolbar" object.
		// ======================================================================
		var selectobj = {
			setSelected: function(valor) {
				var sel = document.getElementById('browseby');
				if (sel) {
					sel.value = valor;
					// CORRECTION: Do not call onButtonClick("browseby", value) here.
					// Programmatic updates (made by the system following a search) 
					// should not trigger the user's click action.
				}
			}
		};

		var toolbar = {
			disableItem: function(itemId) {
				var btn = document.getElementById('btn_' + itemId);
				if (btn) {
					btn.style.opacity = '0.4';
					btn.style.pointerEvents = 'none';
				}
			},
			enableItem: function(itemId) {
				var btn = document.getElementById('btn_' + itemId);
				if (btn) {
					btn.style.opacity = '1';
					btn.style.pointerEvents = 'auto';
				}
			},
			setItemText: function(id, text) {},
			showItem: function(id) {},
			hideItem: function(id) {},
			getItem: function(itemId) {
				if (itemId === 'browseby') {
					return {
						selElement: document.getElementById('browseby')
					};
				}
				return null;
			}
		};

		// Compatibility alias for any direct pop-up calls
		function Buscar(Prefijo) {
			BuscarPalabras();
		}


		/**
		 * FocoEn(field)
		 * Updates the "ep" (entry point) variable in the parent frame, indicating
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
		 * Called when the "format" dropdown changes.
		 * Updates top.Format and reloads the current record with the new format,
		 * but only if a record is already being displayed.
		 */
		function GenerarDespliegue() {
			var sel = document.forma1.formato;
			if (!sel || sel.selectedIndex < 0) return;
			var novoFormato = sel.options[sel.selectedIndex].value;
			try {
				top.Formato = novoFormato;
				/* Reload only if there is an active record (mfn > 0 or active search) */
				if (top.mfn > 0 || top.Mfn_Search > 0) {
					top.Menu('ver');
				}
			} catch (e) {}
		}

		/**
		 * GenerarWks()
		 * Called when the "wks" (worksheet) selection changes.
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
		 * "undo_selected" is a pseudo-option: clears the selection and returns to MFN.
		 * @param {string} type  — always 'browseby' in the current toolbar.
		 * @param {string} value — 'mfn' | 'search' | 'selected_records' | 'undo_selected'
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

				// ABCD FIX: The system's original trigger to force the screen to reload 
				// whilst retaining the current record, but applying the new "browseby".
				if (top.mfn > 0 || top.Mfn_Search > 0) {
					top.Menu('same');
				}
			} catch (e) {
				console.error("Erro ao alterar modo de navegação:", e);
			}
		}

		/**
		 * Diccionario()
		 * Opens the dictionary window for the quick search field selected via POST.
		 */
		function Diccionario() {
			var sel = document.forma1.blibre;
			if (!sel || sel.selectedIndex < 0) return;

			var rawVal = sel.options[sel.selectedIndex].value;
			var label = sel.options[sel.selectedIndex].text;

			// ABCD stores the value in the format 'PREFIX|W' or 'PREFIX|', so we split by '|' to extract the prefix for the dictionary search.
			var p = rawVal.split('|');
			var prefijo = p[0];

			// Prepare the empty window BEFORE submitting (so that the target matches)
			var msgwin = window.open("", "Diccionario", "status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=750,height=400,top=10,left=100");
			msgwin.focus();

			// Populate the hidden form and submit it
			document.diccio.base.value = top.base;
			document.diccio.cipar.value = top.cipar; // Using the exact global variable from the parent frame
			document.diccio.prefijo.value = prefijo;
			document.diccio.campo.value = label;
			document.diccio.submit();
		}

		/**
		 * AbrirAyuda()
		 * Delegates to the help function defined in inicio_main.php.
		 * Does NOT call window.open directly — this avoids duplication of code.
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
		/**
		 * BuscarPalabras()
		 * Constructs the search query based on the selected prefix and the terms entered.
		 * Checks whether the field is set to 'Word' (|W) to split the terms or use the exact phrase.
		 */
		function BuscarPalabras() {
			var sel = document.forma1.blibre;
			if (!sel || sel.selectedIndex < 0) return false;

			var term = document.forma1.busqueda_palabras.value.trim();
			if (term === "") return false;

			var rawVal = sel.options[sel.selectedIndex].value;
			var isWord = (rawVal.indexOf("|W") !== -1);
			var prefix = rawVal.replace(/\|W$/, '').replace(/\|$/, '');

			var Expresion = "";
			if (isWord) {
				// Keyword search: splits the phrase and inserts the prefix into each part, using 'AND'
				var terms = term.split(/\s+/);
				for (var i = 0; i < terms.length; i++) {
					if (terms[i] !== "") {
						if (Expresion !== "") Expresion += " and ";
						Expresion += prefix + terms[i];
					}
				}
			} else {
				// Exact/phrase search: inserts the prefix only at the start of the entire phrase
				Expresion = prefix + term;
			}

			// Populates the main frame and triggers the search engine
			top.Expresion = Expresion;
			top.Menu('ejecutarbusqueda');
			return false;
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

						<!-- Dictionary/index icon -->
						<a href="javascript:Diccionario()"
							class="btn-toolbar-blue"
							title="<?php $msgstr["m_quicksrcwith"]; ?>">
							<i class="fab fa-searchengin"></i>
						</a>

						<!-- Search terms field -->
						<input type="text"
							name="busqueda_palabras"
							style="width:180px"
							value=""
							onfocus="FocoEn('blibre')"
							title="<?php echo $msgstr["m_enterterms"]; ?>"
							onkeypress="if(event.keyCode==13||event.which==13){BuscarPalabras();return false;}">
					</div>
				<?php endif; /* camposbusqueda.tab */ ?>

				<!-- Format of display + button to edit format (if with permission) ─ -->
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
					/* Format edit button: visible only to users with permissiono */
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

					<!-- Record navigation -->
					<button type="button" class="btn-toolbar" id="btn_0_primero"
						onclick="top.Menu('primero')"
						title="<?php echo $msgstr["m_primero"]; ?>">
						<i class="fas fa-step-backward"></i>
					</button>
					<button type="button" class="btn-toolbar" id="btn_0_anterior"
						onclick="top.Menu('anterior')"
						title="<?php echo $msgstr["m_anterior"]; ?>">
						<i class="fas fa-backward"></i>
					</button>
					<button type="button" class="btn-toolbar" id="btn_0_siguiente"
						onclick="top.Menu('proximo')"
						title="<?php echo $msgstr["m_siguiente"]; ?>">
						<i class="fas fa-forward"></i>
					</button>
					<button type="button" class="btn-toolbar" id="btn_0_ultimo"
						onclick="top.Menu('ultimo')"
						title="<?php echo $msgstr["m_ultimo"]; ?>">
						<i class="fas fa-step-forward"></i>
					</button>

					<!-- Mode of navigation (browseby) -->
					<select id="browseby"
						onchange="onButtonClick('browseby', this.value)"
						title="<?php echo $msgstr["m_browseby"] ?? "Mode of navigation"; ?>">
						<option value="mfn" selected>Mfn</option>
						<option value="search"><?php echo $msgstr["busqueda"]; ?></option>
						<option value="selected_records"><?php echo $msgstr["selected_records"]; ?></option>
						<option value="undo_selected"><?php echo $msgstr["undo_selected"]; ?></option>
					</select>

					<div class="toolbar-divider"></div>

					<!-- Search -->
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('buscar')"
						title="<?php echo $msgstr["m_buscar"]; ?>">
						<i class="fas fa-search"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.SearchHistory()"
						title="<?php echo $msgstr["m_history"]; ?>">
						<i class="fas fa-clipboard-list"></i>
					</button>
					<?php if (isset($tesaurus)): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Tesaurus()"
							title="<?php echo $msgstr["m_tesaurussrc"]; ?>">
							<i class="fas fa-book"></i>
						</button>
					<?php endif; ?>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('busquedalibre')"
						title="<?php echo $msgstr["freesearch_title"]; ?>">
						<i class="fas fa-database"></i>
					</button>
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('alfa')"
						title="<?php echo $msgstr["m_indiceaz"]; ?>">
						<i class="fas fa-sort-alpha-down"></i>
					</button>

					<div class="toolbar-divider"></div>

					<!-- Creating records (with permission checks) -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])     ||
						isset($_SESSION["permiso"]["CENTRAL_CREC"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_CREC"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('nuevo')"
							title="<?php echo $msgstr["m_crear"]; ?>">
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
							title="<?php echo $msgstr["m_capturar"]; ?>">
							<i class="fas fa-copy"></i>
						</button>
					<?php endif; ?>

					<?php
					/* Import document: this option only appears if the database has a COLLECTION defined */
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
								title="<?php echo $msgstr["dd_upload"]; ?>">
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
							title="<?php echo $msgstr["m_z3950"]; ?>">
							<i class="fas fa-download"></i>
						</button>
					<?php endif; ?>

					<div class="toolbar-divider"></div>

					<!-- Default values -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])        ||
						isset($_SESSION["permiso"]["CENTRAL_VALDEF"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_VALDEF"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('editdv')"
							title="<?php echo $msgstr["editar"] . ' ' . $msgstr["valdef"]; ?>">
							<i class="fas fa-tasks"></i>
						</button>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('deletedv')"
							title="<?php echo $msgstr["eliminar"] . ' ' . $msgstr["valdef"]; ?>">
							<i class="fas fa-eraser"></i>
						</button>
						<div class="toolbar-divider"></div>
					<?php endif; ?>

					<!-- Barcode -->
					<?php
					if ((isset($_SESSION["permiso"]["CENTRAL_ALL"])       ||
							isset($_SESSION["permiso"]["CENTRAL_BARCODE"])   ||
							isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
							isset($_SESSION["permiso"][$db . "_CENTRAL_BARCODE"])) &&
						(isset($_SESSION["BARCODE"]) || isset($_SESSION["BARCODE_SIMPLE"]))
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('barcode')"
							title="<?php echo $msgstr["barcode"]; ?>">
							<i class="fas fa-barcode"></i>
						</button>
					<?php endif; ?>

					<!-- Print / Reports -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])      ||
						isset($_SESSION["permiso"]["CENTRAL_PREC"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_PREC"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('imprimir')"
							title="<?php echo $msgstr["m_reportes"]; ?>">
							<i class="fas fa-print"></i>
						</button>
					<?php endif; ?>

					<!-- Manage -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])       ||
						isset($_SESSION["permiso"]["CENTRAL_UTILS"])     ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_UTILS"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('administrar')"
							title="<?php echo $msgstr["mantenimiento"]; ?>">
							<i class="fas fa-cogs"></i>
						</button>
					<?php endif; ?>

					<!-- Update database -->
					<button type="button" class="btn-toolbar"
						onclick="top.Menu('refresh_db')"
						title="<?php echo $msgstr["refresh_db"]; ?>">
						<i class="fas fa-sync-alt"></i>
					</button>

					<div class="toolbar-divider"></div>

					<!-- Statistics -->
					<?php
					if (
						isset($_SESSION["permiso"]["CENTRAL_ALL"])        ||
						isset($_SESSION["permiso"]["CENTRAL_STATGEN"])    ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_ALL"]) ||
						isset($_SESSION["permiso"][$db . "_CENTRAL_STATGEN"])
					): ?>
						<button type="button" class="btn-toolbar"
							onclick="top.Menu('stats')"
							title="<?php echo $msgstr["estadisticas"]; ?>">
							<i class="fas fa-chart-bar"></i>
						</button>
					<?php endif; ?>

					<!-- Help -->
					<button type="button" class="btn-toolbar"
						onclick="AbrirAyuda()"
						title="<?php echo $msgstr["m_ayuda"]; ?>">
						<i class="fas fa-question-circle"></i>
					</button>

					<!-- Home -->
					<button type="button" class="btn-toolbar"
						style="color:#005aa9;"
						onclick="top.Menu('home')"
						title="<?php echo $msgstr["inicio"]; ?>">
						<i class="fas fa-home"></i>
					</button>

				</div><!-- /.abcd-toolbar-buttons -->



				<!-- Worksheet — now displayed alongside the buttons -->
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

	<form name=diccio method=post action=../dataentry/diccionario.php target=Diccionario>
		<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
		<input type=hidden name=cipar value=<?php echo $arrHttp["base"] ?>.par>
		<input type=hidden name=Formato value="">
		<input type=hidden name=Opcion value=diccionario>
		<input type=hidden name=prefijo value="">
		<input type=hidden name=campo value="">
		<input type=hidden name=id value="">
		<input type=hidden name=Diccio value="">
		<input type=hidden name=Decode value="">
		<input type=hidden name=toolbar value="Y">
		<input type=hidden name=desde value=dataentry><input type=hidden name=prologo value=prologoact>
	</form>

</body>

</html>