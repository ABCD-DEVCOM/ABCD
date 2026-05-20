<?php
session_start();
if (!isset($_SESSION["permiso"])) die;
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];
include("../lang/dbadmin.php");
include("../lang/statistics.php");
include("../common/header.php");

if (isset($arrHttp["encabezado"])) {
	include("../common/institutional_info.php");
	$encabezado = "&encabezado=s";
} else {
	$encabezado = "";
}
?>

<body>
	<div class="sectionInfo">
		<div class="breadcrumb"><?php echo $msgstr["stats_conf"] . ": " . $arrHttp["base"]; ?></div>
		<div class="actions">
			<?php
			if (isset($arrHttp["from"]) and $arrHttp["from"] == "statistics") $backtoscript = "tables_generate.php";
			else $backtoscript = "../dbadmin/menu_modificardb.php";
			include "../common/inc_back.php";
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>
	<?php
	$ayuda = "stats_config_vars.html";
	include "../common/inc_div-helper.php";
	?>
	<div class="middle form">
		<div class="formContent">
			<?php
			$file = $db_path . $arrHttp["base"] . "/def/" . $lang . "/stat.cfg";
			$fp = fopen($file, "w");
			if (!isset($arrHttp["ValorCapturado"]))  $arrHttp["ValorCapturado"] = "";
			$arrHttp["ValorCapturado"] = stripslashes($arrHttp["ValorCapturado"]);
			$vc = explode("\n", $arrHttp["ValorCapturado"]);
			foreach ($vc as $value) {
				echo "$value<br>";
				if (trim($value) != "")
					$r = fwrite($fp, $value . "\n");
			}
			if (isset($_REQUEST["lmp"]) and $_REQUEST["lmp"] != "") {
				$excluir = "";
				if (isset($_REQUEST["excluir"])) {
					$excluir = trim($_REQUEST["excluir"]);
				}
				$r = fwrite($fp, "Most Borrowed|" . $_REQUEST["lmp"] . "|LMP|$excluir\n");
			}
			$r = fclose($fp);
			echo "<br><h4>" . $arrHttp["base"] . "/" . $lang . "/def/stat.cfg" . " " . $msgstr["updated"] . "</h4>";

			include("inc_stat_menu.php");
			?>
		</div>
	</div>
	<?php include("../common/footer.php"); ?>