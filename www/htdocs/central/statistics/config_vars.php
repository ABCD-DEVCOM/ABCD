<?php
/*
* @file        config_vars.php
* @description Statistics Variables Configuration with Assistant and TableEditor
* @author      Refactored by Roger C. Guilherme
*/
session_start();
if (!isset($_SESSION["permiso"])) die;
include("../common/get_post.php");
include("../config.php");

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/statistics.php");

if (isset($charset)) {
	$content_charset = $charset;
} elseif (isset($meta_encoding)) {
	$content_charset = $meta_encoding;
} else {
	$content_charset = "ISO-8859-1";
}
$php_charset = (strtoupper($content_charset) == "ANSI") ? "ISO-8859-1" : $content_charset;

$file_fdt = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $arrHttp["base"] . ".fdt";
if (!file_exists($file_fdt)) {
	$file_fdt = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $arrHttp["base"] . ".fdt";
}
$fdt_processed = array();
if (file_exists($file_fdt)) {
	$raw_fdt = file($file_fdt);
	foreach ($raw_fdt as $line) {
		$line = trim($line);
		if ($line == "") continue;

		$parts = explode('|', $line);
		$type = $parts[0];
		if (in_array($type, array('F', 'T', 'AI', 'M', 'OD'))) {
			$tag = isset($parts[1]) ? $parts[1] : '';
			if ($tag === "") continue;
			$name = isset($parts[2]) ? $parts[2] : '';
			$fdt_processed[$tag] = $name;
		}
	}
}

include("../common/header.php");
?>

<body>
	<?php
	if (isset($arrHttp["encabezado"])) {
		include("../common/institutional_info.php");
		$encabezado = "&encabezado=s";
	} else {
		$encabezado = "";
	}
	?>

	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["stats"] . " - " . $msgstr["stat_cfg_vars"] . ": " . $arrHttp["base"]; ?>
		</div>
		<div class="actions">
			<?php
			if (isset($arrHttp["from"]) and $arrHttp["from"] == "statistics")
				$backtoscript = "tables_generate.php";
			else
				$backtoscript = "../dbadmin/menu_modificardb.php";

			include "../common/inc_back.php";
			$savescript = "javascript:Guardar()";
			include "../common/inc_save.php";
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>

	<?php
	$ayuda = "stats_config_vars.html";
	include "../common/inc_div-helper.php";

	$file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/stat.cfg";
	if (!file_exists($file)) $file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/stat.cfg";
	$fp = file_exists($file) ? file($file) : array();

	$lmp = "";
	$excluir = "";
	$stat_vars = array();

	foreach ($fp as $value) {
		$value = trim($value);
		if ($value != "") {
			$var = explode('|', $value);
			if (isset($var[2]) and $var[2] == "LMP") {
				$lmp = $var[1];
				$excluir = isset($var[3]) ? $var[3] : "";
			} else {
				$stat_vars[] = $var;
			}
		}
	}
	?>

	<div class="middle formContent">
		<form name="stats" method="post" onsubmit="return false;">

			<div class="helper-box" style="background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
				<h5 style="margin-top:0; color: #333; margin-bottom: 10px;"><i class="fas fa-magic"></i> <?php echo $msgstr["stat_ass_var"]; ?></h5>

				<div class="form-row-custom" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
					<div class="form-group-custom" style="flex: 1;">
						<label style="font-weight: bold; font-size: 0.9em; display:block;"><?php echo $msgstr["stat_var_name_tab"]; ?></label>
						<input type="text" id="new_var_name" class="form-control" placeholder="<?php echo $msgstr["stat_ex_author"]; ?>">
					</div>

					<div class="form-group-custom" style="flex: 2;">
						<label style="font-weight: bold; font-size: 0.9em; display:block;"><?php echo $msgstr["stat_db_field_fdt"]; ?></label>
						<select id="new_var_field" class="form-control">
							<option value=""><?php echo $msgstr["stat_sel_field"]; ?></option>
							<?php
							foreach ($fdt_processed as $tag => $name) {
								echo "<option value='$tag'>$tag - " . htmlspecialchars($name, ENT_QUOTES, $php_charset) . "</option>";
							}
							?>
							<option value="CUSTOM"><?php echo $msgstr["stat_other_manual"]; ?></option>
						</select>
					</div>

					<div class="form-group-custom" style="display: flex; align-items: center; height: 35px; gap: 5px;">
						<input type="checkbox" id="new_var_date" value="true">
						<label for="new_var_date" style="font-size: 0.9em; margin: 0;"><?php echo $msgstr["date_field"]; ?></label>
					</div>

					<div class="form-group-custom">
						<button type="button" class="bt bt-blue" onclick="insertHelperRow()">
							<i class="fas fa-plus"></i> <?php echo $msgstr["stat_insert_var"]; ?>
						</button>
					</div>
				</div>
			</div>

			<div class="mb-2">
				<button type="button" class="bt bt-blue" onclick="addBlankRowTop()">
					<i class="fas fa-plus"></i> <?php echo $msgstr["stat_blank_row"]; ?>
				</button>
			</div>

			<table class="striped" id="varsTable" style="width: 100%;">
				<thead>
					<tr>
						<th width="30"></th>
						<th width="30%"><?php echo $msgstr["var"]; ?></th>
						<th width="40%"><?php echo $msgstr["pft_ext"]; ?></th>
						<th width="15%"><?php echo $msgstr["date_field"]; ?></th>
						<th width="100" class="text-center"><?php echo $msgstr["stat_actions"]; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($stat_vars as $var) {
						$nome = isset($var[0]) ? $var[0] : "";
						$pft = isset($var[1]) ? $var[1] : "";
						$is_date = (isset($var[2]) && $var[2] == "true") ? "checked" : "";
						$id_var = isset($var[3]) ? trim($var[3]) : "var_" . uniqid();
					?>
						<tr>
							<td class="drag-handle text-center" style="cursor: move; vertical-align: middle;">
								<input type="hidden" name="row_id" value="<?php echo htmlspecialchars($id_var, ENT_QUOTES, $php_charset); ?>">
								<i class="fas fa-bars text-muted"></i>
							</td>
							<td>
								<input type="text" name="row_nome" value="<?php echo htmlspecialchars($nome, ENT_QUOTES, $php_charset); ?>" class="form-control">
							</td>
							<td>
								<textarea name="row_pft" rows="1" class="form-control" style="width:100%; resize:vertical;"><?php echo htmlspecialchars($pft, ENT_QUOTES, $php_charset); ?></textarea>
							</td>
							<td class="text-center" style="vertical-align: middle;">
								<input type="checkbox" name="row_date" <?php echo $is_date; ?>>
							</td>
							<td nowrap class="text-center">
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="varsEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="varsEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
								<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="varsEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

			<?php if ($arrHttp["base"] == "trans") { ?>
				<div style="margin-top: 30px; border-top: 2px solid #eee; padding-top: 15px;">
					<h5 style="color: #333;"><i class="fas fa-chart-bar"></i> <?php echo $msgstr["stat_spec_config"]; ?></h5>
					<div class="form-row-custom" style="display: flex; gap: 20px;">
						<div class="form-group-custom" style="flex: 2;">
							<label style="font-weight: bold;"><?php echo $msgstr["mostborrowed"]; ?></label>
							<textarea name="lmp" id="lmp" class="form-control" rows="2"><?php echo htmlspecialchars($lmp, ENT_QUOTES, $php_charset); ?></textarea>
						</div>
						<div class="form-group-custom" style="flex: 1;">
							<label style="font-weight: bold;"><?php echo $msgstr["excludetotallt"]; ?></label>
							<input type="text" name="excluir" class="form-control" value="<?php echo htmlspecialchars($excluir, ENT_QUOTES, $php_charset); ?>">
						</div>
					</div>
				</div>
			<?php } else { ?>
				<input type="hidden" name="lmp" id="lmp" value="">
				<input type="hidden" name="excluir" value="">
			<?php } ?>

		</form>

		<?php include("inc_stat_menu.php"); ?>
	</div>

	<form name="enviar" method="post" action="config_vars_update.php">
		<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
		<input type="hidden" name="ValorCapturado">
		<input type="hidden" name="lmp">
		<input type="hidden" name="excluir">
		<?php
		if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=S>\n";
		if (isset($arrHttp["from"])) echo "<input type=hidden name=from value=" . $arrHttp["from"] . ">\n";
		?>
	</form>

	<template id="rowTemplate">
		<tr>
			<td class="drag-handle text-center" style="cursor: move; vertical-align: middle;">
				<input type="hidden" name="row_id" value="">
				<i class="fas fa-bars text-muted"></i>
			</td>
			<td>
				<input type="text" name="row_nome" value="" class="form-control">
			</td>
			<td>
				<textarea name="row_pft" rows="1" class="form-control" style="width:100%; resize:vertical;"></textarea>
			</td>
			<td class="text-center" style="vertical-align: middle;">
				<input type="checkbox" name="row_date">
			</td>
			<td nowrap class="text-center">
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="varsEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="varsEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
				<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="varsEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
	</template>

	<?php include("../common/footer.php"); ?>

	<script src="../../assets/js/Sortable.min.js"></script>
	<script src="../../assets/js/table_editor.js"></script>

	<script>
		const varsEditor = new TableEditor('varsTable', {
			enableDrag: true,
			handleClass: '.drag-handle',
			templateId: 'rowTemplate'
		});

		function generateVarId() {
			return 'var_' + Date.now() + Math.floor(Math.random() * 1000);
		}

		function addBlankRowTop() {
			varsEditor.addRow();
			var tbody = document.querySelector("#varsTable tbody");
			var rows = tbody.querySelectorAll("tr");
			if (rows.length > 0) {
				var newRow = rows[rows.length - 1];
				newRow.querySelector("input[name='row_id']").value = generateVarId();

				if (rows.length > 1) {
					tbody.insertBefore(newRow, tbody.firstChild);
				}
			}
		}

		function insertHelperRow() {
			var name = document.getElementById("new_var_name").value.trim();
			var tag = document.getElementById("new_var_field").value.trim();
			var isDate = document.getElementById("new_var_date").checked;

			if (name === "") {
				alert("<?php echo $msgstr["stat_pls_var_name"]; ?>");
				return;
			}

			var pftFormat = "";
			if (tag !== "" && tag !== "CUSTOM") {
				pftFormat = "v" + tag;
			}

			varsEditor.addRow();

			var tbody = document.querySelector("#varsTable tbody");
			var rows = tbody.querySelectorAll("tr");
			var lastRow = rows[rows.length - 1];

			if (lastRow) {
				lastRow.querySelector("input[name='row_id']").value = generateVarId();
				lastRow.querySelector("input[name='row_nome']").value = name;
				lastRow.querySelector("textarea[name='row_pft']").value = pftFormat;
				lastRow.querySelector("input[name='row_date']").checked = isDate;

				if (rows.length > 1) {
					tbody.insertBefore(lastRow, tbody.firstChild);
				}
			}

			document.getElementById("new_var_name").value = "";
			document.getElementById("new_var_field").value = "";
			document.getElementById("new_var_date").checked = false;
			document.getElementById("new_var_name").focus();
		}

		function Guardar() {
			const content = varsEditor.collectData(function(row) {
				const id = row.querySelector("input[name='row_id']").value.trim();
				const nome = row.querySelector("input[name='row_nome']").value.trim();
				let pft = row.querySelector("textarea[name='row_pft']").value;
				const isDate = row.querySelector("input[name='row_date']").checked ? "true" : "";

				if (nome === "" && pft === "") return null;

				if (nome === "") {
					alert("<?php echo $msgstr["sel_tit"]; ?>");
					throw "Validation Error";
				}
				if (pft === "") {
					alert("<?php echo $msgstr["misspft"]; ?>");
					throw "Validation Error";
				}

				pft = pft.replace(/(\r\n|\n|\r)/gm, " ");

				return nome + "|" + pft + "|" + isDate + "|" + id;
			});

			var lmpField = document.getElementById("lmp");
			if (lmpField && lmpField.value.trim() !== "") {
				document.enviar.lmp.value = lmpField.value.replace(/(\r\n|\n|\r)/gm, " ");
				document.enviar.excluir.value = document.stats.excluir ? document.stats.excluir.value : "";
			}

			document.enviar.ValorCapturado.value = content;
			document.enviar.submit();
		}
	</script>
