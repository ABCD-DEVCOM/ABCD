<?php
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
	exit;
}
include("../common/get_post.php");
include("../config.php");

// Recupera os dados e processa a gravação
$arrHttp["ValorCapturado"] = stripslashes($arrHttp["ValorCapturado"] ?? "");
$t = explode("\n", $arrHttp["ValorCapturado"]);
$fp = fopen($db_path . "circulation/def/" . $_SESSION["lang"] . "/typeofusers.tab", "w");

if ($fp) {
	foreach ($t as $value) {
		fwrite($fp, stripslashes($value) . "\n");
	}
	fclose($fp);
}

// O fluxo mágico: Redireciona o usuário de volta para a listagem com a flag de sucesso
header("Location: adm_typeofusers.php?msg=success");
exit;
