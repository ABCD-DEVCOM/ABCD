<?php
/*
* @file        busqueda_avanzada_tr.php
* @description Configures Advanced Search per Record Type.
* @author      Refactored by Roger C. Guilherme
* @date        2026-08-29
* 
* CHANGE LOG:
* 2026-08-29 Fixes file path for colecciones.tab, refactored to DOM-based table.
*/
include("conf_opac_top.php");
$wiki_help = "index.php?desde=help&title=OPAC-ABCD_configuraci%C3%B3n_avanzada#B.C3.BAsqueda_avanzada_-_Tipos_de_registro";
include "../../common/inc_div-helper.php";
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
		<h3><?php echo $msgstr["buscar_a"] . " - " . $msgstr["tipos_registro"]; ?></h3>

		<?php
		$base = isset($_REQUEST["base"]) ? $_REQUEST["base"] : "";
		$lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : "en";
		$update_message = "";

		if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
			$archivo_conf = $db_path . $base . "/opac/$lang/" . $_REQUEST["file"];
			$cod_idioma = [];
			$nom_idioma = [];

			foreach ($_REQUEST as $var => $value) {
				if (trim($value) != "") {
					$code = explode("_", $var);
					if ($code[0] == "conf") {
						if ($code[1] == "lc") {
							if (!isset($cod_idioma[$code[2]])) $cod_idioma[$code[2]] = $value;
						} else if ($code[1] == "ln") {
							if (!isset($nom_idioma[$code[2]])) $nom_idioma[$code[2]] = $value;
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
					fwrite($fout, $value . "|" . $name_val . "\n");
				}
				fclose($fout);
				$update_message = "<div class=\"alert success\"><strong>" . $archivo_conf . " " . $msgstr["updated"] . "</strong></div>";
			}
		}

		if (!empty($update_message)) echo $update_message;

		$archivo = $db_path . "opac_conf/$lang/bases.dat";
		$fp = file_get_contents_utf8($archivo);
		if ($fp && $base != "") {
			foreach ($fp as $value) {
				if (trim($value) != "") {
					$x = explode('|', $value);
					if ($base != $x[0]) continue;

					// CORREÇÃO: O caminho do arquivo de coleções fica dentro da pasta da base
					$colecciones_file = $db_path . $base . "/opac/$lang/" . $base . "_colecciones.tab";

					if (!file_exists($colecciones_file)) {
						echo "<div class=\"alert alert-warning\"><h4>" . $msgstr["nrt_defined"] . " " . $base . "</h4>";
						echo "<small>O arquivo esperado não foi encontrado em: <code>" . $colecciones_file . "</code></small></div>";
						continue;
					}

					$fpTm = file_get_contents_utf8($colecciones_file);
					echo "<h4>" . htmlspecialchars($x[1]) . "</h4><p>";

					if ($fpTm) {
						foreach ($fpTm as $coleccion) {
							if (trim($coleccion) != "") {
								$c = explode('|', $coleccion);
								$TM = trim($c[0]);
								$nombre_c = trim($c[1]);
								$file_avanzada = $base . "_avanzada_$TM.tab";
								Entrada($base, trim($x[1]), $lang, $file_avanzada, $nombre_c, $TM);
							}
						}
					}
				}
			}
		}
		?>
	</div>
</div>

<?php
function Entrada($base, $name, $lang, $file, $nombre_c, $TM)
{
	global $msgstr, $db_path;

	echo "<strong>" . htmlspecialchars($name) . " - " . htmlspecialchars($nombre_c) . "</strong>";
	echo "<div id='{$base}{$TM}' style=\"margin-bottom: 30px;\">\n";
?>
	<form name="<?php echo $base . $TM; ?>Frm" method="post" onsubmit="reindexTable(this)">
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

		<table class="table striped" id="table_<?php echo $base . $TM; ?>">
			<thead>
				<tr>
					<th><?php echo $msgstr["ix_nombre"]; ?></th>
					<th><?php echo $msgstr["ix_pref"]; ?></th>
					<th style="text-align:center; white-space: nowrap;"><?php echo $msgstr["actions"] ?? "Ações"; ?></th>
				</tr>
			</thead>
			<tbody id="tbody_<?php echo $base . $TM; ?>">
				<?php
				if ($fp) {
					foreach ($fp as $value) {
						$value = trim($value);
						if ($value != "") {
							$l = explode('|', $value);
							$ix++;
							$campo_val = isset($l[0]) ? htmlspecialchars(trim($l[0])) : '';
							$pref_val = isset($l[1]) ? htmlspecialchars(trim($l[1])) : '';

							echo "<tr>";
							echo "<td><input type='text' name='conf_lc_" . $ix . "' size='30' value='" . $campo_val . "'></td>";
							echo "<td><input type='text' name='conf_ln_" . $ix . "' size='30' value='" . $pref_val . "'></td>";
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
			<button type="button" class="bt-gray" onclick="addRowBusquedaAvanzada('tbody_<?php echo $base . $TM; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"]; ?></button>
			<button type="submit" class="bt-green m-2"><i class="fas fa-save"></i> <?php echo $msgstr["save"] . " " . htmlspecialchars($TM); ?></button>
		</div>
	</form>
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

	function addRowBusquedaAvanzada(tbodyId) {
		var tbody = document.getElementById(tbodyId);
		var tr = document.createElement("tr");

		tr.innerHTML = `
        <td><input type="text" name="conf_lc_0" size="30" value=""></td>
        <td><input type="text" name="conf_ln_0" size="30" value=""></td>
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