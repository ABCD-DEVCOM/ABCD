<?php
include_once("../config.php");
/*echo "$db_path";
if (file_exists($db_path."logtrans/data/logtrans.mst") and $_SESSION["MODULO"]!="loan"){
	include("../circulation/grabar_log.php");
	$datos_trans["operador"]=$_SESSION["login"];
	GrabarLog("P",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path);

}
*/
$_SESSION["MODULO"]="loan";
global $arrHttp,$msgstr,$db_path,$valortag,$lista_bases;
?>
			<div class="mainBox" >
				<div class="boxContent loanSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_arrow_swap_24_regular.svg">
						<h1><?php echo $msgstr["trans"]?></h1>
					</div>
					<div class="sectionButtons">
<?php

if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_LOAN"])){
?>
						<a href="../circulation/start-loans.php?encabezado=s" class="menuButton loanButton">
							<span><strong><?php echo $msgstr["loan"]?></strong></span>
						</a>
<?php
	if (isset($ILL) and $ILL!="") {
?>
						<a href="../circulation/interbib.php?encabezado=s" class="menuButton providersButton">
							<span><strong><?php echo $msgstr["r_ill"]?></strong></span>
						</a>
<?php
	}
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_LOAN"])){
	if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){
?>
						<a href="../circulation/estado_de_cuenta.php?encabezado=s&reserve=S" class="menuButton reserveButton">
							<span><strong><?php echo $msgstr["reserve"]?></strong></span>
						</a>
<?php
	}
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_RETURN"])){
?>
						<a href="../circulation/devolver.php?encabezado=s" class="menuButton returnButton">
							<span><strong><?php echo $msgstr["return"]?></strong></span>
						</a>
<?php
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_RENEW"])){
?>
						<a href="../circulation/renovar.php?encabezado=s" class="menuButton renewButton">
							<span><strong><?php echo $msgstr["renew"]?></strong></span>
						</a>
<?php
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_SALA"])){
?>
						<a href="../circulation/sala.php?encabezado=s" class="menuButton exploreButton">
							<span><strong><?php echo $msgstr["sala"]?></strong></span>
						</a>
<?php }
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_SUSPEND"])){
?>
						<a href="../circulation/sanctions.php?encabezado=s" class="menuButton sanctionsButton">
							<span><strong><?php echo $msgstr["suspend"]."/".$msgstr["fine"]?></strong></span>
						</a>
<?php }?>
						<a href="../circulation/situacion_de_un_objeto.php?encabezado=s" class="menuButton statusitemButton">
							<span><strong><?php echo $msgstr["ecobj"]?></strong></span>
						</a>
						<a href="../circulation/estado_de_cuenta.php?encabezado=s" class="menuButton userstatmentButton">
							<span><strong><?php echo $msgstr["statment"]?></strong></span>
						</a>
                        <a href="../circulation/borrower_history.php?encabezado=s" class="menuButton userhistoryButton">
							<span><strong><?php echo $msgstr["bo_history"]?></strong></span>
						</a>
						<a href="../circulation/item_history.php?encabezado=s" class="menuButton receivingButton">
							<span><strong><?php echo $msgstr["item_history"]?></strong></span>
						</a>
						<a href="../output_circulation/menu.php" class="menuButton reportsButton">
							<span><strong><?php echo $msgstr["reports"]?></strong></span>
						</a>
					</div>
					<div class="spacer"></div>
				</div>
			</div>
<?php
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCDATABASES"])){
?>

			<div class="mainBox" >
				<div class="boxContent loanSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_database_24_regular.svg">
						<h1><?php echo $msgstr["basedatos"]?></h1>
					</div>
					<div class="sectionButtons">

						<a href="../dataentry/browse.php?base=users&modulo=loan" class="menuButton userButton">
							<span><strong><?php echo $msgstr["users"]?></strong></span>
						</a>
						<a href="../dataentry/browse.php?base=trans&modulo=loan" class="menuButton transactionsButton">
							
							<span><strong><?php echo $msgstr["trans"]?></strong></span>
						</a>
						<a href="../dataentry/browse.php?base=suspml&modulo=loan" class="menuButton sanctionsButton">
							
							<span><strong><?php echo $msgstr["suspen"]."/".$msgstr["multas"]?></strong></span>
						</a>
<?php
	if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){
?>
						<a href="../dataentry/browse.php?base=reserve&modulo=loan" class="menuButton reserveButton">
							
							<span><strong><?php echo $msgstr["reservas"]?></strong></span>
						</a>
<?php } ?>
					</div>
					<div class="spacer"></div>
				</div>
			</div>
<?php
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCFG"])
	or isset($_SESSION["permiso"]["CIRC_CIRCREPORTS"]) or isset($_SESSION["permiso"]["CIRC_CIRCSTAT"])){
?>

			<div class="mainBox" >
				<div class="boxContent loanSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_settings_24_regular.svg">
						<h1><?php echo $msgstr["admin"]?></strong></h1>
					</div>
					<div class="sectionButtons">
<?php
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_CIRADMIN"])){
?>                      <!--a href="javascript:VerificarInicializacion()" class="menuButton databaseButton" on>
						<a href="../circulation/menu_mantenimiento.php" class="menuButton databaseButton" on>
							
							<span><strong><?php echo $msgstr["basedatos"]?></strong></span>
						</a-->
<?php
}

if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCFG"])){
?>
						<a href="../circulation/configure_menu.php?encabezado=s" class="menuButton toolsButton">
							<span><strong><?php echo $msgstr["configure"]?></strong></span>
						</a>
<?php
}
if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCSTAT"])){
?>
			<a href="../statistics/tables_generate.php?base=users&encabezado=s" class="menuButton statisticsusersButton">
				<span><strong><?php echo $msgstr["stat_users"]?></strong></span>
			</a>
			<a href="../statistics/tables_generate.php?base=trans&encabezado=s" class="menuButton statButton">
				<span><strong><?php echo $msgstr["stat_trans"]?></strong></span>
			</a>
			<a href="../statistics/tables_generate.php?base=suspml&encabezado=s" class="menuButton statisticsanctionsButton">
				<span><strong><?php echo $msgstr["stat_suspml"]?></strong></span>
			</a>
<?php

}
?>
				</div>
					<div class="spacer"></div>
				</div>

			</div>
<?php }?>
<?php
// HOOK (UI): plugins can add their own boxes/buttons to the circulation home
if (function_exists('abcd_run_hook')) echo abcd_run_hook('circulation_menu', '');
?>
<script>
function VerificarInicializacion(){
	if (confirm("Quiere inicializar las transacciones de préstamo")){
		self.location.href="../circulation/initialize_trans.php?encabezado=s"
	}
}
</script>