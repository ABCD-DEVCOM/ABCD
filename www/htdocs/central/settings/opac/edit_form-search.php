<?php
/*
* @file        edit_form-search.php
* @description File to edit the free/advanced search form configuration.
* @author      Refactored by Roger C. Guilherme
* @date        2026-08-29
* 
* CHANGE LOG:
* 2026-08-29 Refactored table UI (no hidden rows, inline action buttons, DOM reindexing). 
*            Added protection to disable row manipulation when configuring Free Search.
*/
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/search-forms";
include "../../common/inc_div-helper.php";

if (isset($_REQUEST["base"]) && $_REQUEST["base"] === "META") {
?>
	<script>
		var idPage = "metasearch";
	</script>
<?php } else { ?>
	<script>
		var idPage = "db_configuration";
	</script>
<?php } ?>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2 p-0">
		<?php include("conf_opac_menu.php"); ?>
	</div>
	<div class="formContent col-9 m-2">
		<?php include("menu_dbbar.php");  ?>

		<?php if (isset($_REQUEST['o_conf']) && $_REQUEST['o_conf'] === "libre") { ?>
			<h3><?php echo $msgstr["free_search"]; ?></h3>
		<?php } else { ?>
			<h3><?php echo $msgstr["buscar_a"]; ?></h3>
		<?php } ?>

		<?php
		$db_path = $_SESSION["db_path"];
		$base = $_REQUEST["base"] ?? "";
		$update_message = "";

		if (isset($_REQUEST["Opcion"]) && $_REQUEST["Opcion"] === "Guardar") {
			if (isset($_REQUEST['base']) && $_REQUEST['base'] === "META") {
				$archivo_conf = $db_path . "opac_conf/$lang/" . $_REQUEST["file"];
			} else {
				$archivo_conf = $db_path . $_REQUEST['base'] . "/opac/$lang/" . $_REQUEST["file"];
			}

			$cod_idioma = [];
			$nom_idioma = [];
			foreach ($_REQUEST as $var => $value) {
				if (trim($value) !== "") {
					$code = explode("_", $var);
					if ($code[0] === "conf") {
						if ($code[1] === "lc") {
							if (!isset($cod_idioma[$code[2]])) {
								$cod_idioma[$code[2]] = $value;
							}
						} elseif ($code[1] === "ln") {
							if (!isset($nom_idioma[$code[2]])) {
								$nom_idioma[$code[2]] = $value;
							}
						}
					}
				}
			}

			$fout = fopen($archivo_conf, "w");
			if ($fout) {
				foreach ($cod_idioma as $key => $value) {
					if (trim($value) === "" && (!isset($nom_idioma[$key]) || trim($nom_idioma[$key]) === "")) {
						continue;
					}
					$name_val = $nom_idioma[$key] ?? "";
					fwrite($fout, $value . "|" . $name_val . "\n");
				}
				fclose($fout);
				$update_message = "<div class=\"alert success\"><strong>" . $archivo_conf . " " . $msgstr["updated"] . "</strong></div>";
			} else {
				$update_message = "<div class=\"alert error\"><strong>Error: Cannot open file for writing: " . $archivo_conf . "</strong></div>";
			}
		}

		if (!empty($update_message)) echo $update_message;

		if (!isset($_REQUEST["Opcion"]) || $_REQUEST["Opcion"] !== "Guardar") {
			$archivo = $db_path . "opac_conf/" . $lang . "/bases.dat";
			$fp = file_get_contents_utf8($archivo);

			if (isset($_REQUEST["base"]) && $_REQUEST["base"] === "META") {
				Entrada("MetaSearch", $msgstr["metasearch"], $lang, $_REQUEST['o_conf'] . ".tab", "META");
			} else {
				if ($fp) {
					foreach ($fp as $value) {
						if (trim($value) !== "") {
							$x = explode('|', $value);
							if ($_REQUEST["base"] !== $x[0]) continue;
							Entrada(trim($x[0]), trim($x[1]), $lang, trim($x[0]) . "_" . $_REQUEST['o_conf'] . ".tab", $x[0]);
						}
					}
				}
			}
		}
		?>
	</div>
</div>

<?php
function Entrada($iD, $name, $lang, $file, $base)
{
	global $msgstr, $db_path;

	// Check if we are configuring Free Search
	$is_free_search = (isset($_REQUEST['o_conf']) && $_REQUEST['o_conf'] === 'libre');

	echo "<strong>" . htmlspecialchars($name);
	if ($base !== "" && $base !== "META") echo " (" . htmlspecialchars($base) . ")";
	echo "</strong>";
	echo "<div id='$iD'>\n";
	echo "<div style=\"display: flex;\">";

	$cuenta = 0;
	$fp_campos = [];
	$file_fieldsearch = $db_path . $base . "/pfts/" . $_REQUEST["lang"] . "/camposbusqueda.tab";
	$fp_campos_base = file_get_contents_utf8($file_fieldsearch);

	if ($fp_campos_base) {
		$fp_campos[$base] = $fp_campos_base;
	} else {
		$file_fieldsearch_en = $db_path . $base . "/pfts/en/camposbusqueda.tab";
		$fp_campos_base_en = file_get_contents_utf8($file_fieldsearch_en);
		if ($fp_campos_base_en) {
			$fp_campos[$base] = $fp_campos_base_en;
		} else {
			$fp_campos[$base] = [];
		}
	}

	if ($base !== "" && $base !== "META") {
		$cuenta = count($fp_campos[$base]);
	} else if ($base === "META") {
		$file_bases_dat = $db_path . "opac_conf/" . $_REQUEST["lang"] . "/bases.dat";
		$fpbases = file_get_contents_utf8($file_bases_dat);
		if ($fpbases) {
			foreach ($fpbases as $value) {
				$value = trim($value);
				if ($value === "") continue;
				$v = explode('|', $value);
				$b_0 = $v[0];
				$file_fieldsearch_meta = $db_path . $b_0 . "/pfts/" . $_REQUEST["lang"] . "/camposbusqueda.tab";
				$fpbb = file_get_contents_utf8($file_fieldsearch_meta);
				if ($fpbb) {
					foreach ($fpbb as $campos) {
						if (trim($campos) !== "") $fp_campos[$b_0][] = $campos;
					}
				} else {
					$file_fieldsearch_meta_en = $db_path . $b_0 . "/pfts/en/camposbusqueda.tab";
					$fpbb_en = file_get_contents_utf8($file_fieldsearch_meta_en);
					if ($fpbb_en) {
						foreach ($fpbb_en as $campos) {
							if (trim($campos) !== "") $fp_campos[$b_0][] = $campos;
						}
					}
				}
			}
		}
		$cuenta = count($fp_campos);
	}
?>

	<div style="flex: 0 0 50%;">
		<form name="<?php echo $iD; ?>Frm" method="post" onsubmit="reindexTable(this)">
			<input type="hidden" name="Opcion" value="Guardar">
			<input type="hidden" name="base" value="<?php echo $base; ?>">
			<input type="hidden" name="file" value="<?php echo $file; ?>">
			<input type="hidden" name="lang" value="<?php echo $lang; ?>">
			<?php
			if (isset($_REQUEST["o_conf"])) {
				echo "<input type=\"hidden\" name=\"o_conf\" value=\"" . $_REQUEST["o_conf"] . "\">\n";
			}
			if ($base !== "" && $base !== "META") {
				$file_av = $db_path . $base . "/opac/$lang/$file";
			} else {
				$file_av = $db_path . "/opac_conf/$lang/$file";
			}

			echo "<strong>" . $file_av . "</strong><br>";
			$fp = file_get_contents_utf8($file_av);
			$ix = 0;

			echo "<table id='search_table_" . $iD . "' class='table striped' cellpadding=5>\n";
			echo "<thead><tr>";
			echo "<th>" . ($msgstr["ix_nombre"] ?? "Name") . "</th>";
			echo "<th>" . ($msgstr["ix_pref"] ?? "Prefix") . "</th>";

			if (!$is_free_search) {
				echo "<th style='text-align:center;'>" . ($msgstr["actions"] ?? "Actions") . "</th>";
			}

			echo "</tr></thead>";
			echo "<tbody id='tbody_search_" . $iD . "'>";

			if ($fp) {
				foreach ($fp as $value) {
					$value = trim($value);
					if ($value !== "") {
						$l = explode('|', $value);
						if (count($l) < 2) $l[1] = "";
						$ix++;
						echo "<tr>";
						echo "<td><input type='text' name='conf_lc_" . $ix . "' size='30' value='" . htmlspecialchars(trim($l[0])) . "'></td>";
						echo "<td><input type='text' name='conf_ln_" . $ix . "' size='5' value='" . htmlspecialchars(trim($l[1])) . "'></td>";

						if (!$is_free_search) {
							echo "<td style=\"text-align: center; white-space: nowrap;\">";
							echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, -1)'><i class='fas fa-arrow-up'></i></button> ";
							echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, 1)'><i class='fas fa-arrow-down'></i></button> ";
							echo "<button type='button' class='bt bt-blue' onclick='duplicateRow(this)'><i class='far fa-copy'></i></button> ";
							echo "<button type='button' class='bt bt-red' onclick='deleteRow(this)'><i class='fas fa-trash-alt'></i></button>";
							echo "</td>";
						}

						echo "</tr>";
					}
				}
			}
			echo "</tbody>";
			echo "</table>\n";

			if (!$is_free_search) {
			?>
				<div style="margin-top: 10px;">
					<button type="button" class="bt-gray" onclick="addRowSearch('tbody_search_<?php echo $iD; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"] ?? "Add Line"; ?></button>
				</div>
			<?php } ?>

			<button type="submit" class="bt-green m-2"><i class="fas fa-save"></i> <?php echo $msgstr["save"]; ?></button>
		</form>
	</div>

	<div style="flex: 1; padding-left: 20px; width: 150px;">
		<?php if ($cuenta > 0) { ?>
			<button type="button" class="accordion">
				<i class="fas fa-question-circle"></i> <?php echo $msgstr["view_searchfields_help"]; ?>
			</button>
			<div class="panel p-0">
				<div class="reference-box" style="max-height: 450px;">
					<?php
					foreach ($fp_campos as $key => $value_campos) {
						echo "<strong>" . $key . "/" . $_REQUEST["lang"] . "/camposbusqueda.tab (central ABCD)</strong><br>";
					?>
						<table class="table striped">
							<thead>
								<tr>
									<th><?php echo $msgstr["ix_nombre"]; ?></th>
									<th><?php echo $msgstr["ix_pref"]; ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (!empty($value_campos)) {
									foreach ($value_campos as $value) {
										$value = trim($value);
										if ($value === "") continue;
										$v = explode('|', $value);
										if (count($v) < 3) $v[2] = "";
										echo "<tr><td>" . htmlspecialchars(trim($v[0])) . "</td><td>" . htmlspecialchars(trim($v[2])) . "</td></tr>\n";
									}
								}
								?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>
	</div>
	</div>
<?php
}
?>

<script>
	function moveRow(btn, direction) {
		var row = btn.closest("tr");
		var tbody = row.parentNode;
		if (direction === -1 && row.previousElementSibling) {
			tbody.insertBefore(row, row.previousElementSibling);
		} else if (direction === 1 && row.nextElementSibling) {
			tbody.insertBefore(row.nextElementSibling, row);
		}
	}

	function deleteRow(btn) {
		if (confirm("<?php echo $msgstr['are_you_sure'] ?? 'Are you sure?'; ?>")) {
			btn.closest("tr").remove();
		}
	}

	function duplicateRow(btn) {
		var row = btn.closest("tr");
		var clone = row.cloneNode(true);
		row.parentNode.insertBefore(clone, row.nextSibling);
	}

	function addRowSearch(tbodyId) {
		var tbody = document.getElementById(tbodyId);
		var tr = document.createElement("tr");

		tr.innerHTML = `
        <td><input type="text" name="conf_lc_0" size="30" value=""></td>
        <td><input type="text" name="conf_ln_0" size="5" value=""></td>
        <td style="text-align: center; white-space: nowrap;">
            <button type="button" class="bt bt-gray" onclick="moveRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="bt bt-gray" onclick="moveRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="bt bt-blue" onclick="duplicateRow(this)"><i class="far fa-copy"></i></button>
            <button type="button" class="bt bt-red" onclick="deleteRow(this)"><i class="fas fa-trash-alt"></i></button>
        </td>
    `;
		tbody.appendChild(tr);
	}

	function reindexTable(form) {
		var rows = form.querySelectorAll("tbody tr");
		rows.forEach(function(row, index) {
			var inputs = row.querySelectorAll("input[type='text']");
			inputs.forEach(function(input) {
				var name = input.getAttribute("name");
				if (name && name.startsWith("conf_")) {
					var parts = name.split("_");
					parts[2] = index + 1;
					input.setAttribute("name", parts.join("_"));
				}
			});
		});
	}
</script>

<?php include("../../common/footer.php"); ?>