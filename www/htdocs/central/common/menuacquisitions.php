<?php
/* Modifications
20210310 fho4abcd removed language include.(set by inicio.php)
*/
$_SESSION["MODULO"]="acquisitions";
global $arrHttp,$msgstr,$db_path,$valortag,$lista_bases;
?>
			
			<div class="mainBox" >
				<div class="boxContent acqSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_database_24_regular.svg">
						<h1><?php echo $msgstr["suggestions"]?></h1>
					</div>
					<div class="sectionButtons">

						<a href="../acquisitions/overview.php?encabezado=s&Expresion=STA_0" class="menuButton multiLine resumeButton">
							<span><strong><?php echo $msgstr["overview"]?></strong></span>
						</a>
<?php
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_NEWSUGGESTIONS"])){
?>
						<a href="../acquisitions/suggestions_new.php?encabezado=s&base=suggestions&cipar=suggestions.par" class="menuButton multiLine newsuggestionButton">
							<span><strong><?php echo $msgstr["newsugges"]?></strong></span>
						</a>
<?php }
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_APPROVREJECT"])){
?>
						<a href="../acquisitions/suggestions_status.php?base=suggestions&cipar=suggestions.par&sort=TI&encabezado=s" class="menuButton multiLine approvedsuggestionsButton">
							<span><strong><?php echo $msgstr["approve"]."/".$msgstr["reject"]?></strong></span>
						</a>
<?php }
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_BIDDING"])){
?>
						<a href="../acquisitions/bidding.php?base=suggestions&sort=DA&encabezado=s&menu=s" class="menuButton multiLine biddingButton">
							<span><strong><?php echo $msgstr["bidding"]?></strong></span>
						</a>
<?php }
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_DECISION"])){
?>
						<a href="../acquisitions/decision.php?base=suggestions&sort=DA&encabezado=s&menu=s" class="menuButton multiLine decisionButton">
							<span><strong><?php echo $msgstr["decision"]?></strong></span>
						</a>
<?php }?>
					</div>
					<div class="spacer">&#160;</div>
				</div>

			</div>

			<div class="mainBox" >
				<div class="boxContent acqSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_database_24_regular.svg">
						<h1><?php echo $msgstr["purchase"]?></h1>
					</div>
					<div class="sectionButtons">
<?php
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_CREATEORDER"])){
?>
						<a href="../acquisitions/order_new_menu.php?base=suggestions&sort=PV&encabezado=s" class="menuButton multiLine createpurchaseorderButton">
							<span><strong><?php echo $msgstr["createorder"]?></strong></span>
						</a>

						<a href="../acquisitions/order.php?base=suggestions&sort=PV&encabezado=s" class="menuButton multiLine requisitionButton">
							<span><strong><?php echo $msgstr["generateorder"]?></strong></span>
						</a>
<?php }?>
						<a href="../acquisitions/pending_order.php?base=purchaseorder&sort=PV&encabezado=s" class="menuButton multiLine pendingButton">
							<span><strong><?php echo $msgstr["pendingorder"]?></strong></span>
						</a>
<?php if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_RECEIVING"])){
?>
						<a href="../acquisitions/receive_order.php?encabezado=s" class="menuButton multiLine ACQreceivingButton">
							<span><strong><?php echo $msgstr["receiving"]?></strong></span>
						</a>
<?php }?>
					</div>
					<div class="spacer">&#160;</div>
				</div>

			</div>
<?php
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_ACQDATABASES"])){
?>

			<div class="mainBox" >
				<div class="boxContent acqSection">
					<div class="sectionTitle">
					<img src="../../assets/svg/circ/ic_fluent_database_24_regular.svg">
						<h1><?php echo $msgstr["basedatos"]?></h1>
					</div>
					<div class="sectionButtons">

						<a href="../acquisitions/browse.php?base=suggestions&modulo=acquisitions" class="menuButton multiLine suggestButton">
							<span><strong><?php echo $msgstr["suggestions"]?></strong></span>
						</a>
						<a href="../dataentry/browse.php?base=providers&modulo=acquisitions" class="menuButton multiLine providersButton">
							<span><strong><?php echo $msgstr["providers"]?></strong></span>
						</a>
						<a href="../dataentry/browse.php?base=purchaseorder&modulo=acquisitions" class="menuButton multiLine requisitionButton">
							<span><strong><?php echo $msgstr["purchase"]?></strong></span>
						</a>
						<a href="../dataentry/browse.php?base=copies&modulo=acquisitions" class="menuButton multiLine copiesdbButton">
							<span><strong><?php echo $msgstr["copies"]?></strong></span>
						</a>

					</div>
					<div class="spacer">&#160;</div>
				</div>

			</div>
<?php  }
if (isset($_SESSION["permiso"]["ACQ_ACQALL"]) or isset($_SESSION["permiso"]["ACQ_RESETCN"])){
?>

	<div class="mainBox" >
		<div class="boxContent acqSection">
			<div class="sectionTitle">
			<img src="../../assets/svg/circ/ic_fluent_database_24_regular.svg">
				<h1><?php echo $msgstr["admin"]?></h1>
			</div>
			<div class="sectionButtons">
				<a href="../acquisitions/resetautoinc.php?base=suggestions&encabezado=s" class="menuButton multiLine ACQresetButton">
					<span><strong><?php echo $msgstr["resetctl"]. " (".$msgstr["suggestions"].")"?></strong></span>
				</a>
			</div>
			<div class="spacer">&#160;</div>

		</div>

	</div>
<?php }?>
<?php
// HOOK (UI): plugins can add their own boxes/buttons to the acquisitions home
if (function_exists('abcd_run_hook')) echo abcd_run_hook('acquisitions_menu', '');
?>
