<?php
include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/alpha_ix";
include "../../common/inc_div-helper.php";

$update_message = "";

if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
	$base = $_REQUEST['base'];
	$lang = $_REQUEST['lang'];
	$file = $_REQUEST["file"];

	if ($base == "META") {
		$archivo_conf = $db_path . "/opac_conf/" . $lang . "/" . $file;
	} else {
		$archivo_conf = $db_path . $base . "/opac/" . $lang . "/" . $file;
	}

	$fout = fopen($archivo_conf, "w");

	$campos = isset($_REQUEST["campo"]) ? $_REQUEST["campo"] : [];
	$prefijos = isset($_REQUEST["prefijo"]) ? $_REQUEST["prefijo"] : [];
	$colunas = isset($_REQUEST["coluna"]) ? $_REQUEST["coluna"] : [];
	$postings = isset($_REQUEST["posting"]) ? $_REQUEST["posting"] : [];

	foreach ($campos as $key => $campo) {
		if (trim($campo) != "") {
			$prefixo_val = isset($prefijos[$key]) ? $prefijos[$key] : "";
			$coluna_val = isset($colunas[$key]) ? $colunas[$key] : "";
			$posting_val = (isset($postings[$key]) && $postings[$key] === 'ALL') ? "ALL" : "";

			fwrite($fout, trim($campo) . "|" . trim($prefixo_val) . "|" . trim($coluna_val) . "|" . trim($posting_val) . "\n");
		}
	}
	fclose($fout);

	$update_message = "<div class='alert success'>" . $archivo_conf . " " . $msgstr["updated"] . "</div>";
}

if (isset($_REQUEST["base"]) && $_REQUEST["base"] == "META") {
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
		<?php include("menu_dbbar.php"); ?>
		<h3><?php echo $msgstr["indice_alfa"]; ?></h3>

		<?php if (!empty($update_message)) echo $update_message; ?>

		<?php
		if (!isset($_REQUEST["Opcion"]) or $_REQUEST["Opcion"] != "Guardar") {
			if (isset($_REQUEST["base"]) && $_REQUEST["base"] == "META") {
				Entrada("MetaSearch", $msgstr["metasearch"], $lang, "indice.ix", "META");
			} else {
				$archivo = $db_path . "opac_conf/$lang/bases.dat";
				$fp = file_get_contents_utf8($archivo);
				if ($fp) {
					foreach ($fp as $value) {
						if (trim($value) != "") {
							$x = explode('|', $value);
							if (isset($_REQUEST["base"]) && $_REQUEST["base"] == $x[0]) {
								Entrada(trim($x[0]), trim($x[1]), $lang, trim($x[0]) . ".ix", $x[0]);
							}
						}
					}
				}
			}
		}

		function Entrada($iD, $name, $lang, $file, $base)
		{
			global $msgstr, $db_path;

			echo "<strong>" . $name . "</strong>";
			echo "<div id='$iD'>\n";
			echo "<div style=\"display: flex;\">";

			$file_ix = ($base == "META") ? $db_path . "/opac_conf/" . $lang . "/" . $file : $db_path . $base . "/opac/" . $lang . "/" . $file;
			$lineas = file_exists($file_ix) ? file_get_contents_utf8($file_ix) : [];
		?>

			<div style="flex: 0 0 60%;">
				<form name="<?php echo $iD; ?>Frm" method="post" onsubmit="reindexTable(this)">
					<input type="hidden" name="Opcion" value="Guardar">
					<input type="hidden" name="base" value="<?php echo $base; ?>">
					<input type="hidden" name="file" value="<?php echo $file; ?>">
					<input type="hidden" name="lang" value="<?php echo $lang; ?>">

					<strong><?php echo $file_ix; ?></strong><br>
					<table id="alphaTable" class="table striped">
						<thead>
							<tr>
								<th><?php echo $msgstr["ix_nombre"]; ?></th>
								<th><?php echo $msgstr["ix_pref"]; ?></th>
								<th><?php echo $msgstr["ix_cols"]; ?></th>
								<th><?php echo $msgstr["ix_postings"]; ?></th>
								<th style="text-align: center;"><?php echo $msgstr["actions"] ?? "Actions"; ?></th>
							</tr>
						</thead>
						<tbody id="tbody_alpha_<?php echo $iD; ?>">
							<?php
							foreach ($lineas as $linea) {
								if (trim($linea) == "") continue;
								$partes = explode('|', $linea);
							?>
								<tr class="alpha-row">
									<td><input type="text" name="campo[]" value="<?php echo isset($partes[0]) ? htmlspecialchars($partes[0]) : ''; ?>" size="15"></td>
									<td><input type="text" name="prefijo[]" value="<?php echo isset($partes[1]) ? htmlspecialchars($partes[1]) : ''; ?>" size="30"></td>
									<td>
										<select name="coluna[]">
											<option value=""></option>
											<option value="1" <?php echo (isset($partes[2]) && $partes[2] == '1') ? 'selected' : ''; ?>>1</option>
											<option value="2" <?php echo (isset($partes[2]) && $partes[2] == '2') ? 'selected' : ''; ?>>2</option>
										</select>
									</td>
									<td>
										<input type="hidden" name="posting[]" value="<?php echo (isset($partes[3]) && trim($partes[3]) == 'ALL') ? 'ALL' : ''; ?>">
										<input type="checkbox" <?php echo (isset($partes[3]) && trim($partes[3]) == 'ALL') ? 'checked' : ''; ?> onclick="this.previousElementSibling.value = this.checked ? 'ALL' : ''">
									</td>
									<td style="text-align: center; white-space: nowrap;">
										<button type="button" class="bt bt-gray" onclick="moveRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
										<button type="button" class="bt bt-gray" onclick="moveRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
										<button type="button" class="bt bt-blue" onclick="duplicateRow(this)"><i class="far fa-copy"></i></button>
										<button type="button" class="bt bt-red" onclick="deleteRow(this)"><i class="fas fa-trash-alt"></i></button>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>

					<div style="margin-top: 10px;">
						<button type="button" class="bt bt-gray" onclick="addRowAlpha('tbody_alpha_<?php echo $iD; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"]; ?></button>
						<button type="submit" class="bt bt-green"><i class="fas fa-save"></i> <?php echo $msgstr["save"]; ?></button>
					</div>
				</form>

				<?php if ($base != "META") { ?>
					<div style="margin-top: 30px; border-top: 2px solid #ccc; padding-top: 20px;">
						<h4><?php echo $msgstr["static_dictionary_title"]; ?></h4>
						<p><small><?php echo $msgstr["static_dictionary_help"]; ?></small></p>
						<a href="processar_ifkeys.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>" class="bt bt-green"><?php echo $msgstr["dict_generate_fast"]; ?></a>
						<a href="view_dic.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>" class="bt bt-blue"><?php echo $msgstr["adm_list"]; ?></a>
					</div>
				<?php } ?>
			</div>

			<div style="flex: 1; padding-left: 10px; width: 150px;">
				<button type="button" class="accordion">
					<i class="fas fa-question-circle"></i> <?php echo $msgstr["view_fst_help"]; ?>
				</button>
				<div class="panel p-0">
					<div class="reference-box" style="max-height: 450px;">
						<?php
						if ($base != "" and $base != "META") {
							$fst_file = $db_path . $base . "/data/$base.fst";
							if (file_exists($fst_file)) {
								$fp_campos = file_get_contents_utf8($fst_file);
								echo '<strong>' . $base . '/data/' . $base . '.fst</strong>';
								echo '<table class="table striped">';
								echo '<thead><tr><th>ID</th><th>IT</th><th>Formato</th></tr></thead><tbody>';
								if ($fp_campos) {
									foreach ($fp_campos as $value) {
										if (trim($value) != "") {
											$v = explode(' ', $value, 3);
											echo "<tr>";
											echo "<td width='50'>" . (isset($v[0]) ? $v[0] : '') . "</td>";
											echo "<td width='50'>" . (isset($v[1]) ? $v[1] : '') . "</td>";
											echo "<td>" . (isset($v[2]) ? htmlspecialchars($v[2]) : '') . "</td>";
											echo "</tr>\n";
										}
									}
								}
								echo "</tbody></table>";
							} else {
								echo "<strong><font color=red>" . $msgstr["missing"] . " $base/data/$base.fst</font></strong>";
							}
						} else {
							echo $msgstr["fst_not_applicable"];
						}
						?>
					</div>
				</div>
			</div>
	</div>
</div>
<?php } ?>
</div>
</div>

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

		var selects = row.querySelectorAll("select");
		var cloneSelects = clone.querySelectorAll("select");
		for (var i = 0; i < selects.length; i++) {
			cloneSelects[i].value = selects[i].value;
		}
		row.parentNode.insertBefore(clone, row.nextSibling);
	}

	function addRowAlpha(tbodyId) {
		var tbody = document.getElementById(tbodyId);
		var tr = document.createElement("tr");
		tr.className = "alpha-row";

		tr.innerHTML = `
        <td><input type="text" name="campo[]" value="" size="15"></td>
        <td><input type="text" name="prefijo[]" value="" size="30"></td>
        <td>
            <select name="coluna[]">
                <option value=""></option>
                <option value="1">1</option>
                <option value="2">2</option>
            </select>
        </td>
        <td>
            <input type="hidden" name="posting[]" value="">
            <input type="checkbox" onclick="this.previousElementSibling.value = this.checked ? 'ALL' : ''">
        </td>
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
			var inputs = row.querySelectorAll("input[type='text'], select, input[type='hidden']");
			inputs.forEach(function(input) {
				var name = input.getAttribute("name");
				if (name) {
					var baseName = name.replace(/\[\d*\]$/, "").replace("[]", "");
					input.setAttribute("name", baseName + "[" + index + "]");
				}
			});
		});
	}
</script>

<?php include("../../common/footer.php"); ?>