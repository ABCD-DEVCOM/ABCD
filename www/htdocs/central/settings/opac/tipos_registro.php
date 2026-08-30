<?php
/*
* @file        tipos_registro.php
* @description Configures Record Types / Collections in OPAC.
* @author      Roger C. Guilherme
* @date        2025-11-24
* 
* CHANGE LOG:
* 2026-08-29 Refactored to remove hidden rows and use DOM-based dynamic table management
*/
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/record-types";
include "../../common/inc_div-helper.php";

$update_message = "";

if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
	$archivo_conf = $db_path . $_REQUEST['base'] . "/opac/$lang/" . $_REQUEST["file"];
	$cod_idioma = [];
	$nom_idioma = [];
	$pref_idioma = [];

	foreach ($_REQUEST as $var => $value) {
		if (trim($value) != "") {
			$code = explode("_", $var);
			if ($code[0] == "conf") {
				if ($code[1] == "lc") {
					if (!isset($cod_idioma[$code[2]])) $cod_idioma[$code[2]] = $value;
				} else if ($code[1] == "ln") {
					if (!isset($nom_idioma[$code[2]])) $nom_idioma[$code[2]] = $value;
				} else if ($code[1] == "lp") {
					if (!isset($pref_idioma[$code[2]])) $pref_idioma[$code[2]] = $value;
				}
			}
		}
	}

	$fout = fopen($archivo_conf, "w");
	if ($fout) {
		foreach ($cod_idioma as $key => $value) {
			if (trim($value) == "" && (!isset($nom_idioma[$key]) || trim($nom_idioma[$key]) == "")) {
				continue;
			}
			$name_val = isset($nom_idioma[$key]) ? $nom_idioma[$key] : "";
			$pref_val = isset($pref_idioma[$key]) ? $pref_idioma[$key] : "";

			fwrite($fout, $value . "|" . $name_val . "|" . $pref_val . "\n");
		}
		fclose($fout);
		$update_message = "<div class=\"alert success\"><strong>" . $archivo_conf . " " . $msgstr["updated"] . "</strong></div>";
	}
}
?>

<script>
	var idPage = "db_configuration";
</script>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2 p-0">
		<?php include("conf_opac_menu.php"); ?>
	</div>
	<div class="formContent col-9 m-2">
		<?php include("menu_dbbar.php"); ?>
		<h3><?php echo $msgstr["tipos_registro"]; ?></h3>

		<?php if (!empty($update_message)) echo $update_message; ?>

		<form name="indices" method="post">
			<input type="hidden" name="db_path" value="<?php echo $db_path; ?>">
			<?php
			if (!isset($_REQUEST["Opcion"]) or $_REQUEST["Opcion"] != "Guardar") {
				$archivo = $db_path . "opac_conf/$lang/bases.dat";
				$fp = file_get_contents_utf8($archivo);

				if ($fp) {
					foreach ($fp as $value) {
						if (trim($value) != "") {
							$x = explode('|', $value);
							if ($x[0] != $_REQUEST["base"]) continue;
							echo "<p>";
							Entrada(trim($x[0]), trim($x[1]), $lang, trim($x[0]) . "_colecciones.tab", $x[0]);
							break;
						}
					}
				}
			}
			?>
		</form>
	</div>
</div>

<?php
function Entrada($iD, $name, $lang, $file, $base)
{
	global $msgstr, $db_path;

	echo "<strong>" . $name . "</strong>";
	echo "<div id='$iD'>\n";
	echo "<div style=\"display: flex;\">";

	// Busca dados do FST para painel de ajuda
	$fp_fst = [];
	$fst_file_path = $db_path . $base . "/data/$base.fst";
	if (file_exists($fst_file_path)) {
		$fp_fst = file_get_contents_utf8($fst_file_path);
	}
?>

	<div style="flex: 0 0 60%;">
		<form name="<?php echo $iD; ?>Frm" method="post" onsubmit="reindexTable(this)">
			<input type="hidden" name="Opcion" value="Guardar">
			<input type="hidden" name="base" value="<?php echo $base; ?>">
			<input type="hidden" name="file" value="<?php echo $file; ?>">
			<input type="hidden" name="lang" value="<?php echo $lang; ?>">

			<?php
			$config_file_path = $db_path . $base . "/opac/$lang/$file";
			echo "<strong>" . $config_file_path . "</strong><br>";

			$fp = file_exists($config_file_path) ? file_get_contents_utf8($config_file_path) : [];
			$ix = 0;
			?>

			<table class="table striped" id="tipos_table_<?php echo $iD; ?>">
				<thead>
					<tr>
						<th>ID (Value)</th>
						<th><?php echo $msgstr["nombre"]; ?></th>
						<th><?php echo $msgstr["ix_pref"] ?? "Prefixo FST"; ?></th>
						<th style="text-align:center; white-space: nowrap;"><?php echo $msgstr["actions"] ?? "Ações"; ?></th>
					</tr>
				</thead>
				<tbody id="tbody_<?php echo $iD; ?>">
					<?php
					if ($fp) {
						foreach ($fp as $value) {
							$value = trim($value);
							if ($value != "") {
								$l = explode('|', $value);
								$ix = $ix + 1;
								$id_val = isset($l[0]) ? htmlspecialchars(trim($l[0])) : '';
								$name_val = isset($l[1]) ? htmlspecialchars(trim($l[1])) : '';
								$pref_val = isset($l[2]) ? htmlspecialchars(trim($l[2])) : '';

								echo "<tr>";
								echo "<td><input type='text' name='conf_lc_" . $ix . "' size='10' value='" . $id_val . "'></td>";
								echo "<td><input type='text' name='conf_ln_" . $ix . "' size='30' value='" . $name_val . "'></td>";
								echo "<td><input type='text' name='conf_lp_" . $ix . "' size='10' value='" . $pref_val . "'></td>";

								echo "<td style=\"text-align: center; white-space: nowrap;\">";
								echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, -1)'><i class='fas fa-arrow-up'></i></button> ";
								echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, 1)'><i class='fas fa-arrow-down'></i></button> ";
								echo "<button type='button' class='bt bt-blue' onclick='duplicateRow(this)'><i class='far fa-copy'></i></button> ";
								echo "<button type='button' class='bt bt-red' onclick='deleteRow(this)'><i class='fas fa-trash-alt'></i></button>";
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					?>
				</tbody>
			</table>

			<div style="margin-top: 10px;">
				<button type="button" class="bt-gray" onclick="addRowTipos('tbody_<?php echo $iD; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"]; ?></button>
			</div>
			<button type="submit" class="bt-green m-2"><i class="fas fa-save"></i> <?php echo $msgstr["save"]; ?></button>
		</form>
	</div>

	<div style="flex: 1; padding-left: 20px;">
		<button type="button" class="accordion">
			<i class="fas fa-question-circle"></i> <?php echo $msgstr["view_fst_help"] ?? "Ver arquivo FST"; ?>
		</button>
		<div class="panel p-0">
			<div class="reference-box" style="max-height: 450px;">
				<?php if (!empty($fp_fst)) { ?>
					<table class="table striped">
						<thead>
							<tr>
								<th colspan="3"><strong><?php echo "$base/data/$base.fst"; ?></strong></th>
							</tr>
							<tr>
								<th>ID</th>
								<th>IT</th>
								<th>Formato</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($fp_fst as $value) {
								if (trim($value) != "") {
									$v = explode(' ', $value, 3);
									echo "<tr>";
									echo "<td width='30'>" . (isset($v[0]) ? htmlspecialchars($v[0]) : '') . "</td>";
									echo "<td width='30'>" . (isset($v[1]) ? htmlspecialchars($v[1]) : '') . "</td>";
									echo "<td>" . (isset($v[2]) ? htmlspecialchars($v[2]) : '') . "</td>";
									echo "</tr>\n";
								}
							} ?>
						</tbody>
					</table>
				<?php } else {
					echo "<strong><font color=red>" . $msgstr["missing"] . " $fst_file_path</font></strong>";
				} ?>
			</div>
		</div>
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
		if (confirm("<?php echo $msgstr['are_you_sure'] ?? 'Tem certeza?'; ?>")) {
			btn.closest("tr").remove();
		}
	}

	function duplicateRow(btn) {
		var row = btn.closest("tr");
		var clone = row.cloneNode(true);
		row.parentNode.insertBefore(clone, row.nextSibling);
	}

	function addRowTipos(tbodyId) {
		var tbody = document.getElementById(tbodyId);
		var tr = document.createElement("tr");

		tr.innerHTML = `
        <td><input type="text" name="conf_lc_0" size="10" value=""></td>
        <td><input type="text" name="conf_ln_0" size="30" value=""></td>
        <td><input type="text" name="conf_lp_0" size="10" value=""></td>
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
			var rowIndex = index + 1;
			var inputs = row.querySelectorAll("input[type='text']");
			inputs.forEach(function(input) {
				var name = input.getAttribute("name");
				if (name && name.startsWith("conf_")) {
					var parts = name.split("_");
					parts[2] = rowIndex;
					input.setAttribute("name", parts.join("_"));
				}
			});
		});
	}
</script>

<?php include("../../common/footer.php"); ?>