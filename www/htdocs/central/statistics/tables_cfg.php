<?php
/*
* @file        tables_cfg.php
* @description Relational Tables Configuration (tabs.cfg) with TableEditor
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

$stat_file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/stat.cfg";
if (!file_exists($stat_file)) $stat_file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/stat.cfg";

$stat_vars = array();
$error_stat = false;

if (!file_exists($stat_file)) {
	$error_stat = true;
} else {
	$fp = file($stat_file);
	foreach ($fp as $line) {
		$line = trim($line);
		if ($line != "") {
			$t = explode('|', $line);
			$nome = isset($t[0]) ? trim($t[0]) : "";
			$id = (isset($t[3]) && trim($t[3]) != "") ? trim($t[3]) : $nome;
			$isLMP = (isset($t[2]) && $t[2] == "LMP");

			$stat_vars[] = array(
				'id' => htmlspecialchars($id, ENT_QUOTES, $php_charset),
				'nome' => htmlspecialchars($nome, ENT_QUOTES, $php_charset),
				'isLMP' => $isLMP
			);
		}
	}
}

$tabs_file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tabs.cfg";
if (!file_exists($tabs_file)) $tabs_file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tabs.cfg";

$tabs_data = array();
if (file_exists($tabs_file)) {
	$fp = file($tabs_file);
	foreach ($fp as $line) {
		$line = trim($line);
		if ($line != "") {
			$t = explode('|', $line);
			$id_tab = (isset($t[3]) && trim($t[3]) != "") ? trim($t[3]) : "tab_" . uniqid();
			$expr = isset($t[4]) ? htmlspecialchars($t[4], ENT_QUOTES, $php_charset) : "";

			$tabs_data[] = array(
				'titulo' => isset($t[0]) ? htmlspecialchars($t[0], ENT_QUOTES, $php_charset) : "",
				'row' => isset($t[1]) ? trim($t[1]) : "",
				'col' => isset($t[2]) ? trim($t[2]) : "",
				'id'  => htmlspecialchars($id_tab, ENT_QUOTES, $php_charset),
				'expr' => $expr
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
			<?php echo $msgstr["stats_conf"] . " - " . $msgstr["stat_cfg_tabs"] . ": " . $arrHttp["base"]; ?>
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
	$ayuda = "stats_config_tabs.html";
	include "../common/inc_div-helper.php";
	?>

	<div class="middle formContent">

		<?php if ($error_stat) {
			$urlforvardef = "../statistics/config_vars.php?base=" . $arrHttp["base"] . "&Opcion=Update&from=statistics" . $encabezado;
		?>
			<div style="padding:20px; color:#d9534f; background:#f9f2f2; border:1px solid #dca7a7; border-radius:4px;">
				<h4><i class="fas fa-exclamation-triangle"></i> <?php echo $msgstr["mis_statscfg"]; ?></h4>
				<a href="<?php echo $urlforvardef; ?>" class="bt bt-blue" style="margin-top:10px;">
					<?php echo $msgstr["stats"] . " - " . $msgstr["var_list"]; ?>
				</a>
			</div>
		<?php die;
		} ?>

		<form name="stats" method="post" onsubmit="return false;">

			<div class="mb-2">
				<button type="button" class="bt bt-blue" onclick="addBlankRowTop()">
					<i class="fas fa-plus"></i> <?php echo isset($msgstr["stat_blank_row"]) ? $msgstr["stat_blank_row"] : "Adicionar Tabela"; ?>
				</button>
			</div>

			<table class="striped" id="tabsTable" style="width: 100%;">
				<thead>
					<tr>
						<th width="30"></th>
						<th width="30%"><?php echo $msgstr["title"]; ?></th>
						<th width="20%"><?php echo $msgstr["stat_rows_by"]; ?></th>
						<th width="20%"><?php echo $msgstr["stat_cols_by"]; ?></th>
						<th width="20%">Expressão de Busca (Opcional)</th>
						<th width="10%" class="text-center"><?php echo isset($msgstr["stat_actions"]) ? $msgstr["stat_actions"] : "Ações"; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($tabs_data as $tab) { ?>
						<tr>
							<td class="drag-handle text-center" style="cursor: move; vertical-align: middle;">
								<input type="hidden" name="row_id" value="<?php echo $tab['id']; ?>">
								<i class="fas fa-bars text-muted"></i>
							</td>
							<td valign="top">
								<input type="text" name="row_tit" value="<?php echo $tab['titulo']; ?>" class="form-control">
							</td>
							<td valign="top">
								<select name="row_row" class="form-control">
									<option value=""></option>
									<?php foreach ($stat_vars as $v) {
										$selected = ($tab['row'] === $v['id'] || $tab['row'] === $v['nome']) ? "selected" : "";
										echo "<option value='{$v['id']}' data-islmp='{$v['isLMP']}' $selected>{$v['nome']}</option>";
									} ?>
								</select>
							</td>
							<td valign="top">
								<select name="row_col" class="form-control">
									<option value=""></option>
									<?php foreach ($stat_vars as $v) {
										$selected = ($tab['col'] === $v['id'] || $tab['col'] === $v['nome']) ? "selected" : "";
										echo "<option value='{$v['id']}' data-islmp='{$v['isLMP']}' $selected>{$v['nome']}</option>";
									} ?>
								</select>
							</td>
							<td valign="top">
								<input type="text" name="row_expr" value="<?php echo $tab['expr']; ?>" class="form-control" placeholder='Ex: ("OPED_2026$")'>
							</td>
							<td nowrap class="text-center" valign="top">
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="tabsEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
								<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="tabsEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
								<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="tabsEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>

		<?php include("inc_stat_menu.php"); ?>
	</div>

	<form name="enviar" method="post" action="tables_cfg_update.php">
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
			<td valign="top">
				<select name="row_row" class="form-control">
					<option value=""></option>
					<?php foreach ($stat_vars as $v) {
						echo "<option value='{$v['id']}' data-islmp='{$v['isLMP']}'>{$v['nome']}</option>";
					} ?>
				</select>
			</td>
			<td valign="top">
				<select name="row_col" class="form-control">
					<option value=""></option>
					<?php foreach ($stat_vars as $v) {
						echo "<option value='{$v['id']}' data-islmp='{$v['isLMP']}'>{$v['nome']}</option>";
					} ?>
				</select>
			</td>
			<td valign="top">
				<input type="text" name="row_expr" value="" class="form-control" placeholder='Ex: ("OPED_2026$")'>
			</td>
			<td nowrap class="text-center" valign="top">
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="tabsEditor.moveRow(this, -1)"><i class="fas fa-arrow-up"></i></a>
				<a class="bt bt-gray bt-mini" href="javascript:void(0)" onclick="tabsEditor.moveRow(this, 1)"><i class="fas fa-arrow-down"></i></a>
				<a class="bt bt-red bt-mini" href="javascript:void(0)" onclick="tabsEditor.deleteRow(this)"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
	</template>

	<?php include("../common/footer.php"); ?>

	<script src="../../assets/js/Sortable.min.js"></script>
	<script src="../../assets/js/table_editor.js"></script>

	<script>
		const tabsEditor = new TableEditor('tabsTable', {
			enableDrag: true,
			handleClass: '.drag-handle',
			templateId: 'rowTemplate'
		});

		function generateTabId() {
			return 'tab_' + Date.now() + Math.floor(Math.random() * 1000);
		}

		function addBlankRowTop() {
			tabsEditor.addRow();
			var tbody = document.querySelector("#tabsTable tbody");
			var rows = tbody.querySelectorAll("tr");
			if (rows.length > 0) {
				var newRow = rows[rows.length - 1];
				newRow.querySelector("input[name='row_id']").value = generateTabId();

				if (rows.length > 1) {
					tbody.insertBefore(newRow, tbody.firstChild);
				}
			}
		}

		if (document.querySelectorAll("#tabsTable tbody tr").length === 0) {
			addBlankRowTop();
		}

		function Guardar() {
			const content = tabsEditor.collectData(function(row) {
				const id = row.querySelector("input[name='row_id']").value.trim();
				const titulo = row.querySelector("input[name='row_tit']").value.trim();
				const selectRow = row.querySelector("select[name='row_row']");
				const selectCol = row.querySelector("select[name='row_col']");
				const expr = row.querySelector("input[name='row_expr']").value.trim();

				const rowVal = selectRow.value;
				const colVal = selectCol.value;

				const isColLmp = selectCol.options[selectCol.selectedIndex]?.getAttribute('data-islmp') === '1';

				if (titulo === "" && rowVal === "" && colVal === "") return null;

				if (titulo === "") {
					alert("<?php echo $msgstr["sel_tit"]; ?>");
					throw "Validation Error";
				}
				if (rowVal === "" && colVal === "") {
					alert("<?php echo $msgstr["sel_rc"]; ?>");
					throw "Validation Error";
				}
				if (isColLmp) {
					alert("Os mais emprestados (Los más prestados) só podem aparecer como Linha.");
					throw "Validation Error";
				}

				return titulo + "|" + rowVal + "|" + colVal + "|" + id + "|" + expr;
			});

			document.enviar.ValorCapturado.value = content;
			document.enviar.submit();
		}
	</script>
