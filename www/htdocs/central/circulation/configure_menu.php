<?php

/**
 * @program:   ABCD - ABCD-Central - https://abcd-community.org
 * @file:      configure_menu.php
 * @desc:      Configuration menu
 * @author:    Guilda Ascencio
 * @since:     20091203
 * @version:   2.2
 *
 * changelog:
 * 2026-06-07 rogercgui Refactored circulation configure menu to use Font Awesome icons instead of image tags for better consistency and maintainability.
 */
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"] = "en";
$lang = $_SESSION["lang"];
include("../common/get_post.php");
include("../config.php");
include("../lang/admin.php");
include("../lang/prestamo.php");


//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");
$encabezado = "";
$encabezado = "&encabezado=s";
?>

<?php include("../common/institutional_info.php"); ?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["configure"]; ?>
	</div>
	<div class="actions">
	</div>
	<?php include("submenu_prestamo.php"); ?>
</div>


<?php
$ayuda = "configure_menu.html";
include "../common/inc_div-helper.php";
?>


<div class="middle homepage">


	<div class="mainBox">
		<div class="boxContent loanSection">
			<div class="sectionTitleIcons">
				<i class="fas fa-cogs"></i>
				<h1><?php echo $msgstr["policy"] ?></h1>
			</div>
			<div class="sectionButtons">

				<a href="adm_databases.php" class="menuButton-fa">
					<i class="fas fa-database"></i>
					<span><strong><?php echo $msgstr["sourcedb"] ?></strong></span>
				</a>
				<a href="borrowers_configure.php" class="menuButton-fa">
					<i class="fas fa-users-cog"></i>
					<span><strong><?php echo $msgstr["bconf"] ?></strong></span>
				</a>
				<a href="adm_typeofusers.php" class="menuButton-fa">
					<i class="fas fa-user-tag"></i>
					<span><strong><?php echo $msgstr["typeofusers"] ?></strong></span>
				</a>
				<a href="adm_typeofitems.php" class="menuButton-fa">
					<i class="fas fa-tags"></i>
					<span><strong><?php echo $msgstr["typeofitems"] ?></strong></span>
				</a>
				<a href="adm_loanobjects.php" class="menuButton-fa">
					<i class="fas fa-sliders-h"></i>
					<span><strong><?php echo $msgstr["objectpolicy"] ?></strong></span>
				</a>

				<a href="adm_form_locales.php" class="menuButton-fa">
					<i class="fas fa-coins"></i>
					<span><strong><?php echo $msgstr["local"] ?></strong></span>
				</a>

				<a href="adm_calendario.php" class="menuButton-fa">
					<i class="fas fa-calendar-alt"></i>
					<span><strong><?php echo $msgstr["calendar"] ?></strong></span>
				</a>

				<a href="sala_configure.php" class="menuButton-fa">
					<i class="fas fa-book-reader"></i>
					<span><strong><?php echo $msgstr["sala"] ?></strong></span>
				</a>
				<?php if (isset($ILL)) {
				?>
					<a href="../circulation/interbib_configure.php?encabezado=s" class="menuButton-fa">
						<i class="fas fa-handshake"></i>
						<span><strong><?php echo $msgstr["r_ill"] ?></strong></span>
					</a>
				<?php
				}
				?>

			</div>
			<div class="spacer">&#160;</div>
		</div>

	</div>


	<div class="mainBox">
		<div class="boxContent loanSection">
			<div class="sectionTitleIcons">
				<i class="fas fa-print"></i>
				<h1><?php echo $msgstr["outputs"] ?></h1>
			</div>
			<div class="sectionButtons">

				<a href="../circulation/receipts.php?base=trans&encabezado=s&retorno=../circulation/configure_menu.php" class="menuButton-fa">
					<i class="fas fa-receipt"></i>
					<span><strong><?php echo $msgstr["receipts"] ?></strong></span>
				</a>
				<a href="../dbadmin/pft.php?base=trans&encabezado=s&retorno=../circulation/configure_menu.php" class="menuButton-fa">
					<i class="fas fa-exchange-alt"></i>
					<span><strong><?php echo $msgstr["reports_trans"] ?></strong></span>
				</a>
				<a href="../dbadmin/pft.php?base=suspml&encabezado=s&retorno=../circulation/configure_menu.php" class="menuButton-fa">
					<i class="fas fa-user-slash"></i>
					<span><strong><?php echo $msgstr["reports_suspml"] ?></strong></span>
				</a>
				<a href="../dbadmin/pft.php?base=users&encabezado=s&retorno=../circulation/configure_menu.php" class="menuButton-fa">
					<i class="fas fa-users"></i>
					<span><strong><?php echo $msgstr["reports_borrowers"] ?></strong></span>
				</a>


			</div>
			<div class="spacer">&#160;</div>
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
</div>
<?php include("../common/footer.php"); ?>
</body>

</html>