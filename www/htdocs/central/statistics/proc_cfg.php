<?php
/*
* @file        proc_cfg.php
* @description Process Configuration (Groups of Tables) with TableEditor
* @author      Refactored by Roger C. Guilherme
* @date        2026-05-20
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

$tabs_file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tabs.cfg";
if (!file_exists($tabs_file)) $tabs_file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tabs.cfg";

$available_tables = array();
if (file_exists($tabs_file)) {
	$fp = file($tabs_file);
	foreach ($fp as $line) {
		$line = trim($line);
		if ($line != "") {
			$t = explode('|', $line);
			$nome_tab = isset($t[0]) ? trim($t[0]) : "";
			$id_tab = (isset($t[3]) && trim($t[3]) != "") ? trim($t[3]) : $nome_tab;

			$available_tables[] = array(
				'id'   => htmlspecialchars($id_tab, ENT_QUOTES, $php_charset),
				'nome' => htmlspecialchars($nome_tab, ENT_QUOTES, $php_charset)
			);
		}
	}
}

$proc_file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/proc.cfg";
if (!file_exists($proc_file)) $proc_file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/proc.cfg";

$proc_data = array();
if (file_exists($proc_file)) {
	$fp = file($proc_file);
	foreach ($fp as $line) {
		$line = trim($line);
		if ($line != "") {
			$t = explode('||', $line);
			$proc_data[] = array(
				'titulo' => isset($t[0]) ? htmlspecialchars($t[0], ENT_QUOTES, $php_charset) : "",
				'tables' => isset($t[1]) ? explode('|', trim($t[1], '|')) : array(),
				'id'     => isset($t[2]) ? trim($t[2]) : "proc_" . uniqid()
			);
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
			<?php echo $msgstr["stats"] . " - " . $msgstr["stat_cfg_procs"] . ": " . $arrHttp["base"]; ?>
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
	$ayuda = "stats_config_procs.html";
	include "../common/inc_div-helper.php";
	?>

	<div class="middle formContent">
		<form name="stats" method="post" onsubmit="return false;">

			<div class="mb-2">
				<button type="button" class="bt bt-blue" onclick="addBlankRowTop()">
					<i class="fas fa-plus"></i> <?php echo $msgstr["add"]; ?>
				</button>
			</div>

			<table class="striped" id="procTable" style="width: 100%;">
				<thead>
					<tr>
						<th width="30"></th>
						<th width="35%"><?php echo $msgstr["title"]; ?></th>
						<th width="55%"><?php echo $msgstr["tab_list"]; ?></th>
						<th width="100" class="text-center"><?php echo $msgstr["stat_actions"] ?? "Actions"; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($proc_data as $proc) { ?>
						<tr>
							<td class="drag-handle text-center" style="cursor: move; vertical-align: middle;">
								<input type="hidden" name="row_id" value="<?php echo $proc['id']; ?>">
								<i class="fas fa-bars text-muted"></i>
							</td>
							<td valign="top">
								<input type="text" name="row_tit" value="<?php echo $proc['titulo']; ?>" class="form-control">
							</td>
							<td>
								<small><?php echo $msgstr["sel_multiple"]; ?></small>
								<select name="row_tabs" class="form-control" multiple size="6" style="height: auto;">
									<?php foreach ($available_tables as $tab) {
										$selected = (in_array($tab['id'], $proc['tables']) || in_array($tab['nome'], $proc['tables'])) ? "selected" : "";
										echo "<option value='{$tab['id']}' $selected>{$tab['nome']}</option>";
									} ?>
								</select>
							</td>
							<td nowrap class="text-center" valign="top">
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="procEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="procEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
								<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="procEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>

		<?php include("inc_stat_menu.php"); ?>
	</div>

	<form name="enviar" method="post" action="proc_cfg_update.php">
		<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
		<input type="hidden" name="ValorCapturado">
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
			<td valign="top">
				<input type="text" name="row_tit" value="" class="form-control">
			</td>
			<td>
				<small><?php echo $msgstr["sel_multiple"]; ?></small>
				<select name="row_tabs" class="form-control" multiple size="6" style="height: auto;">
					<?php foreach ($available_tables as $tab) {
						echo "<option value='{$tab['id']}'>{$tab['nome']}</option>";
					} ?>
				</select>
			</td>
			<td nowrap class="text-center" valign="top">
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="procEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="procEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
				<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="procEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
	</template>

	<?php include("../common/footer.php"); ?>

	<script src="../../assets/js/Sortable.min.js"></script>
	<script src="../../assets/js/table_editor.js"></script>

	<script>
		const procEditor = new TableEditor('procTable', {
			enableDrag: true,
			handleClass: '.drag-handle',
			templateId: 'rowTemplate'
		});

		function generateProcId() {
			return 'proc_' + Date.now() + Math.floor(Math.random() * 1000);
		}

		function addBlankRowTop() {
			procEditor.addRow();
			var tbody = document.querySelector("#procTable tbody");
			var rows = tbody.querySelectorAll("tr");
			if (rows.length > 0) {
				var newRow = rows[rows.length - 1];
				newRow.querySelector("input[name='row_id']").value = generateProcId();
				if (rows.length > 1) {
					tbody.insertBefore(newRow, tbody.firstChild);
				}
			}
		}

		if (document.querySelectorAll("#procTable tbody tr").length === 0) {
			addBlankRowTop();
		}

		function Guardar() {
			const content = procEditor.collectData(function(row) {
				const id = row.querySelector("input[name='row_id']").value.trim();
				const titulo = row.querySelector("input[name='row_tit']").value.trim();
				const selectTabs = row.querySelector("select[name='row_tabs']");

				let selectedOptions = Array.from(selectTabs.selectedOptions).map(opt => opt.value);
				let tablesString = selectedOptions.join('|');

				if (titulo === "" && tablesString === "") return null;

				if (titulo === "") {
					alert("<?php echo $msgstr["tit_req"]; ?>");
					throw "Validation Error";
				}
				if (tablesString === "") {
					alert("<?php echo $msgstr["tab_req"]; ?>");
					throw "Validation Error";
				}

				return titulo + "||" + tablesString + "||" + id;
			});

			document.enviar.ValorCapturado.value = content;
			document.enviar.submit();
		}
	</script>
