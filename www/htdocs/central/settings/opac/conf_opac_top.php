<?php

session_start();
$is_opac_admin = true;
include("../../config_opac.php");
$charset = $opac_charset_config;

if (isset($_REQUEST["lang"])) {
	$_SESSION["lang"] = $_REQUEST["lang"];
}

if (!isset($_SESSION["lang"])) {
	$_SESSION["lang"] = "en";
}

$lang = $_SESSION["lang"];

header('Content-Type: text/html; charset=<?php echo $charset?>');

include("../../common/header.php");
include("opac_functions.php");

if (!isset($_SESSION["permiso"])) {
	header("Location: ../../common/error_page.php");
}

if (!isset($_REQUEST["db_path"])) $_REQUEST["db_path"] = $db_path;
if (isset($_REQUEST["db_path"]) and $_REQUEST["db_path"] != "")
	$_SESSION["db_path"] = $_REQUEST["db_path"];
if (!isset($_SESSION["db_path"])) {
	$_SESSION["db_path"] = $_REQUEST["db_path"];
}
$db_path = $_SESSION["db_path"];

?>
<link rel="stylesheet" type="text/css" href="assets/css/styles.css?v=<?php echo time(); ?>">
<script src="assets/js/opac_config.js?v=<?php echo time(); ?>"></script>

</head>

<body <?php if (isset($onload)) echo $onload ?>>

	<?php
	include("../../common/institutional_info.php");
	?>
	<div class="sectionInfo">
		<div class="breadcrumb">
			OPAC Config
		</div>
		<div class="actions">
			<?php
			$backtoscript = "../conf_abcd.php";
			include "../../common/inc_back.php";
			?>

		</div>
		<div class="spacer">&#160;</div>
	</div>

	<?php if (isset($_REQUEST["conf_level"]) and $_REQUEST["conf_level"] == "advanced")
		echo "<input type=hidden name=conf_level value=" . $_REQUEST["conf_level"] . ">\n";
	if (isset($_REQUEST["base"]) and $_REQUEST["base"] != "")
		echo "<input type=hidden name=base value=" . $_REQUEST["base"] . ">\n";
	?>