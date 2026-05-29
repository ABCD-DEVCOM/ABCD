<?php
/* Modifications
2021-03-03 fho4abcd Replaced helper code fragment by included file
2021-03-03 fho4abcd Conformance: Moved <body>,improved other html (more to do)
2021-03-06 fho4abcd Pass selected records (conforms with iso export)
2021-03-11 fho4abcd lineends, sanitize html
2021-03-22 fho4abcd remove unused functions in script
2021-04-18 fho4abcd Iso import from carga_iso.pho to vmx_import_iso.php
2021-11-04 fho4abcd Replace vmx_fullinv.php by fullinv.php.
20211216 fho4abcd Backbutton by included file, removed redundant help
20220107 fho4abcd Removed opcion parameter for text import/export. Smaller textblocks
20220124 fho4abcd No back button if institutional info not shown
20220227 fho4abcd Always show backbutton. Other back if institutional info not shown
20251013 fho4abcd Replace $db by $base to ensure correct permissions
20260529 rogercgui Refactored layout.
*/
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");


//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");
?>

<body>


	<script language=javascript>
		function Activar(Opcion) {
			if (top.base == "") {
				alert("<?php echo $msgstr["seldb"] ?>")
				return
			} else {
				seleccionados = ""
				<?php
				if (isset($arrHttp["seleccionados"])) {
					echo 'seleccionados=\'&seleccionados=' . $arrHttp["seleccionados"] . "'\n";
				}
				?>
				switch (Opcion) {
					case "imptxt":
						self.location = "carga_txt_cnv.php?base=" + top.base + "&accion=import&tipo=txt&lang=<?php echo $_SESSION["lang"] ?>"
						break
					case "exptxt":
						self.location = "carga_txt_cnv.php?base=" + top.base + "&accion=export&tipo=txt&lang=<?php echo $_SESSION["lang"] ?>" + seleccionados
						break
					case "expiso":
						self.location = "exporta_txt.php?base=" + top.base + "&cipar=" + top.base + ".par&tipo=iso&lang=<?php echo $_SESSION["lang"] ?>" + seleccionados
						break
					case "impiso":
						self.location = "../utilities/vmx_import_iso.php?base=" + top.base
						break
				}
			}
		}

		function EnviarForma(Opcion, Mensaje) {
			if (top.base == "") {
				alert("<?php echo $msgstr["seldb"] ?>")
				return
			} else {
				//	if (confirm(Mensaje)==true){
				switch (Opcion) {
					case "fullinv":
						msgwin = window.open("", "fullinv", "menu=no,status,resizable,scrollbars")
						msgwin.document.writeln("<html><title><?php echo $msgstr["mnt_gli"] ?></title><body><font color=red face=verdana><?php echo $msgstr["mnt_lig"] . ". " . $msgstr["mnt_espere"] ?> ...")
						msgwin.document.writeln("</body></html>")
						msgwin.focus()
						document.admin.target = "fullinv"
						break
					case "globalc":
						document.admin.action = "c_global.php"
						document.admin.target = ""
						break;
					case "listar":
					case "unlock":
					case "lisdelrec":
						document.admin.action = "mfn_ask_range.php";
						document.admin.target = ""
						break;
					default:
						document.admin.target = ""
						break;
					case "fullinvMX":
						document.admin.action = "../utilities/fullinv.php"
						document.admin.target = ""
				}
				document.admin.Opcion.value = Opcion
				document.admin.base.value = top.base
				document.admin.cipar.value = top.base + ".par"
				document.admin.submit()
			}


			//	}
		}
	</script>
	<?php
	if (isset($arrHttp["encabezado"]) and $arrHttp["encabezado"] == "s") {
		include("../common/institutional_info.php");
	} else {
		$arrHttp["encabezado"] = "";
	}
	?>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["mantenimiento"] ?>
		</div>
		<div class="actions">
			<?php
			if ($arrHttp["encabezado"] == "s") {
				include "../common/inc_back.php";
			} else {
				$backtoscript = "../dataentry/inicio_main.php";
				include "../common/inc_back.php";
			}
			?>
		</div>
		<div class="spacer">&#160;</div>
	</div>
	<?php include "../common/inc_div-helper.php" ?>
	<div class="  homepage">

		<style>
			/* Pequeno bloco de apoio seguindo a semântica e padrão estético do ABCD */
			.admin-grid {
				display: flex;
				flex-wrap: wrap;
				gap: 20px;
				align-items: flex-start;
			}

			.admin-column {
				flex: 1;
				min-width: 300px;
			}

			.admin-column h3 {
				border-bottom: 2px solid var(--abcd-blue);
				padding-bottom: 8px;
				font-weight: 600;
			}
		</style>

		<div class="admin-grid">
			<div class="admin-column col-6">
				<?php if (
					isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
					isset($_SESSION["permiso"]["CENTRAL_UNLOCKDB"]) or
					isset($_SESSION["permiso"]["CENTRAL_LISTBKREC"]) or
					isset($_SESSION["permiso"]["CENTRAL_UNLOCKDBREC"]) or
					isset($_SESSION["permiso"]["CENTRAL_FULLINV"]) or
					isset($_SESSION["permiso"]["CENTRAL_COPYDB"]) or
					isset($_SESSION["permiso"]["CENTRAL_GLOBC"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_UNLOCKDB"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_LISTBKREC"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_UNLOCKDBREC"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_FULLINV"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_COPYDB"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_GLOBC"])
				) {
				?>
					<h3 class="color-blue mb-4">
						<i class="fas fa-tools"></i> <?php echo $msgstr["mantenimiento"] ?>
					</h3>

					<div class="mainBox">
						<div class="formContent">
							<h4 class="color-blue mb-2">
								<?php echo $msgstr["mantenimiento"] ?>
							</h4>
							<div class="sectionButtons">
								<?php
								if (
									isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
									isset($_SESSION["permiso"]["CENTRAL_UNLOCKDB"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_UNLOCKDB"])
								) {
								?>
									<a href='javascript:EnviarForma("unlockbd","<?php echo $msgstr["mnt_desb"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-unlock-alt"></i> <?php echo $msgstr["mnt_desb"] ?>
									</a><br>
								<?php }
								if (
									isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
									isset($_SESSION["permiso"]["CENTRAL_LISTBKREC"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_LISTBKREC"])
								) {
								?>
									<a href='javascript:EnviarForma("listar","<?php echo $msgstr["mnt_rlb"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-list"></i> <?php echo $msgstr["mnt_rlb"] ?>
									</a>
								<?php }
								if (
									isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
									isset($_SESSION["permiso"]["CENTRAL_UNLOCKDBREC"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_UNLOCKDBREC"])
								) {
								?>
									<a href='javascript:EnviarForma("unlock","<?php echo $msgstr["mnt_dr"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-eraser"></i> <?php echo $msgstr["mnt_dr"] ?>
									</a>
									<a href='javascript:EnviarForma("lisdelrec","<?php echo $msgstr["mnt_lisdr"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-list-ul"></i> <?php echo $msgstr["mnt_lisdr"] ?>
									</a><br>
								<?php }

								if (
									isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
									isset($_SESSION["permiso"]["CENTRAL_FULLINV"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_FULLINV"])
								) {
								?>
									<a href='javascript:EnviarForma("fullinv","<?php echo $msgstr["mnt_gli"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-database"></i> <?php echo $msgstr["mnt_gli"] . " (WXIS)" ?>
									</a>
									<a href='javascript:EnviarForma("fullinvMX","<?php echo $msgstr["mnt_gli"] ?>")' class="bt bt-gray mb-2">
										<i class="fas fa-database"></i> <?php echo $msgstr["mnt_gli"] . " (MX)" ?>
									</a>
								<?php
								}
								if (
									isset($_SESSION["permiso"]["CENTRAL_GLOBC"]) or
									isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_GLOBC"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_DBUTILS"]) or
									isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])
								) {
								?>
									<a href='javascript:EnviarForma("globalc","Global changes")' class="bt bt-gray mb-2">
										<i class="fas fa-edit"></i> <?php echo $msgstr["mnt_globalc"] ?>
									</a>
								<?php } ?>
								<div class="spacer">&#160;</div>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>

			<div class="admin-column col-6">
				<h3 class="color-blue mb-4">
					<i class="fas fa-exchange-alt"></i> <?php echo $msgstr["cnv_export"] . " / " . $msgstr["cnv_import"]; ?>
				</h3>

				<?php if (
					isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
					isset($_SESSION["permiso"]["CENTRAL_IMPEXP"]) or
					isset($_SESSION["permiso"]["CENTRAL_IMPORT"]) or
					isset($_SESSION["permiso"]["CENTRAL_EXPORT"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_IMPEXP"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_IMPORT"])
				) {
				?>
					<div class="mainBox mb-4">
						<div class="formContent">
							<h4 class="color-blue mb-2">
								<?php echo $msgstr["cnv_import"] ?>
							</h4>
							<div class="sectionButtons">
								<a href='javascript:Activar("impiso")' class="bt bt-blue mb-2">
									<i class="fas fa-file-import"></i> <?php echo $msgstr["cnv_iso"] ?>
								</a>
								<a href='javascript:Activar("imptxt")' class="bt bt-blue mb-2">
									<i class="fas fa-file-alt"></i> <?php echo $msgstr["cnv_txt"] ?>
								</a>
							</div>
							<div class="spacer">&#160;</div>
						</div>
					</div>
				<?php } ?>

				<?php if (
					isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
					isset($_SESSION["permiso"]["CENTRAL_IMPEXP"]) or
					isset($_SESSION["permiso"]["CENTRAL_EXPORT"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_DBUTILS"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_IMPEXP"]) or
					isset($_SESSION["permiso"][$base . "_CENTRAL_EXPORT"])
				) {
				?>
					<div class="mainBox mb-4">
						<div class="formContent">
							<h4 class="color-blue mb-2">
								<?php echo $msgstr["cnv_export"] ?>
							</h4>
							<div class="sectionButtons">
								<a href='javascript:Activar("expiso")' class="bt bt-blue mb-2">
									<i class="fas fa-file-export"></i> <?php echo $msgstr["cnv_iso"] ?>
								</a>
								<a href='javascript:Activar("exptxt")' class="bt bt-blue mb-2">
									<i class="fas fa-file-alt"></i> <?php echo $msgstr["cnv_txt"] ?>
								</a>
							</div>
							<div class="spacer">&#160;</div>
						</div>
					</div>
				<?php } ?>
			</div>

		</div>

	</div>

	<form name=admin method=post action=administrar_ex.php onSubmit="Javascript:return false">
		<input type=hidden name=base>
		<input type=hidden name=cipar>
		<input type=hidden name=Opcion>
		<?php
		if (isset($arrHttp["seleccionados"])) {
			echo "<input type=hidden name=seleccion value=" . $arrHttp["seleccionados"] . ">\n";
		}
		?>
	</form>
	<?php include("../common/footer.php"); ?>