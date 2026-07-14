<?php
/* Modified
20210521 fho4abcd Replaced helper code fragment by included file
20210521 fho4abcd Added OPAC compare+replace componente by table (makes equal code)
20210829 fho4abcd Added missing importdoc compare+replace componente by table
20211216 fho4abcd Backbutton by included file
20221030 fho4abcd Added translations for odds, mysite. Use moduleButton for modules
20250204 fho4abcd Improve UTF-8 display
*/
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
include("../common/get_post.php");
include("../config.php");

include("../common/inc_nodb_lang.php");

include("../lang/acquisitions.php");
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/importdoc.php");
include("../lang/mysite.php");
include("../lang/prestamo.php");
include("../lang/profile.php");
include("../lang/soporte.php");
include("../common/header.php");
$backtoscript = "../common/inicio.php?reinicio=s"; // The default return script
if (isset($arrHttp["base"])) $backtoscript .= "&base=" . $arrHttp["base"];

echo "<body>";
include("../common/institutional_info.php");

?>
<div class="sectionInfo">
	<div class="breadcrumb"><?php echo $msgstr["translate"]; ?></div>
	<div class="actions">
		<?php include "../common/inc_back.php"; ?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php
include "../common/inc_div-helper.php";
?>
<div class="middle homepage">
	<div class="mainBox">
		<div class="boxContent catalogSection">
			<div class="sectionTitle">
				<img src="../../assets/svg/catalog/ic_fluent_globe_24_regular.svg">
				<h1><?php echo $msgstr["traducir"] ?> <?php echo $msgstr["lang"] . ": " . $_SESSION["lang"]; ?></h1>
			</div>
			<div class="sectionButtons">

				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=admin.tab" class="menuButton  DataEntry">
					<span><strong><?php echo $msgstr["catalogacion"] ?></strong></span>
				</a>

				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=dbadmin.tab" class="menuButton  databaseButton">
					<span><strong><?php echo $msgstr["dbadmin"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=soporte.tab" class="menuButton  utilsButton">
					<span><strong><?php echo $msgstr["maintenance"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=importdoc.tab" class="menuButton  utilsButton">
					<span><strong><?php echo $msgstr["dd_documents"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=prestamo.tab" class="menuButton  loanButton">
					<span><strong><?php echo $msgstr["prestamo"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=reports.tab" class="menuButton  reportsButton">
					<span><strong><?php echo $msgstr["reports"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=statistics.tab" class="menuButton  statButton">
					<span><strong><?php echo $msgstr["statistics"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=acquisitions.tab" class="menuButton decisionButton">
					<span><strong><?php echo $msgstr["acquisitions"] ?></strong></span>
				</a>
				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=profile.tab" class="menuButton  userButton">
					<span><strong><?php echo $msgstr["profiles"] ?></strong></span>
				</a>

				<a href="../lang/translate.php?lang=<?php echo $_SESSION["lang"] ?>&table=opac.tab" class="menuButton  moduleButton">
					<span><strong><?php echo "OPAC" ?></strong></span>
				</a>
				<!-- HOOK: Plugins can inject translation buttons here -->
				<?php echo abcd_run_hook('abcd_translation_menu', ''); ?>
			</div>
			<div class="spacer">&#160;</div>
		</div>
	</div>


	<div class="mainBox">
		<div class="boxContent catalogSection">
			<div class="sectionTitle">
				<img src="../../assets/svg/catalog/ic_fluent_branch_compare_24_regular.svg">
				<h1><?php echo $msgstr["compare_trans"] ?></h1>
			</div>
			<div class="sectionButtons">

				<a href="../lang/compare_admin.php?table=admin.tab" class="menuButton DataEntry">
					<span><strong><?php echo $msgstr["catalogacion"] ?></strong></span>
				</a>

				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=dbadmin.tab" class="menuButton databaseButton">
					<span><strong><?php echo $msgstr["dbadmin"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=soporte.tab" class="menuButton utilsButton">
					<span><strong><?php echo $msgstr["maintenance"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=importdoc.tab" class="menuButton utilsButton">
					<span><strong><?php echo $msgstr["dd_documents"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=prestamo.tab" class="menuButton loanButton">
					<span><strong><?php echo $msgstr["prestamo"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=reports.tab" class="menuButton reportsButton">
					<span><strong><?php echo $msgstr["reports"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=statistics.tab" class="menuButton statButton">
					<span><strong><?php echo $msgstr["statistics"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=acquisitions.tab" class="menuButton decisionButton">
					<span><strong><?php echo $msgstr["acquisitions"] ?></strong></span>
				</a>
				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=profile.tab" class="menuButton userButton">
					<span><strong><?php echo $msgstr["profiles"] ?></strong></span>
				</a>

				<a href="../lang/compare_admin.php?lang=<?php echo $_SESSION["lang"] ?>&table=opac.tab" class="menuButton moduleButton">
					<span><strong><?php echo "OPAC" ?></strong></span>
				</a>

				<!-- HOOK: Plugins can inject compare buttons here -->
				<?php echo abcd_run_hook('abcd_compare_translation_menu', ''); ?>
			</div>
			<div class="spacer">&#160;</div>
		</div>
	</div>


	<form name=admin method=post action=administrar_ex.php onSubmit="Javascript:return false">
		<input type=hidden name=base>
		<input type=hidden name=cipar>
		<input type=hidden name=Opcion>
	</form>
</div>
<?php include("../common/footer.php"); ?>