<?php
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//foreach ($_REQUEST as $key=>$value)      echo "$key=>".urldecode($value)."<br>";
unset($_REQUEST["usuario"]);
$desde = 1;
$count = "";
$accion = "";

if (isset($_REQUEST["sendto"]) and $_REQUEST["sendto"] != "") $_REQUEST["cookie"] = $_REQUEST["sendto"];

$list = explode("|", $_REQUEST["cookie"]);
$ll = explode("_", $list[0]);

if (isset($_REQUEST["Accion"])) $accion = trim($_REQUEST["Accion"]);

if (isset($_REQUEST["db_path"])) {
	$ptdb = '&db_path=' . $_REQUEST["db_path"];
} else {
	$ptdb = "";
}
?>

<div id="page">
	<div id="content">
		<?php

		// Safe initialization at the top of the file
		$cookie_data = $_REQUEST["cookie"] ?? '';
		$list = explode("|", $cookie_data);
		$ll = explode("_", $list[0] ?? '');

		// Safe parsing of the selected records
		$sel_db = []; // Always initialize arrays before using them

		foreach ($list as $value) {
			$value = trim($value);
			if ($value != "") {
				$x = explode('_=', $value);
				$sel_db[$x[1]] = $x[1];
			}
		}
		?>
		<div class="d-flex justify-content-between py-3 my-3">
			<?php
			$backButtonHTML = '<a href="javascript:history.back()" class="btn btn-secondary" title="' . $msgstr["back"] . '"><i class="fas fa-arrow-left"></i> ' . $msgstr["back"] . '</a>';
			echo "<h2>" . $msgstr["front_records_selected"] . "</h2> " . $backButtonHTML;
			?>
		</div>

		<div id="myMail" class="card bg-white p-2 my-4" style="display:<?php if ($accion == "mail_one") echo "block"; else echo "none"; ?>;">
			<?php include($Web_Dir . "components/mail_form.php"); ?>
		</div>
		<div id="myReserve" class="card bg-white p-2 my-4" style="display:<?php if ($accion == "reserve_one") echo "block"; else echo "none"; ?>;">
			<?php include($Web_Dir . "reserve_iframe.php") ?>
		</div>

		<?php if ($accion != "mail_one" and $accion != "print_one" and $accion != "reserve_one") { ?>

			<div class="btn-toolbar" role="toolbar">
				<?php
				$selectionToolbar = new SelectionButtons($db_path, $lang, $msgstr);
				$showReserve = (isset($WebReservation) and $WebReservation == "Y");
				echo $selectionToolbar->render($showReserve);
				?>
			</div>
			<hr>
		<?php } ?>

		<?php if (isset($_REQUEST["cookie"])) echo ShowSelection(); ?>

		<?php if (isset($accion) and ($accion == "print") or ($accion == "print_one")) { ?>
			<script>
				window.print()
			</script>

		<?php
			foreach ($_REQUEST as $var => $value) {
				$_SESSION[$var] = $value;
			}
		}
		?>
	</div>
</div>