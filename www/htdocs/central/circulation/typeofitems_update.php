<?php
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
	exit;
}
include("../common/get_post.php");
include("../config.php");

// Recolhe os dados e processa a gravação
$arrHttp["ValorCapturado"] = stripslashes($arrHttp["ValorCapturado"] ?? "");
$t = explode("\n", $arrHttp["ValorCapturado"]);
$fp = fopen($db_path . "circulation/def/" . $_SESSION["lang"] . "/items.tab", "w");

if ($fp) {
	foreach ($t as $value) {
		fwrite($fp, stripslashes($value) . "\n");
	}
	fclose($fp);
}

// Redirects the user back to the list page with a success message
header("Location: adm_typeofitems.php?msg=success");
exit;
