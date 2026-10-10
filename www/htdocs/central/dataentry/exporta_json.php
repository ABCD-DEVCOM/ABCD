<?php

/**
 * Name: exporta_json.php
 * Author: Roger C. Guilherme
 * Description: Interface to export an ABCD database to a Pretty JSON file
 * 
 * Created on: 2026-09-15
 */

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

// =========================================================================
// DOWNLOAD INTERCEPTOR
// Forces the JSON file to be downloaded if the “download_json” parameter exists
// =========================================================================
if (isset($_GET['download_json']) && !empty($_GET['download_json'])) {
	$filename = basename($_GET['download_json']); // basename() evita Directory Traversal
	$file_to_download = $db_path . "wrk/" . $filename;

	if (file_exists($file_to_download)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_to_download));
		readfile($file_to_download);
		exit;
	}
}

// =========================================================================
// DELETE INTERCEPTOR
// Deletes the JSON file if the "delete_json" parameter exists
// =========================================================================
if (isset($_GET['delete_json']) && !empty($_GET['delete_json'])) {
	$filename = basename($_GET['delete_json']); // basename() evita Directory Traversal
	$file_to_delete = $db_path . "wrk/" . $filename;

	if (file_exists($file_to_delete)) {
		@unlink($file_to_delete);
	}

	// Redirects back to the same page, updating the table
	header("Location: exporta_json.php?cipar=" . urlencode($arrHttp["base"]) . "&base=" . urlencode($arrHttp["base"]));
	exit;
}

$backtoscript = "../dataentry/administrar.php";
$inframe = 1;
if (isset($arrHttp["backtoscript"])) $backtoscript = $arrHttp["backtoscript"];

if (isset($arrHttp["inframe"]))      $inframe = $arrHttp["inframe"];

if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";
$arrHttp["tipo"] = "json";

$base = $arrHttp["base"];
$targetForm = "forma1";

// API Mapping Discovery
$apiRootPath = rtrim($ABCD_scripts_path, '/\\') . '/abcd-api';
$i2xMapping = $apiRootPath . '/resources/mappings/' . $base . '.i2x';
$hasDcMapping = file_exists($i2xMapping);

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
			x = x.replace(/[\*\[\]\<\>\=\+\'\"\\\/\,\:\; ]/g, "_")
			return x
		}

		function EnviarForma(vp) {
			de = Trim(document.forma1.Mfn.value)
			a = Trim(document.forma1.to.value)
			maxmfn = Trim(document.forma1.maxmfn.value)
			Opcion = ""
			if (de != "" || a != "") Opcion = "rango"
			if (Opcion == "rango") {
				var strValidChars = "0123456789";
				for (i = 0; i < de.length; i++) {
					if (strValidChars.indexOf(de.charAt(i)) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?? ''; ?>")
						return
					}
				}
				for (i = 0; i < a.length; i++) {
					if (strValidChars.indexOf(a.charAt(i)) == -1) {
						alert("<?php echo $msgstr["especificarvaln"] ?? ''; ?>")
						return
					}
				}
				de = Number(de)
				a = Number(a)
				if (de <= 0 || a <= 0 || de > a || a > maxmfn) {
					alert("<?php echo $msgstr["numfr"] ?? ''; ?>")
					return
				}
			}

			cuenta = 0;
			if (Trim(document.forma1.Expresion.value) != "") cuenta++
			if (Trim(document.forma1.Mfn.value) != "") cuenta++

			if (cuenta > 1) {
				alert("<?php echo $msgstr["r_1opcion"] ?? ''; ?>")
				return
			}
			if (cuenta == 0) {
				alert("<?php echo $msgstr["exp_selreg"] ?? ''; ?>")
				return
			}

			// Check Format Selection
			var formatSelected = document.querySelector('input[name="format"]:checked').value;
			var hasMapping = <?php echo $hasDcMapping ? 'true' : 'false'; ?>;
			if (formatSelected === 'dc' && !hasMapping) {
				alert("<?php echo $msgstr["export_json_err_dc_map"] ?? ''; ?>");
				return;
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
					alert("<?php echo $msgstr["exp_archivo"] ?? ''; ?>")
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
			document.forma1.Mfn.value = '';
			document.forma1.to.value = ''
		}

		function Buscar() {
			base = document.forma1.base.value
			cipar = document.forma1.cipar.value
			Url = "buscar.php?Opcion=formab&prologo=prologoact&Target=s&Tabla=imprimir&base=" + base + "&cipar=" + cipar
			msgwin = window.open(Url, "Buscar", "menu=no, resizable,scrollbars,width=850,height=400")
			msgwin.focus()
		}
	</script>
	<?php if ($inframe != 1) include "../common/institutional_info.php"; ?>

	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["export_json_title"] ?? ''; ?>
		</div>
		<div class="actions">
			<?php include "../common/inc_back.php"; ?>
		</div>
		<div class="spacer">&#160;</div>
	</div>

	<?php
	$ayuda = "exportiso.html";
	include "../common/inc_div-helper.php";
	include("../common/inc_get-dbinfo.php");
	?>

	<style>
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

		input.textEntry {
			padding: 6px;
		}

		.radio-group {
			display: flex;
			gap: 15px;
		}

		.radio-option {
			display: flex;
			align-items: center;
			gap: 5px;
			cursor: pointer;
		}
	</style>

	<div class="middle form">
		<div class="formContent">
			<div align="center"><br>
				<form name="forma1" method="post" action="exporta_json_ex.php" onsubmit="Javascript:return false" id="export_forma1">
					<input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
					<input type="hidden" name="cipar" value="<?php echo $arrHttp["cipar"] ?>">
					<input type="hidden" name="tipo" value="json">
					<input type="hidden" name="maxmfn" value="<?php echo $arrHttp["MAXMFN"] ?>">
					<input type="hidden" name="backtoscript" value="<?php echo $backtoscript ?>">
					<input type="hidden" name="inframe" value="<?php echo $inframe ?>">
					<input type="hidden" name="mapping_file" value="<?php echo $i2xMapping ?>">
					<input type="hidden" name="Accion">

					<div class="export-layout">
						<div class="export-header"><?php echo $msgstr["r_recsel"] ?? ''; ?></div>
						<div class="export-title"><?php echo $msgstr["r_mfnr"] ?? ''; ?></div>

						<div class="export-flex-row">
							<div class="export-col-right">
								<?php echo $msgstr["r_desde"] ?? ''; ?>:&nbsp;<input type="text" name="Mfn" size="10" value="1" class="textEntry">
							</div>
							<div class="export-col-left">
								<?php echo $msgstr["r_hasta"] ?? ''; ?>:&nbsp;<input type="text" name="to" size="10" value="<?php echo $arrHttp["MAXMFN"]; ?>" class="textEntry">&nbsp;&nbsp;
								<span class="color-gray-600"><?php echo $msgstr["maxmfn"] ?? ''; ?>:&nbsp;<strong class="color-red"><?php echo $arrHttp["MAXMFN"] ?></strong></span>&nbsp;
								<a href="javascript:BorrarRango()" class="bt bt-default" title="<?php echo $msgstr["borrar"] ?? ''; ?>">
									<i class="fas fa-times-circle"></i> <?php echo $msgstr["borrar"] ?? ''; ?>
								</a>
							</div>
						</div>

						<hr class="my-2 bg-gray-200">

						<div class="export-title"><?php echo $msgstr["r_busqueda"] ?? ''; ?></div>
						<div class="export-flex-row">
							<a href="javascript:Buscar()" class="bt bt-blue me-1" title="<?php echo $msgstr["m_indice"] ?? ''; ?>">
								<i class="fas fa-search"></i>
							</a>
							<input type="text" name="Expresion" size="80" class="textEntry">
							<a href="javascript:BorrarExpresion()" class="bt bt-default ms-1" title="<?php echo $msgstr["borrar"] ?? ''; ?>">
								<i class="fas fa-times-circle"></i>
							</a>
						</div>

						<div class="export-header mt-4"><?php echo $msgstr["export_json_config"] ?? ''; ?></div>
					</div>

					<div class="params-container">
						<div class="params-grid">
							<div class="params-label"><?php echo $msgstr["export_json_format"] ?? ''; ?></div>
							<div class="params-field">
								<div class="radio-group">
									<label class="radio-option">
										<input type="radio" name="format" value="native" <?php echo !$hasDcMapping ? 'checked' : ''; ?>>
										<strong><?php echo $msgstr["export_json_native"] ?? ''; ?></strong> <small class="color-gray-600"><?php echo $msgstr["export_json_raw"] ?? ''; ?></small>
									</label>
									<label class="radio-option" <?php if (!$hasDcMapping) echo 'style="opacity: 0.5;"'; ?>>
										<input type="radio" name="format" value="dc" <?php echo $hasDcMapping ? 'checked' : 'disabled'; ?>>
										<strong><?php echo $msgstr["export_json_dc"] ?? ''; ?></strong> <small class="color-gray-600"><?php echo $msgstr["export_json_mapped"] ?? ''; ?></small>
									</label>
								</div>
							</div>

							<?php if (!$hasDcMapping): ?>
								<div></div>
								<div class="params-field" style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; font-size: 12px; margin-bottom: 10px;">
									<i class="fas fa-info-circle"></i> <?php echo $msgstr["export_json_dc_disabled"] ?? ''; ?> <br>
									<a href="../settings/api_mapper.php?base=<?php echo $base; ?>" class="bt bt-blue" style="margin-top: 5px; font-size: 11px;"><i class="fas fa-external-link-alt"></i> <?php echo $msgstr["export_json_open_mapper"] ?? ''; ?></a>
								</div>
							<?php endif; ?>
							<input type="hidden" name="storein" size="25" value="/wrk" >

							<div class="params-label"><?php echo $msgstr["export_json_filename"] ?? ''; ?></div>
							<div class="params-field">
								<input type="text" name="archivo" size="25" title="<?php echo $msgstr["export_json_filename_help"] ?? ''; ?>" class="textEntry" value="<?php echo $base; ?>_export">&nbsp;
								<a href="javascript:EnviarForma('S')" class="bt bt-green" title="<?php echo $msgstr["export_json_btn_generate"] ?? ''; ?>">
									<i class="fas fa-download"></i> <?php echo $msgstr["export_json_btn_generate"] ?? ''; ?>
								</a>
							</div>
						</div>
					</div>
				</form>

				<?php
				// =========================================================================
				// LIST OF JSON FILES GENERATED IN THE WRK FOLDER
				// =========================================================================
				$wrk_dir = $db_path . "wrk";
				if (is_dir($wrk_dir)) {
					$files = glob($wrk_dir . "/*.json");
					if ($files && count($files) > 0) {
						// Sort the files by modification date (most recent first)
						usort($files, function ($a, $b) {
							return filemtime($b) - filemtime($a);
						});
				?>
						<div class="export-layout mt-4">
							<div class="export-header">
								<?php echo $msgstr["export_json_generated_files"] ?? 'Arquivos JSON gerados (Pasta WRK)'; ?>
							</div>
							<table style="width:100%; border-collapse: collapse; margin-top: 10px; text-align: left;">
								<tr style="background-color: var(--abcd-gray-200); color: var(--abcd-gray-800);">
									<th style="padding:10px; border-bottom: 1px solid #ccc;">
										<?php echo $msgstr["archivo"] ?? 'Arquivo'; ?>
									</th>
									<th style="padding:10px; text-align:center; border-bottom: 1px solid #ccc;">
										<?php echo $msgstr["file_date"] ?? 'Data de Criação'; ?>
									</th>
									<th style="padding:10px; text-align:right; border-bottom: 1px solid #ccc;">
										<?php echo $msgstr["file_size"] ?? 'Tamanho'; ?>
									</th>
									<th style="padding:10px; text-align:center; border-bottom: 1px solid #ccc;">
										<?php echo $msgstr["actions"] ?? 'Ações'; ?>
									</th>
								</tr>

								<?php
								foreach ($files as $file) {
									$filename = basename($file);
									$filedate = date("d/m/Y H:i:s", filemtime($file));
									$filesize = number_format(filesize($file) / 1024, 2) . " KB";

									// Action URLs
									$download_url = "?download_json=" . urlencode($filename) . "&base=" . urlencode($arrHttp["base"]);
									$delete_url = "?delete_json=" . urlencode($filename) . "&base=" . urlencode($arrHttp["base"]);
								?>

									<tr style="border-bottom: 1px solid #eee;" onmouseover="this.style.backgroundColor='#f9f9f9'" onmouseout="this.style.backgroundColor=''">
										<td style="padding:8px;"><strong><?php echo $filename; ?></strong></td>
										<td style="padding:8px; text-align:center;"><?php echo $filedate; ?></td>
										<td style="padding:8px; text-align:right;"><?php echo $filesize; ?></td>
										<td style="padding:8px; text-align:center;">
											<a href="<?php echo $download_url; ?>" class="bt bt-green" title="<?php echo $msgstr['download'] ?? 'Baixar'; ?>">
												<i class="fas fa-download"></i>
											</a>&nbsp;
											<a href="<?php echo $delete_url; ?>" class="bt bt-red" title="<?php echo $msgstr['eliminar'] ?? 'Excluir'; ?>" onclick="return confirm('<?php echo $msgstr['cnv_deltab'] ?? 'Deseja excluir este arquivo?'; ?>');">
												<i class="fas fa-trash"></i>
											</a>
										</td>
									</tr>

								<?php
								} // End of the foreach loop
								?>

							</table>
						</div>
				<?php
					}
				}
				?>

			</div>
		</div>
	</div><br>
	<?php include("../common/footer.php"); ?>