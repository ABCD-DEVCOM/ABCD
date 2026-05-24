<?php

/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      typeofrecs.php
 * @desc:
 * @author:    Guilda Ascencio
 * @since:     20091203
 * @version:   1.0
 * 
 * change log:
 * 20240610 - Updated to use Bootstrap 5 and Font Awesome 5, and to improve the layout and styling of the page.
 */

session_start();
include("../common/get_post.php");
include("../config.php");
include("../common/header.php");
require_once("../lang/admin.php");

$ayuda = "typeofrecs.html";
include('../common/inc_div-helper.php');


//READ THE DATAENTRY WORKSHEET TO DETERMINE THE AVAILABILITY FOR THE OPERATOR
if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/formatos.wks")) {
	$fp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/formatos.wks");
} else {
	if (file_exists($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/formatos.wks"))
		$fp = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/formatos.wks");
}
$i = 0;
$wks_p = array();
if (isset($fp)) {
	foreach ($fp as $linea) {
		if (trim($linea) != "") {
			$linea = trim($linea);
			$l = explode('|', $linea);
			$cod = trim($l[0]);
			$nom = trim($l[1]);
			if (
				isset($_SESSION["permiso"][$arrHttp["base"] . "_fmt_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_fmt_" . $cod])
				or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"] . "_CENTRAL_ALL"])
			) {
				$i = $i + 1;
				$wks_p[$cod] = "Y";
			}
		}
	}
}

$tr = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/typeofrecord.tab";

if (!file_exists($tr))  $tr = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/typeofrecord.tab";
$fp = file($tr);

?>
<div class="middle form">
	<div class="formContent py-4" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
		<h3 class="mb-5"><?php echo $msgstr["typeofr"] ?></h3>

		<div style="display: flex; flex-direction: column; gap: 10px; width: 100%; max-width: 400px;">
			<?php
			$ix = 0;
			$tr = "";
			$nr = "";
			foreach ($fp as $value) {
				$value = trim($value);
				if ($value != "") {
					if ($ix == 0) {  				//THE FIRST LINE MUST CONTAIN THE TAGS THAT DEFINES THE TYPE OF RECORD AND BIBLIOGRAPHIC LEVEL
						$ttm = explode(" ", $value);
						$tl = trim($ttm[0]);
						if (isset($ttm[1])) $nr = trim($ttm[1]);
						$ix = 1;
					} else {
						$ttm = explode('|', $value);
						$cod = $ttm[0];
						$ipos = strpos($cod, ".");
						$cod = substr($cod, 0, $ipos);
						if (isset($wks_p[$cod])) {
							// Correção da interpolação de variáveis e aspas duplas no link
							echo "<a class='bt bt-green' style='width:100%; margin: 0; display: block;' href=\"javascript:top.wks='{$value}|{$tl}|{$nr}';top.Menu('crear')\">{$ttm[3]} <i class='fas fa-arrow-right'></i></a>\n";
						}
					}
				}
			}
			?>
		</div>
	</div>
</div>

<?php include("../common/footer.php"); ?>