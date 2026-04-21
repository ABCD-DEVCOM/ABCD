<?php
/* Modifications
20211216 fho4abcd Backbutton & helper by included file. improve html
20220112 fho4abcd fmt.php->fmt_adm.php
20220202 fho4abcd improved text strings, more translations
20220209 fho4abcd Preserve base
20220214 fho4abcd Marc menu items only for MARC
20220317 fho4abcd Layout, remove superfluous permission check, add barcode configuration
20220321 fho4abcd renamed barcode scripts
20220926 fho4abcd add statistic configuration. top/down buttons
20250902 rogercgui Fix links from statistics links
20251216 Gemini Refactoring for balanced card layout & removal of IAH
*/

session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
// ARCHIVOS DE LENGUAJE
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/statistics.php");

// EXTRACCIÓN DEL NOMBRE DE LA BASE DE DATOS
if (isset($arrHttp["base"])) {
	$selbase = $arrHttp["base"];
} else {
	$selbase = "";
}

if (strpos($selbase, "|") === false) {
} else {
	$ix = explode('|', $selbase);
	$selbase = $ix[0];
}
$base = $selbase;
$arrHttp["base"] = $base;

// VERIFICACION DE LA PERMISOLOTIA
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_MODIFYDEF"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_MODIFYDEF"]) or isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])) {
} else {
	echo "<h2>" . $msgstr["invalidright"] . " " . $base;
	die;
}

include("../common/header.php");
?>

<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script language='javascript'>
	function Update(Option) {
		if (document.update_base.base.value == "") {
			alert("<?php echo $msgstr["seldb"] ?>")
			return
		}
		switch (Option) {
			case "fdt":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "fdt.php"
				document.update_base.type.value = "bd"
				break;
			case "leader":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "fdt.php"
				document.update_base.type.value = "leader.fdt"
				break;
			case "fdt_new":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "fdt_short_a.php"
				document.update_base.type.value = "bd"
				break;
			case "fst":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "fst.php"
				break;
			case "fmt_adm":
				document.update_base.action = "fmt_adm.php"
				break;
			case "pft":
				document.update_base.action = "pft.php"
				break;
			case "typeofrecs":
				document.getElementById('preloader').style.display = 'block';
				<?php
				$archivo = $db_path . $selbase . "/def/" . $_SESSION["lang"] . "/typeofrecs.tab";
				if (!file_exists($archivo))  $archivo = $db_path . $selbase . "/def/" . $lang_db . "/typeofrecs.tab";
				if (file_exists($archivo))
					$script = "typeofrecs_edit.php";
				else
					$script = "typeofrecs_edit.php";
				echo "\ndocument.update_base.action=\"$script\"\n";
				?>
				break;
			case "fixedfield":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "typeofrecs_marc_edit.php"
				break;
			case "fixedmarc":
				document.getElementById('preloader').style.display = 'block';
				document.update_base.action = "fixed_marc.php"
				break;
			case "recval":
				document.update_base.action = "typeofrecs.php"
				break;
			case "delval":
				document.update_base.action = "recdel_val.php"
				document.update_base.format.value = "recdel_val"
				break;
			case "bases":
				document.update_base.action = "../settings/databases_list.php"
				break;
			case "par":
				document.update_base.action = "editpar.php"
				break;
			case "dr_path":
				document.update_base.Opcion.value = "dr_path"
				document.update_base.action = "../settings/editar_abcd_def.php"
				break;
			case "search_catalog":
				document.update_base.action = "advancedsearch.php"
				document.update_base.modulo.value = "catalogacion"
				break;
			case "search_circula":
				document.update_base.action = "advancedsearch.php"
				document.update_base.modulo.value = "prestamo"
				break;
			case "tooltips":
				document.update_base.action = "database_tooltips.php"
				break;
			case "help":
				document.update_base.action = "../documentacion/help_ed.php"
				break;
			case "tes_config":
				document.update_base.action = "tes_config.php"
				break;
			case "chk_dbdef":
				document.update_base.action = "chk_dbdef.php"
				break;
			case "labeltab":
				document.update_base.action = "../barcode/bcl_config_label_table.php"
				break;
			case "labelconfig":
				document.update_base.action = "../barcode/bcl_config_labels.php"
				break;
			case "stats_tab":
				document.update_base.action = "../statistics/tables_cfg.php"
				break;
			case "stats_var":
				document.update_base.action = "../statistics/config_vars.php"
				break;
			case "stats_pft":
				document.update_base.action = "../statistics/config_tables.php"
				break;
			case "stats_tab":
				document.update_base.action = "../statistics/tables_cfg.php"
				break;
			case "stats_proc":
				document.update_base.action = "../statistics/proc_cfg.php"
				break;
		}
		document.update_base.submit()
	}
</script>

<?php include("../common/inc_wait.php") ?>

<?php
// ENCABEZAMIENTO DE LA PÁGINA
if (isset($arrHttp["encabezado"])) {
	include("../common/institutional_info.php");
	$encabezado = "&encabezado=s";
}
?>

<div class="sectionInfo">
	<div class="breadcrumb"><?php echo $msgstr["updbdef"] . ": " . $selbase ?>
	</div>
	<div class="actions">
		<?php include "../common/inc_home.php"; ?>
	</div>
	<div class="spacer">&#160;</div>
</div>

<?php
include "../common/inc_div-helper.php";

$dir_fdt = $db_path . $selbase . "/def/" . $lang . "/";
$ldr = "";

// Verificação MARC/LDR
if (is_dir($dir_fdt)) {
	if (file_exists($dir_fdt . $selbase . ".fdt")) {
		$fp = file($dir_fdt . $selbase . ".fdt");
	} else {
		$fp = file($db_path . $selbase . "/def/" . $lang_db . "/" . $selbase . ".fdt");
	}

	if ($fp) {
		foreach ($fp as $value) {
			$value = trim($value);
			if (trim($value) != "") {
				$fdt = explode('|', $value);
				if (isset($fdt[0]) && $fdt[0] == "LDR") {
					$ldr = "s";
					break;
				}
			}
		}
	}
}
?>

<div class="middle form">
	<div class="formContent">
		<form name=update_base onSubmit="return false" method=post>
			<input type=hidden name=Opcion value=update>
			<input type=hidden name=type value="">
			<input type=hidden name=modulo>
			<input type=hidden name=format>
			<input type=hidden name=base value=<?php echo $selbase; ?>>
			<?php if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=s>"; ?>

			<div class="admin-grid">

				<div class="admin-card">
					<div class="admin-card-header">
						<i class="fas fa-database"></i> <?php echo $msgstr["db_structure"]; ?>
					</div>
					<div class="admin-card-body">
						<div class="card-section-title"><?php echo $msgstr["dbadmin_FDT_FMT"] ?></div>
						<a class="admin-link" href='javascript:Update("fdt")'><i class="fas fa-pen-to-square"></i> <?php echo $msgstr["fdt"] ?></a>
						<a class="admin-link" href='javascript:Update("fdt_new")'><i class="fas fa-pen-to-square"></i> <?php echo $msgstr["fdt"] . " (" . $msgstr["wosubfields"] . ")" ?></a>
						<?php if ($ldr == "s") { ?>
							<a class="admin-link" href='javascript:Update("leader")'><i class="fas fa-asterisk"></i> <?php echo $msgstr["ft_ldr"] ?></a>
							<a class="admin-link" href='javascript:Update("fixedmarc")'><i class="fas fa-asterisk"></i> <?php echo "MARC-" . $msgstr["typeofrecord_ff"] ?></a>
							<a class="admin-link" href='javascript:Update("fixedfield")'><i class="fas fa-asterisk"></i> <?php echo "MARC-" . $msgstr["typeofrecord_aw"] ?></a>
						<?php } ?>
						<a class="admin-link" href='javascript:Update("fmt_adm")'><i class="fas fa-table"></i> <?php echo $msgstr["fmt"] ?></a>
						<?php if (!isset($ldr) or $ldr != "s") { ?>
							<a class="admin-link" href='javascript:Update("typeofrecs")'><i class="fas fa-list"></i> <?php echo $msgstr["typeofrecord_aw"]; ?></a>
						<?php } ?>

						<div class="card-section-title"><?php echo $msgstr["dbadmin_INDEX"] . " / " . $msgstr["dbadmin_FORMAT"] ?></div>
						<a class="admin-link" href='javascript:Update("fst")'><i class="fas fa-list-ol"></i> <?php echo $msgstr["fst"] ?></a>
						<a class="admin-link" href='javascript:Update("pft")'><i class="fas fa-print"></i> <?php echo $msgstr["pft"] ?></a>

						<div class="card-section-title"><?php echo $msgstr["dbadmin_VALID"] ?></div>
						<a class="admin-link" href='javascript:Update("recval")'><i class="fas fa-check-double"></i> <?php echo $msgstr["recval"] ?></a>
						<a class="admin-link" href='javascript:Update("delval")'><i class="fas fa-user-check"></i> <?php echo $msgstr["delval"] ?></a>
					</div>
				</div>

				<div class="admin-card">
					<div class="admin-card-header">
						<i class="fas fa-cogs"></i> <?php echo $msgstr["db_configuration"]; ?>
					</div>
					<div class="admin-card-body">
						<div class="card-section-title"><?php echo $msgstr["dbadmin_ADVANCED"] ?></div>
						<a class="admin-link" href='javascript:Update("par")'><i class="fas fa-file-code"></i> <?php echo $msgstr["dbnpar"] ?></a>
						<a class="admin-link" href='javascript:Update("dr_path")'><i class="fas fa-folder-open"></i> <?php echo $msgstr["dr_path.def"] ?></a>
						<a class="admin-link" href='javascript:Update("tes_config")'><i class="fas fa-book"></i> <?php echo $msgstr["tes_config"] ?></a>
						<a class="admin-link" href='javascript:Update("chk_dbdef")'><i class="fas fa-stethoscope"></i> <?php echo $msgstr["chk_dbdef"] ?></a>

						<div class="card-section-title"><?php echo $msgstr["dbadmin_BARCOLABEL"] ?></div>
						<a class="admin-link" href='javascript:Update("labeltab")'><i class="fas fa-barcode"></i> <?php echo $msgstr["barcode_table"] ?></a>
						<a class="admin-link" href='javascript:Update("labelconfig")'><i class="fas fa-tags"></i> <?php echo $msgstr["barcode_config"] ?></a>
					</div>
				</div>

				<div class="admin-card">
					<div class="admin-card-header">
						<i class="fas fa-chart-pie"></i> <?php echo $msgstr["db_search_stats"]; ?>
					</div>
					<div class="admin-card-body">
						<div class="card-section-title"><?php echo $msgstr["dbadmin_INTERNALSEARCH"] ?></div>
						<a class="admin-link" href='javascript:Update("search_catalog")'><i class="fas fa-search"></i> <?php echo $msgstr["advsearch"] . ": " . $msgstr["catalogacion"] ?></a>
						<a class="admin-link" href='javascript:Update("search_circula")'><i class="fas fa-search"></i> <?php echo $msgstr["advsearch"] . ": " . $msgstr["prestamo"] ?></a>

						<div class="card-section-title"><?php echo $msgstr["dbadmin_EDIT_HELPS"] ?></div>
						<a class="admin-link" href='javascript:Update("help")'><i class="fas fa-circle-question"></i> <?php echo $msgstr["helpdatabasefields"] ?></a>
						<a class="admin-link" href='javascript:Update("tooltips")'><i class="fas fa-comment-dots"></i> <?php echo $msgstr["database_tooltips"] ?></a>

						<div class="card-section-title"><?php echo $msgstr["stats_conf"] ?></div>
						<a class="admin-link" href='javascript:Update("stats_var")'><i class="fas fa-chart-bar"></i> <?php echo $msgstr["stat_cfg_vars"] ?></a>
						<a class="admin-link" href='javascript:Update("stats_pft")'><i class="fas fa-table"></i> <?php echo $msgstr["def_pre_tabs"] ?></a>
						<a class="admin-link" href='javascript:Update("stats_tab")'><i class="fas fa-columns"></i> <?php echo $msgstr["stat_cfg_tabs"] ?></a>
						<a class="admin-link" href='javascript:Update("stats_proc")'><i class="fas fa-microchip"></i> <?php echo $msgstr["stat_cfg_procs"] ?></a>
					</div>
				</div>

			</div>
		</form>
	</div>
</div>
<?php include("../common/footer.php"); ?>