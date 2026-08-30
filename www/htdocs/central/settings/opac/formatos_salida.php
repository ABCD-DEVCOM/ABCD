<?php
/*
* @file        formatos_salida.php
* @description Configures the available formats in OPAC.
* @author      Roger C. Guilherme
* @date        2025-11-24
* 
* CHANGE LOG:
* 2026-08-29 Refactored to remove hidden rows and use DOM-based dynamic table management
*/
include("conf_opac_top.php");

$update_message = "";

if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "Guardar") {
	$archivo_conf = $db_path . $_REQUEST['base'] . "/opac/$lang/" . $_REQUEST["file"];
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
			$is_consolida = (isset($_REQUEST["consolida"]) && $key == $_REQUEST["consolida"]) ? 'Y' : '';
			$is_detalhado = (isset($_REQUEST["detalhado"]) && $key == $_REQUEST["detalhado"]) ? 'Y' : '';

			$name_val = isset($nom_idioma[$key]) ? $nom_idioma[$key] : "";
			fwrite($fout, $value . "|" . $name_val . "|" . $is_consolida . "|" . $is_detalhado . "\n");
		}
		fclose($fout);
		$update_message = "<div class=\"alert success\"><strong>" . $archivo_conf . " " . $msgstr["updated"] . "</strong></div>";
	}
}

$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/display-formats";
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
		<h3><?php echo $msgstr["select_formato"]; ?></h3>

		<?php if (!empty($update_message)) echo $update_message; ?>

		<?php
		if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"] == "copiarde") {
			$archivo_conf = $db_path . $base . "/opac/" . $_REQUEST["lang_copiar"] . "/" . $_REQUEST["archivo"];
			copy($archivo_conf, $db_path . $base . "/opac/" . $_REQUEST["lang"] . "/" . $_REQUEST["archivo"]);
			echo "<p><font color=red>" . $db_path . $base . "/opac/$lang/" . $_REQUEST["archivo"] . " " . $msgstr["copiado"] . "</font></p>";
		}
		?>

		<form name="indices" method="post">
			<input type="hidden" name="db_path" value="<?php echo $db_path; ?>">
			<?php
			$archivo_conf = $db_path . "opac_conf/" . $lang . "/bases.dat";
			$fp = file_get_contents_utf8($archivo_conf);
			if (isset($_REQUEST["base"]) && $_REQUEST["base"] == "META") {
				Entrada("MetaSearch", $msgstr["metasearch"], $lang, "formatos.dat", "META");
			} else {
				if ($fp) {
					foreach ($fp as $value) {
						if (trim($value) != "") {
							$x = explode('|', $value);
							if ($x[0] != $_REQUEST["base"]) continue;
							Entrada(trim($x[0]), trim($x[1]), $lang, trim($x[0]) . "_formatos.dat", $x[0]);
							break;
						}
					}
				}
			}
			?>
		</form>

		<form name="copiarde" method="post">
			<input type="hidden" name="db">
			<input type="hidden" name="archivo">
			<input type="hidden" name="Opcion" value="copiarde">
			<input type="hidden" name="lang_copiar">
			<input type="hidden" name="lang" value="<?php echo $_REQUEST["lang"] ?>">
		</form>
	</div>
</div>

<script>
	function Copiarde(db, db_name, lang, file) {
		var ln = document.getElementById("lang_copy");
		document.copiarde.lang_copiar.value = ln.options[ln.selectedIndex].value;
		document.copiarde.db.value = db;
		document.copiarde.archivo.value = file;
		document.copiarde.submit();
	}

	function EditarPft(Pft) {
		if (Pft == "") {
			alert("<?php echo $msgstr['pft_name_empty'] ?? 'Informe o nome do PFT'; ?>");
			return;
		}
		var params = "scrollbars=auto,resizable=yes,status=no,location=no,toolbar=no,menubar=no,width=800,height=600,left=0,top=0";
		var msgwin = window.open("editar_pft.php?Pft=" + Pft + "&base=<?php echo $_REQUEST["base"] . "&lang=" . $_REQUEST["lang"] . "&db_path=" . $_REQUEST["db_path"]; ?>", 'pft', params);
		msgwin.focus();
	}

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
		// Uncheck radios in the clone to avoid layout conflicts before saving
		var radios = clone.querySelectorAll("input[type='radio']");
		radios.forEach(function(radio) {
			radio.checked = false;
		});
		row.parentNode.insertBefore(clone, row.nextSibling);
	}

	function addRowFormatos(tbodyId) {
		var tbody = document.getElementById(tbodyId);
		var tr = document.createElement("tr");

		tr.innerHTML = `
        <td><input type="text" name="conf_lc_0" size="5" value=""></td>
        <td><input type="text" name="conf_ln_0" size="30" value=""></td>
        <td align="center"><input type="radio" name="consolida" value="0"></td>
        <td align="center"><input type="radio" name="detalhado" value="0"></td>
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

			// Reindex texts
			var inputs = row.querySelectorAll("input[type='text']");
			inputs.forEach(function(input) {
				var name = input.getAttribute("name");
				if (name && name.startsWith("conf_")) {
					var parts = name.split("_");
					parts[2] = rowIndex;
					input.setAttribute("name", parts.join("_"));
				}
			});

			// Reindex radios
			var radios = row.querySelectorAll("input[type='radio']");
			radios.forEach(function(radio) {
				radio.value = rowIndex;
			});
		});
	}
</script>

<?php
function CopiarDe($iD, $name, $lang, $file)
{
	global $db_path, $msgstr;
	echo "<br>" . $msgstr["copiar_de"] . " ";
	echo "<select name=lang_copy onchange='Copiarde(\"$iD\",\"$name\",\"$lang\",\"$file\")' id=lang_copy > ";
	echo "<option></option>\n";
	$fp = file_get_contents_utf8($db_path . "opac_conf/$lang/lang.tab");
	if ($fp) {
		foreach ($fp as $value) {
			if (trim($value) != "") {
				$a = explode("=", $value);
				echo "<option value=" . $a[0] . ">" . trim($a[1]) . "</option>";
			}
		}
	}
	echo "</select><br>";
}

function Entrada($iD, $name, $lang, $file, $base)
{
	global $msgstr, $db_path;

	echo "<strong>" . $name;
	if ($base != "" and $base != "META") echo  " ($base)";
	echo "</strong>";
	echo "<div id='$iD'>\n";
	echo "<div style=\"display: flex;\">";

	$cuenta = 0;
	$fp_campos = [];

	if ($base != "" and $base != "META") {
		$file_campos = $db_path . $base . "/pfts/" . $_REQUEST["lang"] . "/formatos.dat";
		$fp_campos = file_get_contents_utf8($file_campos);
		if (!$fp_campos) {
			$file_campos_en = $db_path . $base . "/pfts/en/formatos.dat";
			$fp_campos = file_get_contents_utf8($file_campos_en);
		}
		$cuenta = $fp_campos ? count($fp_campos) : 0;
	}
?>

	<div style="flex: 0 0 70%;">
		<form name="<?php echo $iD; ?>Frm" method="post" onsubmit="reindexTable(this)">
			<input type="hidden" name="Opcion" value="Guardar">
			<input type="hidden" name="base" value="<?php echo $base; ?>">
			<input type="hidden" name="file" value="<?php echo $file; ?>">
			<input type="hidden" name="lang" value="<?php echo $lang; ?>">

			<?php
			$config_file_path = ($base != "META") ? $db_path . $base . "/opac/$lang/$file" : $db_path . "opac_conf/$lang/$file";
			echo "<strong>" . $config_file_path . "</strong><br>";
			echo "<small>" . $msgstr["no_pft_ext"] . "</small><br>";

			$fp = file_exists($config_file_path) ? file_get_contents_utf8($config_file_path) : [];
			$ix = 0;
			?>

			<table class="table striped" id="formatos_table_<?php echo $iD; ?>">
				<thead>
					<tr>
						<th>Pft</th>
						<th><?php echo $msgstr["nombre"]; ?></th>
						<th width="50" style="text-align:center"><?php echo $msgstr["pft_meta"]; ?></th>
						<th width="50" style="text-align:center"><?php echo $msgstr["cfg_view_detail"]; ?></th>
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
								$pft_name = isset($l[0]) ? htmlspecialchars(trim($l[0])) : '';
								$pft_label = isset($l[1]) ? htmlspecialchars(trim($l[1])) : '';
								$is_consolidado = (isset($l[2]) and trim($l[2]) == "Y");
								$is_detalhado = (isset($l[3]) and trim($l[3]) == "Y");

								echo "<tr>";
								echo "<td><input type=text name=conf_lc_" . $ix . " size=5 value=\"" . $pft_name . "\"></td>";
								echo "<td><input type=text name=conf_ln_" . $ix . " size=30 value=\"" . $pft_label . "\"></td>";
								echo "<td align='center'><input type=radio name=consolida value=$ix" . ($is_consolidado ? " checked" : "") . "></td>";
								echo "<td align='center'><input type=radio name=detalhado value=$ix" . ($is_detalhado ? " checked" : "") . "></td>";

								echo "<td style=\"text-align: center; white-space: nowrap;\">";
								if ($base != "META" && $pft_name != "") {
									echo "<a class='bt bt-blue' href=\"javascript:EditarPft('" . $l[0] . "')\" title='" . $msgstr["edit"] . "'><i class='fas fa-edit'></i></a> ";
								}
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
				<button type="button" class="bt-gray" onclick="addRowFormatos('tbody_<?php echo $iD; ?>')"><i class="fas fa-plus"></i> <?php echo $msgstr["cfg_add_line"]; ?></button>
			</div>
			<button type="submit" class="bt-green m-2"><i class="fas fa-save"></i> <?php echo $msgstr["save"]; ?></button>
		</form>
	</div>

	<div style="flex: 1; padding-left: 10px; width: 150px;">
		<?php if ($cuenta > 0 && $fp_campos) { ?>
			<button type="button" class="accordion">
				<i class="fas fa-question-circle"></i> <?php echo $msgstr["view_formats_help"]; ?>
			</button>
			<div class="panel p-0">
				<div class="reference-box" style="max-height: 450px;">
					<strong><?php echo $base . "/pfts/" . $_REQUEST["lang"] . "/formatos.dat"; ?></strong><br>
					<table class="table striped">
						<thead>
							<tr>
								<th>Pft</th>
								<th><?php echo $msgstr["nombre"]; ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($fp_campos as $value) {
								$value = trim($value);
								if ($value != "") {
									$v = explode('|', $value);
									echo "<tr><td>" . (isset($v[0]) ? htmlspecialchars($v[0]) : '') . "</td><td>" . (isset($v[1]) ? htmlspecialchars($v[1]) : '') . "</td></tr>\n";
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		<?php } ?>
	</div>
	</div>
	</div>
<?php
}
include("../../common/footer.php");
?>