<?php
/* Modifications
20220202 fho4abcd back button, div-helper
20260602 rogercgui - Complete refactoring of the MARC fixed-field manager to make it more generic, flexible, and user-friendly. It now supports multiple 'M'-type fixed fields defined in the FDT, each of which can have its own category matrix and target FDT file. The system has also been enhanced to handle permissions, automatic file creation, and clearer error messages. The interface has been modernized with colorful buttons and icons to improve usability.
*/
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];

include("../lang/dbadmin.php");


if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) and !isset($_SESSION["permiso"]["CENTRAL_MODIFYDEF"])  and !isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_MODIFYDEF"]) and !isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"])) {
	echo "<br><br><h2>" . $msgstr["menu_noau"] . "<h2>";
	die;
}
include("../common/header.php");
if (isset($arrHttp["encabezado"]))
	$encabezado = "&encabezado=s";
else
	$encabezado = "";

?>
<script language="JavaScript" type="text/javascript" src="../dataentry/js/lr_trim.js"></script>
<?php if (isset($arrHttp["encabezado"])) {
	include("../common/institutional_info.php");
}
?>
<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["typeofrecords"] . ": " . $arrHttp["base"] ?>
	</div>
	<div class="actions">
		<?php
		$backtoscript = "menu_modificardb.php";
		$backtoscript = $backtoscript . "?base=" . $arrHttp["base"] . $encabezado;
		include "../common/inc_back.php";
		include "../common/inc_home.php";
		?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
$ayuda = "typeofrecs_marc.html";
include "../common/inc_div-helper.php";
?>
<div class="middle form">
	<div class="formContent">

		<?php
		// ======================================================================
		// GENERIC FIXED-FIELD MOTOR (Replaces the static routine in 008)
		// ======================================================================

		$fixed_tab = isset($arrHttp["fixed_tab"]) ? trim($arrHttp["fixed_tab"]) : "";

		if ($fixed_tab == "") {
			// ---------------------------------------------------------
			// STEP 0: ADMINISTRATION HUB (Reads the FDT of the current database)
			// ---------------------------------------------------------
			echo "<h3>" . ($msgstr["ff_manager"] ?? "Gerenciador de Campos Fixos MARC") . "</h3>";
			echo "<p>" . ($msgstr["ff_select_field"] ?? "Selecione qual campo fixo (Tipo 'M') você deseja configurar:") . "</p>";

			// Localiza o FDT principal da base
			$fdt_file = $db_path . $arrHttp["base"] . "/def/" . $lang . "/" . $arrHttp["base"] . ".fdt";
			if (!file_exists($fdt_file)) {
				$fdt_file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
			}

			if (file_exists($fdt_file)) {
				$fdt_lines = file($fdt_file);
				$found_m = false;

				echo "<div style='margin-top: 20px;'>";
				foreach ($fdt_lines as $line) {
					$l = explode('|', trim($line));
					if (isset($l[0]) && $l[0] == 'M') {
						$found_m = true;
						$tag = $l[1];
						$name = $l[2];
						// Safety check: if the Picklist (column 11) is empty, use ldr_06.tab
						$tab_route = (isset($l[11]) && trim($l[11]) != "") ? trim($l[11]) : "ldr_06.tab";

						$url = "fixed_marc.php?encabezado=s&base=" . $arrHttp["base"] . "&fixed_tab=" . urlencode($tab_route);

						echo "<a href='$url' class='bt bt-blue' style='display:block; width: 300px; margin-bottom: 10px; text-align: left;'>";
						echo "<i class='fas fa-cog'></i> <strong>$name ($tag)</strong> <br><small>" . ($msgstr["ff_router"] ?? "Roteador") . ": $tab_route</small>";
						echo "</a>";
					}
				}
				echo "</div>";

				if (!$found_m) {
					echo "<p style='color:red;'>" . ($msgstr["ff_no_m_type"] ?? "Nenhum campo do tipo 'M' foi encontrado na FDT principal desta base.") . "</p>";
				}
			} else {
				echo "<p style='color:red;'>" . ($msgstr["ff_err_fdt"] ?? "Erro: Não foi possível localizar a FDT da base para leitura.") . "</p>";
			}
		} else {
			// ---------------------------------------------------------
			// STEP 1: THE MATRIX MANAGER (Self-Creation and Editing)
			// ---------------------------------------------------------

			// Proteção básica contra directory traversal
			$fixed_tab = basename($fixed_tab);
			$path_lang = $db_path . $arrHttp["base"] . "/def/" . $lang . "/" . $fixed_tab;
			$path_lang_db = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $fixed_tab;

			// ---------------------------------------------------------
			// Processing: Create an FDT matrix and bypass the fdt.php block
			// ---------------------------------------------------------
			if (isset($arrHttp["Opcion"]) && $arrHttp["Opcion"] == "create_fdt") {
				$target_fdt = isset($arrHttp["new_fdt"]) ? trim($arrHttp["new_fdt"]) : "";
				if ($target_fdt != "") {
					$fdt_path = $db_path . $arrHttp["base"] . "/def/" . $lang . "/" . basename($target_fdt);
					if (!file_exists($fdt_path)) {
						$fp_fdt = fopen($fdt_path, "w");
						if ($fp_fdt) {
							// Simulates the native creation of ABCD by inserting 20 blank lines (23 pipes)
							for ($i = 0; $i < 20; $i++) {
								fwrite($fp_fdt, "|||||||||||||||||||||||\n");
							}
							fclose($fp_fdt);
						}
					}
					// Redirects immediately via JavaScript as an UPDATE
					echo "<script>window.location.href='fdt.php?encabezado=s&Opcion=update&base=" . urlencode($arrHttp["base"]) . "&type=" . urlencode($target_fdt) . "';</script>";
					die;
				}
			}

			// ---------------------------------------------------------
			// Reading the dictionary file (or Auto-creation)
			// ---------------------------------------------------------
			$tab_file_content = false;
			if (file_exists($path_lang)) {
				$tab_file_content = file($path_lang);
			} elseif (file_exists($path_lang_db)) {
				$tab_file_content = file($path_lang_db);
			}

			// AUTO-CREATE: If the file does not exist, create an empty file silently
			if (!$tab_file_content) {
				$fp = fopen($path_lang, "w");
				if ($fp) {
					fclose($fp);
					$tab_file_content = array(); // Inicia como array vazio
				} else {
					echo "<h3 style='color:red;'>" . ($msgstr["ff_err_perm"] ?? "Permission Error") . "</h3>";
					echo "<p>" . ($msgstr["ff_err_write"] ?? "The system does not have write permission to create the file") . " <strong>" . $fixed_tab . "</strong> " . ($msgstr["ff_in_folder"] ?? "in the folder") . " <code>def</code>.</p>";
					echo "<a href='fixed_marc.php?encazado=s&base=" . $arrHttp["base"] . "' class='bt bt-gray'><i class='fas fa-arrow-left'></i> " . ($msgstr["ff_back"] ?? "Back") . "</a>";
					echo "</div></div>";
					include("../common/footer.php");
					die;
				}
			}

			// ---------------------------------------------------------
			// Processing: Add, Update, Delete Category in the .tab file
			// ---------------------------------------------------------
			$save_needed = false;

			if (isset($arrHttp["Opcion"])) {
				if ($arrHttp["Opcion"] == "add_category") {
					$new_code = isset($arrHttp["new_code"]) ? trim($arrHttp["new_code"]) : "";
					$new_desc = isset($arrHttp["new_desc"]) ? trim($arrHttp["new_desc"]) : "";
					$new_fdt  = isset($arrHttp["new_fdt"]) ? trim($arrHttp["new_fdt"]) : "";
					if ($new_code != "" && $new_fdt != "") {
						if (substr($new_fdt, -4) !== ".fdt") $new_fdt .= ".fdt";
						$tab_file_content[] = $new_code . "|" . $new_desc . "|" . $new_fdt . "\n";
						$save_needed = true;
					}
				} elseif ($arrHttp["Opcion"] == "update_category") {
					$orig_code = isset($arrHttp["original_code"]) ? trim($arrHttp["original_code"]) : "";
					$new_code  = isset($arrHttp["edit_code"]) ? trim($arrHttp["edit_code"]) : "";
					$new_desc  = isset($arrHttp["edit_desc"]) ? trim($arrHttp["edit_desc"]) : "";
					$new_fdt   = isset($arrHttp["edit_fdt"]) ? trim($arrHttp["edit_fdt"]) : "";
					if ($orig_code != "" && $new_code != "" && $new_fdt != "") {
						if (substr($new_fdt, -4) !== ".fdt") $new_fdt .= ".fdt";
						foreach ($tab_file_content as $k => $line) {
							$parts = explode("|", trim($line));
							if (isset($parts[0]) && $parts[0] === $orig_code) {
								$tab_file_content[$k] = $new_code . "|" . $new_desc . "|" . $new_fdt . "\n";
								break;
							}
						}
						$save_needed = true;
					}
				} elseif ($arrHttp["Opcion"] == "delete_category") {
					$del_code = isset($arrHttp["del_code"]) ? trim($arrHttp["del_code"]) : "";
					if ($del_code != "") {
						foreach ($tab_file_content as $k => $line) {
							$parts = explode("|", trim($line));
							if (isset($parts[0]) && $parts[0] === $del_code) {
								unset($tab_file_content[$k]);
								break;
							}
						}
						$save_needed = true;
					}
				}
			}

			if ($save_needed) {
				$fp = fopen($path_lang, "w");
				if ($fp) {
					foreach ($tab_file_content as $line) {
						if (trim($line) != "") {
							fwrite($fp, trim($line) . "\n");
						}
					}
					fclose($fp);
				}
				// Recarrega a página para limpar o histórico de POST do navegador
				echo "<script>window.location.href='fixed_marc.php?base=" . urlencode($arrHttp["base"]) . "&fixed_tab=" . urlencode($fixed_tab) . "';</script>";
				die;
			}

			// ---------------------------------------------------------
			// Rendering of the Administration Panel
			// ---------------------------------------------------------
			echo "<h3>" . ($msgstr["typeofrecords"] ?? "Tipo de Registros") . " (" . htmlspecialchars($fixed_tab) . ")</h3>";
			echo "<a href='fixed_marc.php?encabezado=s&base=" . $arrHttp["base"] . "' class='bt bt-gray' style='margin-bottom:15px;'><i class='fas fa-arrow-left'></i> " . ($msgstr["ff_back_fixed"] ?? "Voltar aos Campos Fixos") . "</a>";

			// Encapsulamos a tabela num formulário global para viabilizar a edição inline e os botões nativos
			echo "<form method='POST' action='fixed_marc.php' id='matrix_form'>";
			echo "<input type='hidden' name='base' value='" . htmlspecialchars($arrHttp["base"]) . "'>";
			echo "<input type='hidden' name='fixed_tab' value='" . htmlspecialchars($fixed_tab) . "'>";
			echo "<input type='hidden' name='Opcion' value=''>";
			echo "<input type='hidden' name='original_code' value=''>";
			echo "<input type='hidden' name='del_code' value=''>";

			echo "<table class='listTable' style='width:100%; border-collapse: collapse;'>";
			echo "<tr>";
			echo "<th>" . ($msgstr["ff_code"] ?? "Code") . "</th>";
			echo "<th>" . ($msgstr["ff_desc_cat"] ?? "Description / Material Category") . "</th>";
			echo "<th>" . ($msgstr["ff_target_fdt"] ?? "Target FDT File") . "</th>";
			echo "<th>" . ($msgstr["ff_action"] ?? "Action") . "</th>";
			echo "</tr>";

			$has_categories = false;
			foreach ($tab_file_content as $line) {
				$line = trim($line);
				if ($line != "") {
					$has_categories = true;
					$parts = explode("|", $line);
					$code = isset($parts[0]) ? $parts[0] : "";
					$description = isset($parts[1]) ? $parts[1] : "";
					$fdt_file = isset($parts[2]) ? $parts[2] : "";

					if ($fdt_file == "") continue;

					$fdt_path = $db_path . $arrHttp["base"] . "/def/" . $lang . "/" . $fdt_file;
					$fdt_path_fallback = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $fdt_file;
					$fdt_exists = (file_exists($fdt_path) || file_exists($fdt_path_fallback));

					// Verifica se o usuário ativou o modo de edição para esta linha específica
					if (isset($arrHttp["edit_code"]) && $arrHttp["edit_code"] === $code) {
						// Linha em Modo de Edição (com fundo amarelo para destacar)
						echo "<tr class='rowOver' style='background-color: #ffffcc;'>";
						echo "<td style='text-align:center;'><input type='text' name='edit_code' value='" . $code . "' size='3' maxlength='5' required></td>";
						echo "<td><input type='text' name='edit_desc' value='" . $description . "' size='35' required></td>";
						echo "<td><input type='text' name='edit_fdt' value='" . $fdt_file . "' size='15' required></td>";

						echo "<td style='text-align:center; white-space: nowrap;'>";
						echo "<button type='button' class='bt bt-green' onclick='document.getElementById(\"matrix_form\").Opcion.value=\"update_category\"; document.getElementById(\"matrix_form\").original_code.value=\"$code\"; document.getElementById(\"matrix_form\").submit();' title='" . ($msgstr["ff_save_changes"] ?? "Save Changes") . "'><i class='fas fa-save'></i></button> ";
						echo "<a href='fixed_marc.php?encabezado=s&base=" . $arrHttp["base"] . "&fixed_tab=" . urlencode($fixed_tab) . "' class='bt bt-red' title='" . ($msgstr["cancelar"] ?? "Cancel") . "'><i class='fas fa-times'></i></a>";
						echo "</td>";
						echo "</tr>";
					} else {
						// Linha Normal (Leitura)
						echo "<tr onmouseover=\"this.className='rowOver';\" onmouseout=\"this.className='';\">";
						echo "<td style='text-align:center;'><strong>" . htmlspecialchars($code) . "</strong></td>";
						echo "<td>" . $description . "</td>";
						echo "<td><code>" . htmlspecialchars($fdt_file) . "</code></td>";

						echo "<td style='text-align:center; white-space: nowrap;'>";
						// Botões da Matriz (Azul e Verde)
						if ($fdt_exists) {
							$edit_url = "fdt.php?encabezado=s&Opcion=update&base=" . $arrHttp["base"] . "&type=" . urlencode($fdt_file);
							echo "<a href='$edit_url' class='bt bt-blue' title='" . ($msgstr["ff_edit_matrix"] ?? "Editar Matriz") . "'><i class='fas fa-edit'></i></a> ";
						} else {
							$create_url = "fixed_marc.php?encabezado=s&Opcion=create_fdt&base=" . urlencode($arrHttp["base"]) . "&fixed_tab=" . urlencode($fixed_tab) . "&new_fdt=" . urlencode($fdt_file);
							echo "<a href='$create_url' class='bt bt-green' title='" . ($msgstr["ff_create_matrix"] ?? "Create Matrix") . "'><i class='fas fa-plus'></i></a> ";
						}

						// Botões de Ação da Categoria (Cinza Lápis e Vermelho Lixeira)
						$edit_cat_url = "fixed_marc.php?encabezado=s&base=" . $arrHttp["base"] . "&fixed_tab=" . urlencode($fixed_tab) . "&edit_code=" . urlencode($code);
						echo "<a href='$edit_cat_url' class='bt bt-gray' title='" . ($msgstr["edit"] ?? "Editar") . "'><i class='fas fa-pencil-alt'></i></a> ";

						$del_confirm = htmlspecialchars($msgstr["ff_sure_delete"] ?? "Tem certeza que deseja excluir esta categoria?");
						echo "<button type='button' class='bt bt-red' onclick='if(confirm(\"$del_confirm\")) { document.getElementById(\"matrix_form\").Opcion.value=\"delete_category\"; document.getElementById(\"matrix_form\").del_code.value=\"$code\"; document.getElementById(\"matrix_form\").submit(); }' title='" . ($msgstr["delete"] ?? "Excluir") . "'><i class='fas fa-trash'></i></button>";

						echo "</td>";
						echo "</tr>";
					}
				}
			}

			if (!$has_categories) {
				echo "<tr><td colspan='4' style='text-align:center; padding:20px; color:#666;'>" . ($msgstr["ff_no_categories"] ?? "Nenhuma categoria configurada neste arquivo. Adicione a primeira utilizando o formulário abaixo.") . "</td></tr>";
			}
			echo "</table>";
			echo "</form>";

			// ---------------------------------------------------------
			// Integrated Form for Adding a New Category
			// ---------------------------------------------------------
			echo "<div style='margin-top: 30px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;'>";
			echo "<h4>" . ($msgstr["ff_add_category_to"] ?? "Adicionar Nova Categoria ao") . " <code>" . htmlspecialchars($fixed_tab) . "</code></h4>";
			echo "<form method='GET' action='fixed_marc.php'>";
			echo "<input type='hidden' name='base' value='" . htmlspecialchars($arrHttp["base"]) . "'>";
			echo "<input type='hidden' name='fixed_tab' value='" . htmlspecialchars($fixed_tab) . "'>";
			echo "<input type='hidden' name='Opcion' value='add_category'>";
			echo "<input type='hidden' name='encabezado' value='s'>";

			echo "<label style='display:inline-block; width: 60px;'>" . ($msgstr["ff_code"] ?? "Code") . ":</label> ";
			echo "<input type='text' name='new_code' size='3' maxlength='5' placeholder='e.g., a' required> &nbsp;&nbsp;";

			echo "<label>" . ($msgstr["ff_desc"] ?? "Description") . ":</label> ";
			echo "<input type='text' name='new_desc' size='40' placeholder='e.g., Cartographic Materials' required> &nbsp;&nbsp;";

			echo "<label>" . ($msgstr["ff_fdt"] ?? "FDT") . ":</label> ";
			echo "<input type='text' name='new_fdt' size='15' placeholder='e.g., map_007.fdt' required> &nbsp;&nbsp;";

			echo "<button type='submit' class='bt bt-green'><i class='fas fa-plus'></i> " . ($msgstr["ff_add_category"] ?? "Add Category") . "</button>";
			echo "</form>";
			echo "</div>";
		}
		?>
	</div>
	<?php include("../common/footer.php"); ?>