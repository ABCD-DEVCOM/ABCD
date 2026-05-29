<?php
/* Modifications
2021-03-08 fho4abcd Replaced helper code fragment by included file
2021-03-08 fho4abcd Improved html & code. Hovering symbols works now
2021-03-15 fho4abcd Add functionality from utilities/iso_export.php (specify folder with explorer)
2021-03-15 fho4abcd Add operation in/out of a frame + correct "backto" url
2021-03-25 fho4abcd Enable export by MX (includes option for marc leader data)
2021-03-27 fho4abcd add path to return script
2021-06-05 fho4abcd Always preselect mx for iso. translations
20211215 fho4abcd Backbutton by included file
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
global $arrHttp;
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

// ==================================================================================================
// INICIO DEL PROGRAMA
// ==================================================================================================
/*
** Old code might not send specific info.
** Set defaults for the return script and frame info
*/
$backtoscript = "../dataentry/administrar.php"; // The default return script
$inframe = 1;                      // The default runs in a frame
if (isset($arrHttp["backtoscript"])) $backtoscript = $arrHttp["backtoscript"];
if (isset($arrHttp["inframe"]))      $inframe = $arrHttp["inframe"];

if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";
if (!isset($arrHttp["tipo"])) $arrHttp["tipo"] = "iso";

// Next line removes functionality due to a bug
// wxis cannot work with the combination of PFT and selected MFN's
if ($arrHttp["tipo"] == "txt") unset($arrHttp["seleccionados"]);

$base = $arrHttp["base"];
$cipar = $arrHttp["cipar"];
// Name of main form in this script, required by dirs_explorer (via function Explorar)
// Note that target of Explorar is still fixed to field "storein"
$targetForm = "forma1";

include("../common/header.php");
?>

<body>
	<script language="JavaScript" type="text/javascript" src="js/lr_trim.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/selectbox.js"></script>
	<script language=javascript>
		function Explorar() {
			msgwin = window.open("../dataentry/dirs_explorer.php?targetForm=<?php echo $targetForm ?>&desde=dbcp&Opcion=explorar&base=<?php echo $arrHttp["base"] ?>&tag=document.forma1.dbfolder", "explorar", "width=400,height=600,top=0,left=0,resizable,scrollbars,menu")
			msgwin.focus()
		}

		function check(x) {
			x = x.replace(/\*/g, "") // delete *
			x = x.replace(/\[/g, "") // delete [
			x = x.replace(/\]/g, "") // delete ]
			x = x.replace(/\</g, "") // delete <
			x = x.replace(/\>/g, "") // delete >
			x = x.replace(/\=/g, "") // delete =
			x = x.replace(/\+/g, "") // delete +
			x = x.replace(/\'/g, "") // delete '
			x = x.replace(/\"/g, "") // delete "
			x = x.replace(/\\/g, "") // delete \
			x = x.replace(/\//g, "") // delete /
			x = x.replace(/\,/g, "") // delete ,
			//   	x = x.replace(/\./g, "")      // delete .
			x = x.replace(/\:/g, "") // delete :
			x = x.replace(/\;/g, "") // delete ;
			x = x.replace(/ /g, "_") // delete spaces
			return x
		}


		function EnviarForma(vp) {
			de = Trim(document.forma1.Mfn.value)
			a = Trim(document.forma1.to.value)
			maxmfn = Trim(document.forma1.maxmfn.value)
			Opcion = ""
			if (de != "" || a != "") Opcion = "rango"
			if (Opcion == "rango") {
				Se = ""
				var strValidChars = "0123456789";
				blnResult = true
				//  test strString consists of valid characters listed above
				for (i = 0; i < de.length; i++) {
					strChar = de.charAt(i);
					if (strValidChars.indexOf(strChar) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?>")
						return
					}
				}
				for (i = 0; i < a.length; i++) {
					strChar = a.charAt(i);
					if (strValidChars.indexOf(strChar) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?>")
						return
					}
				}
				de = Number(de)
				a = Number(a)
				if (de <= 0 || a <= 0 || de > a || a > maxmfn) {
					alert("<?php echo $msgstr["numfr"] ?>")
					return
				}
			}
			Letra = ""

			if (Trim(document.forma1.Expresion.value) == "" && (Trim(document.forma1.Mfn.value) == "" && Trim(document.forma1.seleccionados.value == "")) && Letra == "") {
				alert("<?php echo $msgstr["exp_selreg"] ?>")
				return
			}
			cuenta = 0;
			if (Trim(document.forma1.Expresion.value) != "") cuenta++
			if (Trim(document.forma1.Mfn.value) != "") cuenta++
			if (Trim(document.forma1.seleccionados.value) != "") cuenta++
			if (Letra != "") cuenta++
			if (cuenta > 1) {
				alert("<?php echo $msgstr["r_1opcion"] ?>")
				return
			}
			if (vp == "P") {
				msgwin = window.open("", "VistaPrevia", "")
				msgwin.focus()
				document.forma1.target = "VistaPrevia"
			} else {
				document.forma1.target = ""
			}
			if (vp == "S") {
				archivo = Trim(document.forma1.archivo.value)
				archivo = check(archivo)
				if (archivo == "") {
					alert("<?php echo $msgstr["exp_archivo"] ?>")
					return
				}
				document.forma1.archivo.value = archivo
			}
			document.forma1.Accion.value = vp
			document.forma1.submit()
		}

		function BorrarExpresion() {
			document.forma1.Expresion.value = ''
		}

		function BorrarRango() {
			document.forma1.Mfn.value = ''
			document.forma1.to.value = ''
		}

		function Buscar() {
			base = document.forma1.base.value
			cipar = document.forma1.cipar.value
			//ix=top.menu.document.forma1.formato.selectedIndex
			//if (ix==-1) ix=0
			//Formato=top.menu.document.forma1.formato.options[ix].value
			//FormatoActual="&Formato="+Formato
			FormatoActual = "&Formato=" + base
			Url = "buscar.php?Opcion=formab&prologo=prologoact&Target=s&Tabla=imprimir&base=" + base + "&cipar=" + cipar + FormatoActual
			msgwin = window.open(Url, "Buscar", "menu=no, resizable,scrollbars,width=850,height=400")
			msgwin.focus()
		}
	</script>
	<?php
	// If outside a frame: show institutional info
	if ($inframe != 1) include "../common/institutional_info.php";
	?>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php
			echo $msgstr["cnv_export"] . " " . $msgstr["cnv_" . $arrHttp["tipo"]];
			?>
		</div>
		<div class="actions">
			<?php include "../common/inc_back.php"; ?>
		</div>
		<div class="spacer">&#160;</div>
	</div>
	<?php
	$ayuda = "exportiso.html";
	include "../common/inc_div-helper.php";
	include("../common/inc_get-dbinfo.php"); // sets MAXMFN
	?>

	<style>
		/* CSS Semântico para simular o layout de tabela compacto */
		.export-layout {
			width: 80%;
			margin: 0 auto 20px auto;
		}

		.export-header {
			background-color: var(--abcd-gray-200);
			color: var(--abcd-gray-800);
			padding: 8px;
			text-align: center;
			font-weight: bold;
		}

		.export-title {
			padding: 8px;
			text-align: center;
			font-weight: bold;
		}

		.export-flex-row {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 8px;
		}

		.export-col-right {
			width: 50%;
			text-align: right;
			padding-right: 15px;
		}

		.export-col-left {
			width: 50%;
			text-align: left;
			padding-left: 15px;
		}

		.params-container {
			display: flex;
			justify-content: center;
			align-items: flex-start;
			gap: 20px;
			margin-top: 20px;
		}

		.params-grid {
			display: grid;
			grid-template-columns: max-content 1fr;
			gap: 10px 15px;
			align-items: center;
		}

		.params-label {
			text-align: right;
			color: var(--abcd-gray-800);
			font-weight: bold;
			margin: 8px 0;
		}

		.params-field {
			text-align: left;
			display: flex;
			align-items: center;
		}

		.check_sec input {
			position: relative;
		}

		input.textEntry,
		textarea.textEntry {
			padding: 6px;
		}
	</style>

	<div class="middle form">
		<div class="formContent">

			<div align="center"><br>
				<form name="forma1" method="post" action="exporta_iso_ex.php" onsubmit="Javascript:return false" id="export_forma1">
					<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
					<input type="hidden" name="cipar" value="<?php echo $arrHttp["cipar"] ?>">
					<input type="hidden" name="cnv" value="<?php if (isset($arrHttp["cnv"])) echo $arrHttp["cnv"] ?>">
					<input type="hidden" name="tipo" value="<?php echo $arrHttp["tipo"] ?>">
					<input type="hidden" name="maxmfn" value="<?php echo $arrHttp["MAXMFN"] ?>">
					<input type="hidden" name="backtoscript" value="<?php echo $backtoscript ?>">
					<input type="hidden" name="inframe" value="<?php echo $inframe ?>">
					<input type="hidden" name="Accion">

					<div class="export-layout">
						<div class="export-header"><?php echo $msgstr["r_recsel"] ?></div>

						<div class="export-title"><?php echo $msgstr["r_mfnr"] ?></div>

						<div class="export-flex-row">
							<div class="export-col-right">
								<?php echo $msgstr["r_desde"] ?>:&nbsp;<input type="text" name="Mfn" size="10" value="1" class="textEntry">
							</div>
							<div class="export-col-left">
								<?php echo $msgstr["r_hasta"] ?>:&nbsp;<input type="text" name="to" size="10" value="<?php echo $arrHttp["MAXMFN"]; ?>" class="textEntry">&nbsp;&nbsp;
								<span class="color-gray-600"><?php echo $msgstr["maxmfn"] ?>:&nbsp;<strong class="color-red"><?php echo $arrHttp["MAXMFN"] ?></strong></span>&nbsp;
								<a href="javascript:BorrarRango()" class="bt bt-default" title="<?php echo $msgstr["borrar"] ?>">
									<i class="fas fa-times-circle"></i> <?php echo $msgstr["borrar"] ?>
								</a>
							</div>
						</div>

						<?php if (isset($arrHttp["seleccionados"])) { ?>
							<div class="export-flex-row">
								<strong><?php echo $msgstr["selected_records"] ?>:</strong> &nbsp;
								<?php
								$sel = str_replace("__", ",", trim($arrHttp["seleccionados"]));
								$sel = str_replace("_", "", $sel);
								?>
								<input type="text" name="seleccionados" size="80" value="<?php echo $sel; ?>" class="textEntry">
							</div>
						<?php } else { ?>
							<input type="hidden" name="seleccionados">
						<?php } ?>

						<hr class="my-2 bg-gray-200">

						<div class="export-title"><?php echo $msgstr["r_busqueda"] ?></div>
						<div class="export-flex-row">
							<a href="javascript:Buscar()" class="bt bt-blue me-1" title="<?php echo $msgstr["m_indice"] ?>">
								<i class="fas fa-search"></i>
							</a>
							<input type="text" name="Expresion" size="80" class="textEntry">
							<a href="javascript:BorrarExpresion()" class="bt bt-default ms-1" title="<?php echo $msgstr["borrar"] ?>">
								<i class="fas fa-times-circle"></i>
							</a>
						</div>

						<div class="export-header mt-4"><?php echo $msgstr["r_fgent"] ?></div>
					</div>

					<div class="params-container">
						<div>
							<?php if ($arrHttp["tipo"] != "iso") { ?>
								<a href="javascript:EnviarForma('P')" class="bt bt-yellow" title="<?php echo $msgstr["vistap"] ?>">
									<i class="fas fa-eye"></i>
								</a>
							<?php } ?>
						</div>
						<div class="params-grid">
							<?php if ($arrHttp["tipo"] == "iso") {
								$checkmx = "checked";
								$leaderfiles = glob($db_path . $arrHttp["base"] . "/def/*/leader.fdt");
								if (count($leaderfiles) > 0) {
							?>
									<div class="params-label"><?php echo $msgstr["cnv_export"] . " " . $msgstr["ft_ldr"]; ?></div>
									<div class="params-field">
										<label class="check_sec mb-0">
											<input type="checkbox" name="usemarcformat" checked value="on">
											<span class="checkmark"></span>
										</label>
										<span class="color-blue ms-2"><i class="fas fa-info-circle"></i> <?php echo $msgstr["marcleader_detected"] ?></span>
									</div>
								<?php } ?>

								<div class="params-label"><?php echo $msgstr["exportiso_mx"]; ?></div>
								<div class="params-field">
									<label class="check_sec mb-0">
										<input type="checkbox" name="usemx" <?php echo $checkmx ?> value="on">
										<span class="checkmark"></span>
									</label>
									<span class="color-blue"><i class="fas fa-info-circle"></i> <?php echo $msgstr["unch_wxis"] ?></span>
								</div>
							<?php } ?>

							<div class="params-label"><?php echo $msgstr["folder_name"]; ?></div>
							<div class="params-field">
								<input type="text" name="storein" size="25" value="/wrk" class="textEntry">
								<a href="javascript:Explorar()" class="bt bt-gray" title="<?php echo $msgstr["explore"] ?>">
									<i class="fas fa-folder-open"></i> <?php echo $msgstr["explore"] ?>
								</a>
							</div>

							<div class="params-label"><?php echo $msgstr["cnv_" . $arrHttp["tipo"]]; ?></div>
							<div class="params-field">
								<input type="text" name="archivo" size="25" title="Filename (no extension)" class="textEntry">&nbsp;
								<a href="javascript:EnviarForma('S')" class="bt bt-green" title="<?php echo $msgstr["cnv_export"] ?>">
									<i class="fas fa-download"></i> Salvar
								</a>
							</div>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div><br>


	<?php
	include("../common/footer.php");
	?>