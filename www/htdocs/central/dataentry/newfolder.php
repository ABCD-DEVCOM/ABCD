<?php
session_start();

set_time_limit(0);
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value)  echo "$var=$value<br>";
//die;
include("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");

switch ($arrHttp["desde"]) {
	case "dbcp":
		$folder = $db_path;
		break;
	default:
		if (file_exists($db_path . $arrHttp["base"] . "/dr_path.def")) {
			$def = parse_ini_file($db_path . $arrHttp["base"] . "/dr_path.def");
			$folder = trim($def["ROOT"]);
		} else {
			$folder = getenv("DOCUMENT_ROOT") . "/bases/" . $arrHttp["base"];
		}
}

// CORREÇÃO CRÍTICA: Traduz o macro %path_database% para o caminho real do sistema
$folder = str_replace("%path_database%", $db_path, $folder);

$root = $folder;
$activeFolder = "";

if (isset($arrHttp["path"])) {
	if ($arrHttp["path"] != "..")
		$folder = $folder . "/" . $arrHttp["path"];
}
if (isset($arrHttp["source"])) $folder .= "/" . $arrHttp["source"];

$folder .= "/" . $arrHttp["folder"];

// Sanitização absoluta de barras para evitar conflitos de caminhos no Windows e Linux
$folder = str_replace(array('\\', '//'), '/', $folder);

// Cria a estrutura de pastas respeitando o caminho absoluto traduzido
$res = @mkdir($folder, 0777, true);

echo "<script>
history.back()
</script>
";

?>