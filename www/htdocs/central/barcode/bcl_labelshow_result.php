<?php
/*
20220306 fho4abcd div-helper, added informational and error messages, moved functions to end of file
20220309 fho4abcd No "em" this is a relative unit, add link to barcode font script,add option border
20220310 fho4abcd Better check for nothing returned.
20220321 fho4abcd adapt to new includes, renamed to bcl_labelshow_result
20220628 fho4abcd removed unused output options (e.g Excel)
20220630 fho4abcd Improve html, remove unused options
20220717 fho4abcd Use $actparfolder as location for .par files
*/
set_time_limit(0);
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";//die;
include("../config.php");

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");
include("../lang/reports.php");
$base = $arrHttp["base"];

include("inc_barcode_constants.php");
include("inc_barcode_configure.php");
if (!isset($arrHttp["output"])) $arrHttp["output"] = "display";

switch ($arrHttp["output"]) {
	case "doc":
		$filename = "barcode_" . $base . ".doc";
		header('Content-Type: application/msword; charset=windows-1252');
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");

		#echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
		#echo '   xmlns:w="urn:schemas-microsoft-com:office:word"' . "\n";
		#echo '   xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
		break;
	case "txt":
		$filename = "barcode_" . $base . ".txt";
		header('Content-Type: text/plain;  charset=windows-1252');
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
		break;
	case "txt_print":
		header('Content-Type: text/html;  charset=windows-1252');
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
		break;
	default: // the display option
		include("../common/header.php");
		echo "<body>";
		include "../common/inc_div-helper.php";
?>
		<div class="middle form">
			<div class="formContent">
			<?php
			break;
	}

	//SE LEE EL ARCHIVO DE CONFIGURACION
	$configfile = $configfileprefix . $arrHttp["tipo"] . ".conf";
	$configfilefull = $db_path . $configfile;
	if (!file_exists($configfilefull)) {
		echo "<div style='color:red'>" . $msgstr["error"] . ": " . $msgstr["misfile"] . " &rarr; " . $configfile . "<br>" . $msgstr["barcode_conf"] . "</div>";
		echo "</div></div>";
		include("../common/footer.php");
		die;
	}
	if ($arrHttp["output"] == "display") {
			?>
			<div><?php //echo $configfile." ".$msgstr["doesexist"]
					?></div>
			<?php
		}
		// Read the barcode configuration file
		$bar_c = array();
		$fp = file($configfilefull);
		if ($fp) {
			foreach ($fp as $conf) {
				$conf = trim($conf);
				if ($conf != "") {
					$a = explode('=', $conf, 2);
					$bar_c[$a[0]] = $a[1];
				}
			}
		}

		// Check if the pft is a file
		$ispftfile = false;
		if ($arrHttp["output"] == "txt" or $arrHttp["output"] == "txt_print") {
			$bar_c["label_format"] = $bar_c["label_format_txt"];
			if (substr($bar_c["label_format"], 0, 1) == '@') {
				$bar_c["label_format"] = ",@" . $db_path . $configfileprefix . substr($bar_c["label_format"], 1) . ",";
				$ispftfile = true;
			}
		} else {
			if (substr($bar_c["label_format"], 0, 1) == '@') {
				$bar_c["label_format"] = ",@" . $db_path . $configfileprefix . substr($bar_c["label_format"], 1) . ",";
				$ispftfile = true;
			}
		}
		$Pft = trim($bar_c["label_format"]) . "/";
		// Check if the barcode pft file exists.
		if ($ispftfile == true) {
			//  Note that it starts with ",@" and ends with ",/";
			$pftfilefull = substr($Pft, 2);
			$pftfilefull = substr($pftfilefull, 0, -2);
			$pftfile = substr($pftfilefull, strlen($db_path));
			if (!file_exists($pftfilefull)) {
				echo "<div style='color:red'>" . $msgstr["error"] . ": " . $msgstr["misfile"] . " &rarr; " . $pftfilefull . "<br>" . $msgstr["barcode_conf"] . "</div>";
				echo "</div></div>";
				include("../common/footer.php");
				die;
			} else {
				if ($arrHttp["output"] == "display") {
			?>
					<div><?php //echo $pftfile." ".$msgstr["doesexist"]
							?></div>
			<?php
				}
			}
		}
		switch ($arrHttp["Opcion"]) {
			case "mfn":
				MfnBarCode($base, $arrHttp["mfn_from"], $arrHttp["mfn_to"], $bar_c, $Pft);
				break;
			case "clasificacion":
				ClasificacionBarCode($base, $arrHttp["classification_from"], $arrHttp["classification_to"], $bar_c, $Pft);
				break;
			case "inventario":
				InventarioBarCode($base, $arrHttp["inventory_from"], $arrHttp["inventory_to"], $bar_c, $Pft);
				break;
			case "lista_inventario":
				InventarioLista($base, $arrHttp["inventory_list"], $bar_c, $Pft);
				break;
		}
		if ($arrHttp["output"] == "display") {
			?>
			</div>
		</div>
		</body>

		</html>
		<?php
		}
		//================= Functions ==================

		function MostrarSalida($contenido, $medio, $bar_c)
		{
			global $arrHttp, $msgstr;
			if ($contenido == NULL or (count($contenido) == 1 && $contenido[0] == "")) echo "<div style='color:red'>" . $msgstr["barcode_script_none"] . "</div>";
			if ($medio == "display" or $medio == "doc") {
				$table_width = $bar_c["cols"] * $bar_c["width"];
				// Always apply style to display and doc
		?>

			<style>


				.columna {
					border-collapse: collapse;
					border-spacing: 0px;
				}

				.td1 {
					<?php if (isset($arrHttp["border"])) { ?>border: 1px solid;
					<?php } ?>overflow: hidden;
					width: <?php echo $bar_c["width"] . "cm"; ?>;
					height: <?php echo $bar_c["height"] . "cm"; ?>;
				}
			</style>
			<table class=columna>
				<tr>
				<?php
			}
			$ncols = 0;
			$print = "";
			foreach ($contenido as $value) {
				$value = trim($value);
				if ($value != "") {
					$ix = strpos($value, "*INV*");
					if ($ix !== false) {
						$ix1 = strpos($value, "*INV*", $ix + 2);
						if ($ix1 !== false)
							$value = substr($value, 0, $ix) . substr($value, $ix1 + 5);
					}
					$salida = str_replace('!!!', PHP_EOL, $value);
					switch ($medio) {
						case "txt":
							//echo utf8_encode($salida);
							echo $salida;
							break;
						case "txt_print":
							$print .= $salida;
							break;

						default:
							$ncols = $ncols + 1;
							if ($ncols > $bar_c["cols"] and $bar_c["cols"] != 0) {
								echo "</tr>\n<tr>";
								$ncols = 1;
							}
							if ($bar_c["cols"] > 0) {
								$width = $bar_c["width"];
								$height = $bar_c["height"];
								echo "<td class=td1>$salida</td>\n";
							} else {
								echo $salida . "<p>";
							}
					}
				}
			}
			if (isset($bar_c["cols"]) and $bar_c["cols"] > 0 and $medio != "txt" and $medio != "txt_print") {
				echo "</tr></table>";
			}
			if ($medio == "txt_print") {
				$salida = explode(PHP_EOL, $print);
				?>
					<script>
						<?php
						echo "lineas=Array();i=-1;\n";
						foreach ($salida as $linea) {
							echo "i=i+1\n";
							echo "lineas[i]=\"$linea\"\n";
						}
						?>
						msgwin = window.open("", "recibo", "width=400, height=300, scrollbars, resizable")
						for (j = 0; j <= i; j++) {
							msgwin.document.writeln(lineas[j])
						}
						msgwin.document.close()
						msgwin.focus()
						msgwin.print()
						msgwin.close()
						self.close();
					</script>
			<?php
			}
		}


		function MfnBarCode($base, $from, $to, $bar_c, $Pft)
		{
			global $xWxis, $msgstr, $db_path, $Wxis, $wxisUrl, $lang_db, $arrHttp, $actparfolder;
			$base = $base;
			$cipar = $base . ".par";
			$Opcion = "leer";
			$IsisScript = $xWxis . "leer_mfnrange.xis";
			$query = "&base=$base&cipar=$db_path" . $actparfolder . $cipar . "&from=" . $from . "&to=$to&Pft=$Pft";
			if (isset($arrHttp["wxis_sum"])) echo $msgstr["barcode_wxis_cmd"] . ": " . $IsisScript . " &rarr; " . urldecode($query);
			if ($arrHttp["output"] == "display") echo "<div>" . $msgstr["barcode_script"] . ": " . $IsisScript . "</div>";
			//echo $Pft;
			include("../common/wxis_llamar.php");
			if (count($contenido) == 1 && $contenido[0] == "") echo "<div style='color:red'>" . $msgstr["barcode_script_empty"] . "</div>";

			/*foreach ($contenido as $value){
    	 echo "***".$value;
    }
    die;  */
			$contenido = implode("!!!", $contenido);
			$contenido = explode('%%%', $contenido);
			MostrarSalida($contenido, $arrHttp["output"], $bar_c);
		}
		function InventarioLista($base, $lista, $bar_c, $Pft)
		{
			global $xWxis, $msgstr, $db_path, $Wxis, $wxisUrl, $lang_db, $arrHttp, $actparfolder;
			$Expresion = "";
			$inv = explode(",", $lista);
			$comp = array();
			foreach ($inv as $value) {
				$value = trim($value);
				if ($value != "") {
					$comp[$value] = $value;
					if ($Expresion == "") {
						$Expresion = $bar_c["inventory_number_pref_list"] . $value;
					} else {
						$Expresion .= " or " . $bar_c["inventory_number_pref_list"] . $value;
					}
				}
			}
			$IsisScript = $xWxis . "imprime.xis";
			$query = "&base=" . $base . "&cipar=$db_path" . $actparfolder . $base . ".par&Expresion=" . urlencode($Expresion) . "&Opcion=buscar&count=100&Pft=" . urlencode($Pft);
			if (isset($arrHttp["wxis_sum"])) echo $msgstr["barcode_wxis_cmd"] . ": " . $IsisScript . " &rarr; " . urldecode($query);
			include("../common/wxis_llamar.php");
			$inventario = array();
			$array_c = array();
			$contenido = implode("!!!", $contenido);
			$contenido = explode('%%%', $contenido);
			foreach ($contenido as $value) {
				if (trim($value) != "") {
					foreach ($inv as $ni) {
						$ni = trim($ni);
						if (strpos($value, '*INV*' . $ni . '*INV') !== FALSE) {
							if (!isset($inventario[$ni])) {
								$inventario[$ni] = $ni;
								$array_c[] = $value;
							} else {
							}
						}
					}
				}
			}
			MostrarSalida($array_c, $arrHttp["output"], $bar_c);
		}

		function ClasificacionBarCode($base, $from, $to, $bar_c, $Pft)
		{
			global $arrHttp, $xWxis, $msgstr, $db_path, $Wxis, $wxisUrl, $lang_db, $actparfolder;
			$Prefijo = trim($bar_c["classification_number_pref"]) . trim($arrHttp["classification_from"]);
			$to = trim($bar_c["classification_number_pref"]) . trim($arrHttp["classification_to"]);
			$query = "&base=" . $base . "&cipar=$db_path" . $actparfolder . $base . ".par&Opcion=diccionario&prefijo=" . $Prefijo . "&hasta=" . $to . "&Pft=" . $Pft;
			$IsisScript = $xWxis . "indice.xis";
			if (isset($arrHttp["wxis_sum"])) echo $msgstr["barcode_wxis_cmd"] . ": " . $IsisScript . " &rarr; " . urldecode($query);
			include("../common/wxis_llamar.php");
			$contenido = implode("!!!", $contenido);
			$contenido = explode('%%%', $contenido);
			MostrarSalida($contenido, $arrHttp["output"], $bar_c);
		}

		function InventarioBarCode($base, $from, $to, $bar_c, $Pft)
		{
			global $arrHttp, $xWxis, $msgstr, $db_path, $Wxis, $wxisUrl, $lang_db, $actparfolder;
			$Prefijo = trim($bar_c["inventory_number_pref"]) . trim($arrHttp["inventory_from"]);
			$to = trim($bar_c["inventory_number_pref"]) . trim($arrHttp["inventory_to"]) . "ZZZ";
			$query = "&base=" . $base . "&cipar=$db_path" . $actparfolder . $base . ".par&Opcion=diccionario&prefijo=" . urlencode($Prefijo) . "&hasta=" . urlencode($to) . "&Pft=" . urlencode($Pft);
			$IsisScript = $xWxis . "indice.xis";
			if (isset($arrHttp["wxis_sum"])) echo $msgstr["barcode_wxis_cmd"] . ": " . $IsisScript . " &rarr; " . urldecode($query);
			$array_c = array();
			include("../common/wxis_llamar.php");
			$contenido = implode("!!!", $contenido);
			$contenido = explode('%%%', $contenido);
			foreach ($contenido as $value) {
				if (trim($value) != "") {
					$numeros = "";
					$ix = strpos($value, "*INV*");
					if ($ix !== false) {
						$ix1 = strpos($value, "*INV*", $ix + 1);
						$ni = substr($value, $ix + 5, $ix1 - $ix - 5);
						if ($ni >= trim($arrHttp["inventory_from"]) and $ni <= trim($arrHttp["inventory_to"])) {
							if (!isset($inventario[$ni])) {
								$array_c[] = $value;
								$inventario[$ni] = $ni;
							}
						}
					} else {
						$ni = $value;
						if (!isset($inventario[$ni])) {
							$array_c[] = $value;
							$inventario[$ni] = $ni;
						}
					}
				}
			}
			MostrarSalida($array_c, $arrHttp["output"], $bar_c);
		}
			?>