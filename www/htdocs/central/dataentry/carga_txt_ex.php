<?php
/*
20220715 fho4abcd Use $actparfolder as location for .par files
20260321 rogercgui UI improvements and a buffer fix for imports with a single record (a PHP buffer issue where output is only sent to the browser once it reaches 4KB, which meant that small imports did not display anything until the end of the process). The script now forces the header and progress bar to be sent after every record, ensuring visual feedback even for small imports.
20261102 Conversion tables with a header row ("[HEADER]" mode) and loading the text from a file.
Included by carga_txt.php, carga_txt_cnv.php, carga_txt_ver.php and carga_txt_ex.php.
1. Default texts. They are used only when the language files do not define the same key,
   so they can be moved to lang/<lang>/admin.php (or soporte.php) at any time.
2. Functions that read a CSV/TSV text whose first row holds the column names.
Format of the .cnv file in this mode (first line = [HEADER]; the "
label" is the column name):
[HEADER]
*/


//Procesa el archivo TXT y lo carga en base de datos
session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}

include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"];

include("../lang/admin.php");
include("../lang/soporte.php");
include("carga_txt_hdr.php");

// -------------------------------------------------------------------------
// BUFFER OPTIMISATION (CORRECTION FOR 1 RECORD)
// -------------------------------------------------------------------------
// Disables the compression that limits the output
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) {
	@ob_end_flush();
}
ob_implicit_flush(1);

// Send the ABCD header FIRST, before any heavy processing
include("../common/header.php");

// Forces the server to send the header to the browser by sending 4KB of blank space.
// This tricks Apache/Nginx, which usually buffer packets smaller than 4KB.
echo str_pad('', 4096) . "\n";
@ob_flush();
flush();
// -------------------------------------------------------------------------

set_time_limit(0);

?>
<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["cnv_import"] . " " . $msgstr["cnv_txt"] ?>
	</div>
	<div class="actions">
		<?php include "../common/inc_close.php" ?>
	</div>
	<div class="spacer">&#160;</div>
</div>

<?php $ayuda = "txt2isis.html";
include "../common/inc_div-helper.php" ?>

<div class="middle form">
	<div class="formContent">
		<?php

	// The routine that converts labels to ISIS tags is included
	include("rotulos2tags.php");

		function Delimited($labels, $record)
		{
			$output = array();
			if (trim($record != "")) {
				$t = explode("\t", $record);
				foreach ($labels as $row_rotulo) {
					if (trim($row_rotulo[1]) != "") {
						$tag[$row_rotulo[0]] = $row_rotulo[1];
						$repetible[$row_rotulo[0]] = $row_rotulo[4];
					}
				}
				$ix = 0;
				foreach ($t as $val) {
					$ix = $ix + 1;
					if (trim($val) != "")
						if (isset($tag[$ix])) {
							$output[$tag[$ix]] = str_replace("\n", " ", $val);
							$output[$tag[$ix]] = str_replace("\r", " ", $val);
						}
				}
			}
			return $output;
		}

		function SubCampos($field, $subc, $delim)
		{
			$subc = rtrim($subc);
			$ixpos = 0;
			for ($i = 0; $i < strlen($subc); $i++) {
				$sc = substr($subc, $i, 1);
				$ed = substr($delim, $i, 1);
				if ($i == 0) {
					if ($ed == " ")
						$field = '^' . $sc . $field;
					else
						$field = str_replace($ed, '^' . $sc, $field);
					$field = str_replace('^' . $sc . " ", '^' . $sc, $field);
				} else {
					$field = str_replace($ed, '^' . $sc, $field);
					$field = str_replace('^' . $sc . " ", '^' . $sc, $field);
				}
			}
			return $field;
		}

		function ProcesarBD($base, $output, $rotulo)
		{
			global $arrHttp;
			$ValorCapturado = "";
			$rep = "";
			$formato = "";
			foreach ($output as $key => $linea) {
				if (isset($rotulo[$key][2])) $subc = $rotulo[$key][2];
				else $subc = "";
				if (isset($rotulo[$key][3])) $delim = $rotulo[$key][3];
				else $delim = "";
				if (isset($rotulo[$key][4])) $rep = $rotulo[$key][4];
				if (isset($rotulo[$key][5])) $formato = $rotulo[$key][5];

				if (is_array($linea)) {
					foreach ($linea as $item_val) {
						if (trim($item_val) != "") {
							if (trim($rep) != "") {
								$sal = explode($rep, $item_val);
								foreach ($sal as $field) {
									if (trim($subc) != "") $field = SubCampos($field, $subc, $delim);
									$ValorCapturado .= "<$key 0>" . trim($field) . "</" . $key . ">";
								}
							} else {
								if (trim($subc) != "") $item_val = SubCampos($item_val, $subc, $delim);
								$ValorCapturado .= "<$key 0>" . trim($item_val) . "</" . $key . ">";
							}
						}
					}
				} else {
					$ValorCapturado .= "<$key 0>" . trim($linea) . "</" . $key . ">";
				}
			}
			ActualizarRegistro($base, $ValorCapturado);
		}

		function LeerTablaCnv()
		{
			global $separator, $arrHttp, $db_path;
			$separator = "";
			$fp = file($db_path . $arrHttp["base"] . "/cnv/" . $arrHttp["cnv"]);
			$ix = -1;
			foreach ($fp as $line_val) {
				if (substr($line_val, 0, 2) <> '//') {
					if ($ix == -1) {
						$separator = trim($line_val);
						$ix = 0;
					} else {
						$ix = $ix + 1;
						$t = explode('|', $line_val);
						$t[1] = trim($t[1]);
						$t[0] = trim($t[0]);
						$rotulo[$t[1]][0] = $t[0];
						$rotulo[$t[1]][1] = $t[1];
						$rotulo[$t[1]][2] = $t[2];
						if (isset($t[3])) $rotulo[$t[1]][3] = $t[3];
						if (isset($t[4])) $rotulo[$t[1]][4] = $t[4];
						if (isset($t[5])) $rotulo[$t[1]][5] = $t[5];
					}
				}
			}
			return $rotulo;
		}

		function ActualizarRegistro($base, $ValorCapturado)
		{
			global $arrHttp, $Wxis, $xWxis, $db_path, $wxisUrl, $lang_db, $msgstr, $actparfolder;
			$ValorCapturado = urlencode($ValorCapturado);

			$Mfn = "New";
			$base = $arrHttp["base"];
			$IsisScript = $xWxis . "crear_registro.xis";
			$Formato = "ALL";
			$query = "&base=$base&cipar=" . $db_path . $actparfolder . "$base.par" . "&login=" . $_SESSION["login"] . "&Mfn=$Mfn" . "&Pft=$Formato" . "&ValorCapturado=" . $ValorCapturado;
			$contenido = array();
			include("../common/wxis_llamar.php");
			foreach ($contenido as $linea) {
				echo $linea . " ";
			}
		}

		//----------------------------------------------------------------------------------------

		echo "<script>
function RefreshDB(){
	window.opener.location.href='inicio_base.php?base=" . $arrHttp["base"] . "'
	self.close()
}
</script>";

		$Errores = false;
		echo "<form method=post name=forma1 action=carga_txt_ex.php>
<input type=hidden name=Actualizar value=SI>\n";

		if (!isset($arrHttp["Actualizar"])) {
			foreach ($arrHttp as $var => $val_http) {
				echo "<input type=hidden name=$var value=\"" . urlencode($val_http) . "\">\n";
			}
		}

		$value = $arrHttp["bdd"];
		if (isset($arrHttp["Actualizar"])) $value = urldecode($arrHttp["bdd"]);
		$value = str_replace('&nbsp;&nbsp;', "&nbsp;", $value);

		if (trim($value) != "") {
			$noLocalizados = "";
			$separator = "";
			$base = $arrHttp["base"];

			echo "<h4>Base: " . $arrHttp["base"] . "</h4>";

			$rotulo = LeerTablaCnv();

			if ($separator == '[HEADER]') {
				// Header-row mode: the first row of the text holds the column names.
				// Every element of $variables is already the array tag => values that ProcesarBD() expects.
				$header_info = array();
				$variables = TxtImp_ReadHeaderTable($value, $rotulo, $header_info);
				TxtImp_PrintInfo($header_info);
				if ($header_info["matched"] == 0) {
					echo "<h4 style='color:red'>" . $msgstr["txtimp_nomatch"] . "</h4>";
					include "../common/footer.php";
					die;
				}
			} else {
			if ($separator != '[TABS]') {
				$variables = explode($separator, $value);
			} else {
				$variables = explode("\n", $value);
			}

			// --- CORRECTION OF EMPTY ARRAY AT THE END OF THE TXT FILE ---
			// Sometimes the TXT record ends with an extra line break, creating an empty record that causes the account to malfunction.
			$variables = array_filter($variables, function ($v) {
				return trim($v) !== "";
			});
			// -----------------------------------------------
			}

			$total_rows = count($variables);
			$current_line = 0;

			if (isset($arrHttp["Actualizar"])) {
				echo "<div style='margin-bottom: 15px;'>
                <strong>".$msgstr['proc_records']."...</strong>
                <div style='width: 100%; background: #e9ecef; border-radius: 4px; margin-top: 5px; border: 1px solid #ccc; overflow: hidden;'>
                    <div id='progressBar' style='height: 24px; background: #28a745; width: 0%; text-align: center; color: white; line-height: 24px; font-weight: bold; font-size: 14px; transition: width 0.1s linear;'>0%</div>
                </div>
              </div>";
			}

			echo "<div id='logContainer' style='max-height: 55vh; overflow-y: auto; background: #fdfdfd; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); font-family: monospace; font-size: 0.9em;'>";

			// Forces the empty log box to be sent before the heavy loop begins
			@ob_flush();
			flush();

			foreach ($variables as $record) {
				$current_line++;
				$noLocalizados = "";

				if ($separator == '[HEADER]') {
					$output = $record;
				} elseif ($separator == '[TABS]') {
					$output = Delimited($rotulo, $record);
				} else {
					$output = Rotulos2Tags($rotulo, $record, $separator);
				}

				if (count($output) > 0) {
					if (!isset($arrHttp["Actualizar"])) {
						echo "<p style='margin-top:10px; border-top: 1px dashed #ccc; padding-top:10px;'><b>--------</b> <br>";
						foreach ($output as $key => $field_value) {
							if (is_array($field_value)) {
								foreach ($field_value as $field) {
									echo "<strong>" . $rotulo[$key][1] . "</strong> " . $field . "<br>";
								}
							} else {
								echo "<strong>" . $rotulo[$key][1] . "</strong> " . $field_value . "<br>";
							}
						}
					} else {
						echo "<div style='margin-bottom: 4px; border-bottom: 1px solid #eee; padding-bottom: 4px;'>";
						echo "<strong style='color: #0056b3;'>Rec {$current_line}/{$total_rows}:</strong> ";
						ProcesarBD($arrHttp["base"], $output, $rotulo);
						echo "</div>";

						$percent = ($total_rows > 0) ? round(($current_line / $total_rows) * 100) : 100;

						echo "<script>
                    document.getElementById('progressBar').style.width = '{$percent}%';
                    document.getElementById('progressBar').innerText = '{$percent}% ({$current_line}/{$total_rows})';
                    var logBox = document.getElementById('logContainer');
                    logBox.scrollTop = logBox.scrollHeight;
                </script>";

						// Send more padding to force a flush on every iteration, ensuring that one record works
						echo str_pad('', 1024) . "\n";
						@ob_flush();
						flush();
					}
				}

				if (trim($noLocalizados) != "") {
					$Errores = true;
					echo "<font color=red><b>" . $msgstr["cnv_nol"] . "</b></font color=black><br>" . nl2br($noLocalizados);
				}
			}

			echo "</div>";
		}

		echo "<div style='background: #f1f1f1; padding: 15px; border: 1px solid #ddd; border-radius: 4px; text-align: center;'>";

		if (!isset($arrHttp["Actualizar"])) {
			echo "<span style='font-size: 1.1em; margin-right: 15px;'><strong>" . $msgstr["bd"] . ": " . $arrHttp["base"] . "</strong></span> ";
			echo "<button type='submit' class='bt bt-green'><i class='fas fa-save'></i> " . $msgstr["actualizar"] . "</button>";
		} else {
			echo "<script>document.getElementById('progressBar').style.background = '#198754'; document.getElementById('progressBar').innerText = '100% - ".$msgstr['completed']."';</script>";

			echo "<a class='bt bt-red' href='javascript:self.close()'><i class='fas fa-times'></i> " . $msgstr["cerrar"] . "</a> &nbsp; &nbsp;";
			echo "<a class='bt bt-green' href='javascript:RefreshDB()'><i class='fas fa-sync'></i> " . $msgstr["reopendb"] . "</a>";
		}

		echo "</div>";

		?>
		</form>
	</div>
</div>
<?php
include "../common/footer.php"
?>