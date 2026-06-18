<?php
	/* Modifications
20210613 fho4abcd remove password, lineends
20210623 fho4abcd Replaced helper code fragment by included file, improve backbutton, add page title, small html improvements
20210623 fho4abcd Expand echo into html (readability), translate string, enable click on MFN for all situations
20211216 fho4abcd Backbutton by included file
20220711 fho4abcd Use $actparfolder as location for .par files
20260618 rogercgui Add new action "mover" to move a field to another tag. The field is deleted from the original tag and added to the new tag. The change is made in all occurrences of the original tag. The new tag can be created if it does not exist. The value of the field is not changed, but only moved to the new tag.
20260618 rogercgui Fix variable $hasta in "search" mode, which was not being updated with the total number of records retrieved, causing the "Next record" option to not appear when there were more than 100 records to process.
*/

	session_start();
if (!isset($_SESSION["permiso"])) {
	header("Location: ../common/error_page.php");
}
include("../common/get_post.php");

//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";//die;
include("../config.php");
include("../lang/soporte.php");
include("../lang/admin.php");
set_time_limit(0);
include("../common/header.php");
$backtoscript = "../dataentry/c_global.php"; // The default return script

/* ===================================================== */
function CambiarCampo($Mfn, $ValorCapturado, $Tag)
{
	global $xWxis, $db_path, $Wxis, $arrHttp, $wxisUrl, $actparfolder;
	$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Mfn=$Mfn&count=1";
	$query .= "&login=" . $arrHttp["login"] . "&ValorCapturado=" . urlencode($ValorCapturado);
	$contenido = "";
	$IsisScript = $xWxis . "actualizar_cg.xis";
	include("../common/wxis_llamar.php");
	foreach ($contenido as $value) echo "$value";
	$nfilas = 0;
}

function EliminarListaCampos()
{
	global $arrHttp, $ValorCapturado, $Cambiado;
	$tag = explode(" ", $arrHttp["listdel"]);
	$proc = "";
	foreach ($tag as $value) {
		$value = trim($value);
		if ($value != "") {
			while (strlen($value) < 3) $value = "0" . $value;
			if ($proc == "")
				$proc = 'd' . $value;
			else
				$proc .= "\nd$value";
		}
	}
	$ValorCapturado = $proc;
	$Cambiado = "N";
}

function EjecutarCambio($cont, $Anterior, $Tag, $Actual)
{

	global $arrHttp, $ValorCapturado, $Cambiado;
	switch ($arrHttp["tipoc"]) {
		case "agregar":
		case "agregarocc":
			$Cambiado = "S";
			if ($Anterior != "") {
				if ($arrHttp["tipoa"] == "cadena") {
					if (stripos($Actual, $Anterior) === false) {
					} else {
						$Cambiado = "S";
					}
				} else {
					if (strtoupper($Actual) == strtoupper($Anterior)) {
						$Cambiado = "S";
					}
				}
			}
			if ($Cambiado == "S") {
				$ValorCapturado .= "a" . $Tag . "×" . $arrHttp["nuevo"] . "×\n";
			}
			break;
		case "eliminar":
			if ($Anterior != "") {
				if ($arrHttp["tipoa"] == "cadena") {
					if (stripos($Actual, $Anterior) === false) {
					} else {
						$Cambiado = "S";
					}
				} else {
					if (strtoupper($Actual) == strtoupper($Anterior)) {
						$Cambiado = "S";
					}
				}
			} else {
				$Cambiado = "S";
			}
			break;
		case "eliminarocc":
			if ($Anterior != "") {
				if ($arrHttp["tipoa"] == "cadena") {
					if (stripos($Actual, $Anterior) === false) {
					} else {
						$Cambiado = "S";
					}
				} else {
					if (strtoupper($Actual) == strtoupper($Anterior)) {
						$Cambiado = "S";
					}
				}
			} else {
				$Cambiado = "S";
			}
			break;
		case "modificar":
		case "modificarocc":
			if ($arrHttp["tipoa"] == "cadena") {
				if (stripos($Actual, $Anterior) === false) {
				} else {
					$NuevoValor = str_replace($Anterior, $arrHttp["nuevo"], $Actual);
					$ValorCapturado .= "a" . $Tag . "×" . $NuevoValor . "×\n";
					$Cambiado = "S";
				}
			} else {
				if ($Anterior == "") {
					$ValorCapturado .= "a" . $Tag . "×" . $arrHttp["nuevo"] . "×\n";
					$Cambiado = "S";
				} else {
					if (strcasecmp(trim($Actual), trim($Anterior)) == 0) {
						$ValorCapturado .= "a" . $Tag . "×" . $arrHttp["nuevo"] . "×\n";
						$Cambiado = "S";
					}
				}
			}
			break;
		case "dividir":
			$ix = stripos($Actual, $arrHttp["separar"]);
			if ($ix === false) {
			} else {
				$campo_ad = trim(substr($Actual, 0, $ix));
				$campo_dd = trim(substr($Actual, $ix + strlen($arrHttp["separar"])));
				if (isset($arrHttp["posicion"])) {
					switch ($arrHttp["posicion"]) {
						case "antes":
							$amover = $campo_ad;
							$resto = $campo_dd;
							break;
						case "despues":
							$amover = $campo_dd;
							$resto = $campo_ad;
							break;
					}
				}
				if ($resto != "") {
					$ValorCapturado .= "a" . $Tag . "×" . $resto . "×\n";
					$Cambiado = "S";
				}
				if ($amover != "") {
					if (isset($arrHttp["nuevotag"])) {
						$T = explode('|', $arrHttp["nuevotag"]);
						$Tag_n = $T[0];
						if ($Tag_n != "")
							$ValorCapturado .= "a" . $Tag_n . "×" . $amover . "×\n";
						$Cambiado = "S";
					}
				}
			}
			break;
		case "mover":
			$T = explode('|', $arrHttp["nuevotag"]);
			$Tag_n = $T[0];
			$act = explode('$$', $Actual);
			var_dump($act);
			$ValorCapturado .= "a" . $Tag_n . "×" . $Actual . "×\n";
			$Cambiado = "S";
	}
}
/* ===================================================== */


include("leer_fdt.php");

//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
global  $arrHttp, $xWxis;

// ==================================================================================================
// INICIO DEL PROGRAMA
// ==================================================================================================

//foreach ($arrHttp as $val=>$value) echo "$val=$value<br>";
?>

<body>
	<script>
		w = 135
		h = 135

		var xleft = (screen.width / 2) - (w / 2);
		var xtop = (screen.height / 2) - (h / 2);
		waitwin = window.open("../common/wait.html", " ", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=" + w + ", height=" + h + ", top=" + xtop + ", left=" + xleft)
		waitwin.focus()

		function Presentar(Mfn) {
			url = "leer_all.php?base=<?php echo $arrHttp["base"] ?>&cipar=<?php echo $arrHttp["base"] ?>.par&Mfn=" + Mfn + "&count=1"
			msgwin = window.open(url, "SEE", "width=400,height=400,resizable,scrollbars")
			msgwin.focus()
		}
	</script>
	<div class="sectionInfo">
		<div class="breadcrumb">
			<?php echo $msgstr["cg_titulo"] . ": " . $arrHttp["base"] ?>
		</div>
		<div class="actions">
			<?php include "../common/inc_back.php"; ?>
		</div>
		<div class="spacer">&#160;</div>
	</div>
	<?php $ayuda = "cglobal.html";
	include "../common/inc_div-helper.php"; ?>
	<div class=middle form>
		<div class=formContent>

			<form name=tabla method=post action=c_global_ex.php>
				<div align=center>
					<?php
					$base = $arrHttp["base"];
					$cipar = $arrHttp["cipar"];
					if (isset($arrHttp["Expresion"]) and $arrHttp["Expresion"] != "")	$arrHttp["Opcion"] = "buscar";

					if (isset($arrHttp["to"])) $MaxMfn = $arrHttp["to"];

					//foreach ($arrHttp as $key => $value) echo "$key = $value <br>";
					// se lee el archivo mm.fdt
					$arrHttp["login"] = $_SESSION["login"];
					$login = $arrHttp["login"];
					if ($arrHttp["login"] == "") {
						echo $msgstr["menu_noau"];
						die;
					}
					if (!isset($arrHttp["nuevo"])) {
						$arrHttp["nuevo"] = "";
					}
					$Fdt = LeerFdt($base);
					foreach ($Fdt as $tag => $linea) {
						$Titulos[$tag] = trim(substr($linea, 3, 34));
					}

					//echo $arrHttp["anterior"];
					if (isset($arrHttp["actual"])) $ValorAnterior = explode("\r", $arrHttp["actual"]);
					if (isset($arrHttp["global_C"])) {
						$T = explode('|', $arrHttp["global_C"]);
						$tit = $Titulos[$T[0]];
						$tx = explode('|', $tit);
					?>
						<h3><?php echo $msgstr["cg_titulo"]; ?>
							&nbsp;&nbsp;&rarr;&nbsp;&nbsp;(<?php echo $T[0] ?>)&nbsp; <?php echo $tx[1]; ?></h3>
					<?php
						$Tag = $T[0];
						while (strlen($Tag) < 3) $Tag = "0" . $Tag;
					}
					switch ($arrHttp["tipoc"]) {
						case "agregar":
							$msg_accion = $msgstr["cg_add"];
							break;
						case "agregarocc":
							$msg_accion = $msgstr["cg_addocc"];
							break;
						case "modificar":
							$msg_accion = $msgstr["cg_modify"];
							break;
						case "modificarocc":
							$msg_accion = $msgstr["cg_modifyocc"];
							break;
						case "dividir":
							$msg_accion = $msgstr["cg_split"];
							break;
						case "mover":
							$msg_accion = $msgstr["cg_move"];
							break;
						case "eliminar":
							$msg_accion = $msgstr["cg_delete"];
							break;
						case "eliminarocc":
							$msg_accion = $msgstr["cg_deleteocc"];
							break;
					}
					if (isset($arrHttp["tipoa"])) {
						$msg_accion .= ". " . $msgstr["cg_scope"] . ": ";
						switch ($arrHttp["tipoa"]) {
							case "frase":
								$msg_accion .= $msgstr["cg_field"];
								break;
							case "cadena":
								$msg_accion .= $msgstr["cg_part"];
								break;
						}
					}
					?>


					<div style='width:700px;border-style:solid;border-width:1px;text-align:center'>
						<strong><?php echo $msg_accion; ?></strong><br>
						<?php
						if (isset($ValorAnterior)) {
							echo "<strong>" . $msgstr["cg_locate"] . ": </strong>";
							if ($arrHttp["tipoc"] != "dividir") {
								foreach ($ValorAnterior as $value) echo stripslashes($value) . "<br>";
							}
						}
						?>
						<strong><?php echo $msgstr["g_newval"] . ": " ?></strong>
						<font color=blue><?php echo stripslashes($arrHttp["nuevo"]) ?></font>
					</div> <!-- end of border div-->

					<table bgcolor=#cccccc cellspacing=2 border=0 cellpadding=5>
						<tr>
							<th style='text-align:center'>MFN</th>
							<th></th>
							<th></th>
						</tr>
						<?php
						if (isset($T[0]))
							$Formato = "(V" . $T[0] . '+|$$|)';
						else
							$Formato = "";

						$IxMfn = 0;
						// FIX PHP 8: Declare variables before use to avoid "Undefined variable" and "null given" errors
						$arr_mfn = array();
						$hasta = 0;
						$Mfn = 0;

						if (!isset($arrHttp["from"])) $arrHttp["from"] = 1;
						if (!isset($arrHttp["count"])) $arrHttp["count"] = 100;

						if ($arrHttp["Opcion"] == "rango") {						//se construye el rango de Mfn's a procesar
							$tope = $arrHttp["from"] + $arrHttp["count"] - 1;
							if ($tope >= $arrHttp["to"]) $tope = $arrHttp["to"];
							for ($ix = $arrHttp["from"]; $ix <= $tope; $ix++) {
								$arr_mfn[$ix] = $ix;
							}
							$hasta = $arrHttp["to"];
							$ixMfn = 0;
						} else {
							$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"];
							$query .= "&Formato=mfn/&Expresion=" . urlencode(stripslashes($arrHttp["Expresion"])) . "&Opcion=" . $arrHttp["Opcion"];
							$IsisScript = $xWxis . "act_tabla.xis";
							include("../common/wxis_llamar.php");
							$ix = 0;
							foreach ($contenido as $value) {
								if (trim($value) != "") {
									$ix++;
									$val = explode('|', $value);
									$arr_mfn[$ix] = $val[1];
								}
							}
							$arrHttp["from"] = 1;
							$tope = 100;
							$hasta = $ix; // FIX PHP 8: In "search" mode, the variable holds the total number of records retrieved
						}


						foreach ($arr_mfn as $Mfn) {
							$IxMfn = $IxMfn + 1;
							$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Mfn=$Mfn&count=1";
							$query .= "&Formato=" . urlencode($Formato) . "&Opcion=rango";
							$contenido = "";
							$IsisScript = $xWxis . "act_tabla.xis";
							include("../common/wxis_llamar.php");
							$nfilas = 0;
							$ValorCapturado = "";
							if ($arrHttp["tipoc"] != "agregarocc" and !isset($arrHttp["listdel"]))
								$ValorCapturado = "d" . $Tag . "\n";
							$Actualizar = "";
							foreach ($contenido as $linea) {
								if (trim($linea) == "") continue;
								$xcampos = explode('|', $linea);
								$linea = $xcampos[0];
								$posicion = trim(substr($linea, 11));
								$ipos = strpos($posicion, '$$');
								$desde = substr($posicion, 0, $ipos);
								$hasta = substr($posicion, $ipos + 2);
								if ($arrHttp["Opcion"] == "rango")
									if (isset($arrHttp["to"])) $hasta = $arrHttp["to"];
								$arrHttp["from"] = $desde + 1;
								$desde = $desde + 1;
								$Nreg = $xcampos[1];
								$contenido_c = explode('$$', $xcampos[2]);
								if ($arrHttp["Opcion"] == "rango") {
									$seq = "";
								} else {
									$seq = "($Mfn)";
									//	$Nreg=1;
								}
								if ($arrHttp["tipoc"] == "mover") {
									$ValorCapturado = "d" . $Tag . "\n";
									$T = explode('|', $arrHttp["nuevotag"]);
									$Tag_n = $T[0];
									$Actualizar = "";
									foreach ($contenido_c as $cont) {
										if (trim($cont) != "") {
											$ValorCapturado .= "a" . $Tag_n . "×" . $cont . "×\n";
											$Actualizar = "S";
										}
									}
						?>
									<tr style='background-color:white;'>
										<td style='text-align:right'><?php echo $seq; ?> <a href=javascript:Presentar(<?php echo $Mfn; ?>)><?php echo $Nreg ?></a></td>
										<td><?php echo $cont ?></td>
										<td>
											<?php
											if ($Actualizar == "S") {
												echo "OK!!!";
												CambiarCampo($Nreg, $ValorCapturado, $Tag);
											}
											echo "</td></tr>";
											continue;
										}
										foreach ($contenido_c as $cont) {
											$Cambiado = "";
											if (isset($ValorAnterior)) {
												$verifica = "";
												foreach ($ValorAnterior as $Anterior) {
													//$Anterior=trim($Anterior);
													if (
														strtoupper($cont) == strtoupper($Anterior) or
														(isset($arrHttp["tipoa"]) and ($arrHttp["tipoa"] == "cadena" and stripos($cont, $Anterior) !== false))
													) {
														$verifica = "S";
														EjecutarCambio($Mfn, $Anterior, $Tag, $cont);
														if ($Cambiado == "") {
															if ($cont != "")
																$ValorCapturado .= "a" . $Tag . "²" . $cont . "²\n";
														} else {
											?>
									<tr style='background-color:white;'>
										<td style='text-align:right'><?php echo $seq; ?> <a href=javascript:Presentar(<?php echo $Mfn; ?>)><?php echo $Nreg ?></a></td>
										<td>
											<font size=2><?php echo $cont ?></font>
										</td>
										<td><b>OK!!!</b></td>
									</tr>
							<?php
															$Actualizar = "S";
															break;
														}
													}
													if ($verifica == "")	$ValorCapturado .= "a" . $Tag . "²" . $cont . "²\n";
												}
											} else {
												$Cambiado = "";
												if (isset($arrHttp["lisdel"]))
													$Tag = "";
												if (!isset($Tag)) $Tag = "";
												EjecutarCambio($Mfn, "", $Tag, $cont);
												if ($Cambiado == "") {
													$ValorCapturado .= "a" . $Tag . "²" . $cont . "²\n";
												} else {
							?>
							<tr style='background-color:white;'>
								<td style='text-align:right'><?php echo $seq; ?> <a href=javascript:Presentar(<?php echo $Mfn; ?>)><?php echo $Nreg ?></a></td>
								<td>
									<font size=2><?php echo $cont ?></font>
								</td>
								<td><b>OK!!!</b></td>
							</tr>
					<?php
													$Actualizar = "S";
													break;
												}
												echo "</td>\n";
											}
										}
										if ($Actualizar == "S") {
											CambiarCampo($Nreg, $ValorCapturado, $Tag);
										} else {
					?>
					<tr style='background-color:white;'>
						<td style='text-align:right'><?php echo $seq; ?> <a href=javascript:Presentar(<?php echo $Mfn; ?>)><?php echo $Nreg ?></a></td>
						<td style='color:blue'><?php echo $msgstr["cg_nochange"]; ?></td>
						<td></td>
					</tr>
		<?php
										}
										//if ($arrHttp["Opcion"]=='buscar') $tope=$hasta;
										flush();
										ob_flush();
									}
								}
		?>
					</table>
					<script>
						waitwin.close()
					</script>
					<?php
					switch ($arrHttp["Opcion"]) {
						case "rango":
							$arrHttp["from"] = $Mfn + 1;
							break;
						case "buscar":
							echo "<br><div style=\"width=700;border-style:solid;border-width:1px \"><font size=1 face=arial>Expresion: " . stripslashes($arrHttp["Expresion"]) . "<br>Recuperados en la búsqueda : $hasta registros</div>";
							break;
					}
					?>
					<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
					<input type=hidden name=cipar value=<?php echo $arrHttp["cipar"] ?>>

					<input type=hidden name=actual value="<?php if (isset($arrHttp["actual"])) echo stripslashes($arrHttp["actual"]) ?>">
					<input type=hidden name=nuevo value="<?php if (isset($arrHttp["nuevo"])) echo stripslashes($arrHttp["nuevo"]) ?>">
					<input type=hidden name=to value="<?php if (isset($arrHttp["to"])) echo stripslashes($arrHttp["to"]) ?>">
					<input type=hidden name=MaxMfn value="<?php if (isset($arrHttp["MaxMfn"])) echo $arrHttp["MaxMfn"] ?>">

					<input type=hidden name=Expresion value="<?php if (isset($arrHttp["Expresion"])) echo stripslashes($arrHttp["Expresion"]) ?>">
					<input type=hidden name=Opcion value="<?php echo $arrHttp["Opcion"] ?>">
					<input type=hidden name=global_C value="<?php if (isset($arrHttp["global_C"])) echo $arrHttp["global_C"] ?>">
					<input type=hidden name=tipoc value="<?php echo $arrHttp["tipoc"] ?>">
					<input type=hidden name=tipoa value="<?php echo $arrHttp["tipoa"] ?>">
					<input type=hidden name=posicion value="<?php if (isset($arrHttp["posicion"])) echo $arrHttp["posicion"] ?>">
					<input type=hidden name=nuevotag value="<?php if (isset($arrHttp["nuevotag"])) echo $arrHttp["nuevotag"] ?>">
					<input type=hidden name=separar value="<?php if (isset($arrHttp["separar"])) echo $arrHttp["separar"] ?>">
					<input type=hidden name=listdel value="<?php if (isset($arrHttp["listdel"])) echo $arrHttp["listdel"] ?>">
					<?php
					if ($arrHttp["Opcion"] == 'buscar') {
						$arrHttp["from"] = 1;
						$Mfn = 1;
					}
					if ($Mfn < $hasta) {
						$IxMfn = $IxMfn + 1;
						echo "<p><font face=arial size=1>
	Próximo registro:<input type=text size=5 name=from value='" . $arrHttp["from"] . "'>,
	procesar <input type=text size=5 name=count value=" . $arrHttp["count"] . "> registros más
	<br><input type=submit value=\"" . $msgstr["continuar"] . "\"><br>";
					} else {
					}
					?>
				</div>
			</form>
			<form name=menu method=post action=c_global.php>
				<input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
				<input type=hidden name=cipar value=<?php echo $arrHttp["cipar"] ?>>
			</form>
		</div>
	</div>
	<?php
	include("../common/footer.php")
	?>