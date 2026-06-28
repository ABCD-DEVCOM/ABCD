<?php
/* Modifications
2024-04-01 fho4abcd stylesheet from assets + setImagePath + redesign to remove the mix of html and dhtmlx script
2026-06-28 rogercgui Refactored: Removed dhtmlXGrid, native HTML table with generic JS, PRG pattern added, UI fixes
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
include("../lang/prestamo.php");

include("../common/header.php");

$encabezado = "";
include("../common/institutional_info.php");
?>
<script src="../dataentry/js/abcd_grid.js"></script>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["typeofitems"]; ?>
	</div>
	<div class="actions">
		<?php
		$ayuda = "/circulation/loans_typeofitems.html";
		$backtoscript = "configure_menu.php?encabezado=s";
		$savescript = "javascript:Enviar()";
		include "../common/inc_back.php";
		include "../common/inc_save.php";
		?>
	</div>
	<div class="spacer">&#160;</div>
</div>

<?php include "../common/inc_div-helper.php"; ?>

<?php
// Visual success indicator for the PRG, correctly positioned and with Auto-Hide enabled
if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
?>
	<div id="msg-success" style="background-color: var(--abcd-green, #28a745); color: white; padding: 12px 20px; margin: 15px; border-radius: 4px; text-align: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: opacity 0.5s ease;">
		<i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo isset($msgstr["updated"]) ? $msgstr["updated"] : "Dados salvos com sucesso!"; ?>
	</div>
	<script>
		setTimeout(function() {
			var msgDiv = document.getElementById('msg-success');
			if (msgDiv) {
				msgDiv.style.opacity = '0';
				setTimeout(function() {
					msgDiv.style.display = 'none';
				}, 500);
			}
			// Clears the URL silently to prevent the message from reappearing when you press F5
			window.history.replaceState({}, document.title, window.location.pathname);
		}, 3000);
	</script>
<?php
}
?>

<div class="middle form">
	<div class="formContent">
		<br>
		<a class="bt bt-blue mb-2" href="javascript:void(0)" onclick="addEmptyRow('fstBody', 'rowTemplate', 'BEFORE')">
			<i class="fas fa-plus"></i> <?php echo $msgstr["addrowbef"] ?>
		</a>
		<a class="bt bt-blue mb-2" href="javascript:void(0)" onclick="addEmptyRow('fstBody', 'rowTemplate', 'AFTER')">
			<i class="fas fa-plus"></i> <?php echo $msgstr["addrowaf"] ?>
		</a>
		<br><br>

		<?php
		unset($fp);
		$archivo = $db_path . "circulation/def/" . $_SESSION["lang"] . "/items.tab";
		if (!file_exists($archivo)) $archivo = $db_path . "circulation/def/" . $lang_db . "/items.tab";
		if (file_exists($archivo)) {
			$fp = file($archivo);
		} else {
			$fp = array();
			for ($i = 0; $i < 5; $i++) {
				$fp[$i] = '||';
			}
		}
		?>

		<form name="typeofitems_form" id="typeofitemsForm" method="post" onsubmit="return false;">
			<div class="row m-0">
				<div class="col-12 p-0">
					<table class="table striped table-fst" id="fstTable" style="width: 100%;">
						<thead>
							<tr>
								<th width="30%"><?php echo $msgstr["item"]; ?></th>
								<th width="55%"><?php echo $msgstr["description"]; ?></th>
								<th width="15%" style="text-align: center;"><?php echo $msgstr["actions"] ?? "Ações"; ?></th>
							</tr>
						</thead>
						<tbody id="fstBody">
							<?php
							foreach ($fp as $value) {
								$value = trim($value);
								if ($value === "") continue;

								$value .= "||"; // Ensures the minimum separators
								$t = explode("|", $value);
								$item = trim($t[0] ?? "");
								$description = trim($t[1] ?? "");

								echo "<tr class='fst-row'>";
								echo "<td><input type='text' name='row_item' value='" . $item . "' style='width: 100%; box-sizing: border-box;'></td>";
								echo "<td><input type='text' name='row_description' value='" . $description . "' style='width: 100%; box-sizing: border-box;'></td>";

								echo "<td class='actions-cell' style='text-align: center;'>";
								echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, -1)'><i class='fas fa-arrow-up'></i></button> ";
								echo "<button type='button' class='bt bt-gray' onclick='moveRow(this, 1)'><i class='fas fa-arrow-down'></i></button> ";
								echo "<button type='button' class='bt bt-blue' onclick='duplicateRow(this)'><i class='far fa-copy'></i></button> ";
								echo "<button type='button' class='bt bt-red' onclick='deleteRow(this)'><i class='fas fa-trash-alt'></i></button>";
								echo "</td>";
								echo "</tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</form>

		<form name="forma1" action="typeofitems_update.php" method="post">
			<input type="hidden" name="ValorCapturado">
			<input type="hidden" name="desc">
			<input type="hidden" name="Opcion" value="">
			<input type="hidden" name="base" value="items">
		</form>

	</div>
</div>

<template id="rowTemplate">
	<tr class="fst-row">
		<td><input type="text" name="row_item" value="" style="width: 100%; box-sizing: border-box;"></td>
		<td><input type="text" name="row_description" value="" style="width: 100%; box-sizing: border-box;"></td>
		<td class="actions-cell" style="text-align: center;">
			<button type="button" class="bt bt-gray" onclick="moveRow(this, -1)"><i class="fas fa-arrow-up"></i></button>
			<button type="button" class="bt bt-gray" onclick="moveRow(this, 1)"><i class="fas fa-arrow-down"></i></button>
			<button type="button" class="bt bt-blue" onclick="duplicateRow(this)"><i class="far fa-copy"></i></button>
			<button type="button" class="bt bt-red" onclick="deleteRow(this)"><i class="fas fa-trash-alt"></i></button>
		</td>
	</tr>
</template>

<script>
	function Capturar_Grid() {
		var rows = document.querySelectorAll(".fst-row");
		var VC = "";

		rows.forEach(function(row) {
			var item = row.querySelector("input[name='row_item']").value.trim();
			var description = row.querySelector("input[name='row_description']").value.trim();

			if (item !== "") {
				if (VC !== "") VC += "\n";
				VC += item + "|" + description + "|";
			}
		});

		return VC;
	}

	function Enviar() {
		document.forma1.ValorCapturado.value = Capturar_Grid();
		document.forma1.submit();
	}
</script>

<?php include("../common/footer.php"); ?>