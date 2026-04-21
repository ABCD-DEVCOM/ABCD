<?php
/* Modifications
20210721 fho4abcd default subfield-codes for OD from "od to "do"" + lineends
20210906 rogercgui new look
20211013 fho4abcd Pick list "Table":do not echo content,no index failure if value (of key|value) is missing
20211013 fho4abcd No index failure if a "Date" is the last of the FDT
20211113 fho4abcd Remove phantom </center>, remove phantom links to awesome
20211118 fho4abcd improvement for tags with/without leading zeros
20211118 rogercgui edited line     $it="password\""; $it="text\" onfocus=blur()";
20211225 include field type number - $tipo=N
20220207 html improvements
20220214 fho4abcd Replace link to dirs_explorer by javascript+ some html improvements
20220309 rogercgui Included verification for variable $t17
20240129 fho4abcd Show correct number of OD entries. Time in 24 hour format. Improve calendar function. Typo's, formatting
20240517 fho4abcd For PHP8: Empty parameters compare different with value 0: also compare with ""
20250213 fho4abcd Replaced links by buttons, adde translation
202503xx rogercgui Improves the Autoincrement field by displaying the number that will be previously saved in the record.
20250330 fho4abcd Encode quotes in variable $linea01 for $tipo_e=E to get correct value for <input name=eti... value=..>
20250901 fho4abcd Edit button for edit picklists
20251211 fho4abcd Resolve some html issue's
20251223 fho4abcd Formatted code +HTML5
*/
require_once("combo_inc.php");
require_once("../common/inc_calendar.php");
require_once("editor/loader.php");

//include for translate
include("../lang/dbadmin.php");
include("../lang/admin.php");
include("../lang/prestamo.php");
include("../lang/opac.php");


function AsociarVinculo($linea)
{
	$ix = strpos($linea, "<");
	if ($ix > 0) {
		echo "<br>" . substr($linea, $ix);
	}
}

function Calendario($campo, $type_de, $iso_tag, $Etq)
{
	CalendarHelper::render($campo, $type_de, $iso_tag, $Etq);
}

function DibujarHtmlArea($tag, $linea, $numl, $tipoH)
{
	HtmlAreaRenderer::render($tag, $linea, $numl, $tipoH);
}

function SubCampo($campo, $ksc)
{
	return SubfieldHelper::extract($campo, $ksc);
}

function DibujarTextRepetible($tag, $fondocelda, $field_t)
{
	RepeatableRenderer::render($tag, $fondocelda, $field_t);
}

function ColocarSelect($tag, $subc, $nombrec, $lista_opciones, $campo, $picklist)
{
	SelectRenderer::renderColocarSelect($tag, $subc, $nombrec, $lista_opciones, $campo, $picklist);
}

function DibujarTabla($filas, $tag, $fondocelda, $field_t)
{
	TableRenderer::render($filas, $tag, $fondocelda, $field_t);
}

function DecodificaSubCampos($campo, $numsubc, $subc, $delimsc)
{
	return SubfieldHelper::decode($campo, $numsubc, $subc, $delimsc);
}

function DibujarCheck($filas, $fondocelda, $valor, $tag, $opciones, $tope, $tipo, $subc)
{
	CheckRenderer::render($filas, $fondocelda, $valor, $tag, $opciones, $tope, $tipo, $subc);
}

function DibujarSelect($linea, $fondocelda, $valor, $tag, $ksc, $opciones, $rep, $subc)
{
	SelectRenderer::renderDibujarSelect($linea, $fondocelda, $valor, $tag, $ksc, $opciones, $rep, $subc);
}

function TextBox($linea, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delimrep, $ayuda)
{
	TextRenderer::render($linea, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delimrep, $ayuda);
}

function PrepararFormato()
{
	global $msgstr, $vars, $ver, $fondocelda, $valortag, $ixicampo, $base, $cipar, $arrHttp, $FdtHtml, $Html_ingreso, $tagisis;
	global $db_path, $lang_db, $default_values;
	global $config_date_format, $base_fdt, $is_marc, $reintentar, $tag_tipol, $tag_nivel_r, $OpcionDeEntrada, $tag;
	global $term_prefix, $term_tag, $refer_tag;
	$tesaurus = "N";
	if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . "tesaurus.rel")) {
		include("../tesaurus/leer_relaciones.php");
		$tesaurus = "Y";
	}
	// The file typeofrecords.tab is read to determine the fields where the literature type and record level
	// are located for the assignment of field 08
	$tag_tipol = "";
	$tag_nivelr = "";
	if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/typeofrecord.tab")) {
		$f = fopen($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/typeofrecord.tab", 'r');
		$tor = fgets($f);
		$tor = explode(' ', trim($tor));
		if (isset($tor[0])) $tag_tipol = $tor[0];
		if (isset($tor[1])) $tag_nivelr = $tor[1];
	}
	// The online help file is read
	if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/help.tab")) {
		$hlp = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/help.tab");
		foreach ($hlp as $value) {
			if (trim($value) != "") {
				$vhlp = explode('=', $value, 2);
				$hlp_tip[$vhlp[0]] = $vhlp[1];
			}
		}
	}
	if ($ver == "S") $ver = true;
	$cipar = $arrHttp["cipar"];
	$ixTab = -1;
	$ixicampo = 0;
	$first_time = "Y";
	$is_marc = "";
	$cargar_texto = "";
	$secciones = array();
	$numero_secciones = 0;
	$wrapperopen = false;
	for ($ivars = 0; $ivars < count($vars); $ivars++) {
		$linea = $vars[$ivars];
		$t = explode('|', $linea);
		if ($t[0] == "L"  or $t[0] == "H")
			$numero_secciones = $numero_secciones + 1;
	}
	$obligatorio = "N";
	for ($ivars = 0; $ivars < count($vars); $ivars++) {
		$help_url = "";
		$linea = $vars[$ivars];
		$t = explode('|', $linea);
		if (isset($t[2])) $titulo = $t[2];
		if (isset($t[9])) $len = $t[9];
		if (isset($t[4])) $rep = $t[4];
		if (isset($t[1])) $tag = $t[1];
		//    if (isset($rels[$tag]["tag"])) continue;
		$delrep = "";
		if (!isset($t[13])) $t[13] = "";
		$fe = urlencode($t[13]);
		if (isset($t[17])) {
			if (trim($t[17]) != "") $help_url = $t[17];
		}
		$tipo_e = "";
		$entryType = "";

		// --- INÍCIO DA INTERCETAÇÃO DA ABA (TAB) ---
		if ($t[0] == "TAB") {
			if (!isset($tabopen)) $tabopen = false;

			// Se houver uma aba anterior aberta, temos que fechá-la
			if ($tabopen) {
				if ($wrapperopen) {
					echo "</div></div>\n"; // Fecha o cabeçalho (H) que ficou aberto
					$wrapperopen = false;
				}
				echo "</div>\n"; // Fecha a div da aba (abcd-tab-pane)
			}

			$titulo_aba = trim($t[2]);
			// Abre a nova aba invisível
			echo "<div class='abcd-tab-pane' data-tab-title='" . htmlspecialchars($titulo_aba, ENT_QUOTES) . "' style='display:none;'>\n";
			$tabopen = true;

			continue; // Pula para a próxima linha da FDT (não desenha a tabela do campo)
		}
		// --- FIM DA INTERCETAÇÃO ---

		if (!$ver or $ver and isset($valortag[$t[1]]) or ($t[0] == "H" or $t[0] == "L")) {
			if (($t[0] == "H" or $t[0] == "L" or $ivars == 0)) {
				if ($first_time == "Y") {
					$first_time = "N";
					if ($ivars == 0) {
						$display = "";
					} else {
						$display = "display:none";
					}
				} else {
					$display = "display:none";
					if (isset($titulo_ant) and $titulo_ant != "*" and $numero_secciones > 0) {
						echo "\n<a tabindex='-1' href=\"javascript:switchMenu('myvar_$ixant');\" style=\"text-decoration:none \">";
						if (substr($titulo_ant, 0, 1) != "<") {
							echo "<i class=\"far fa-minus-square\" style=\"vertical-align:middle\"></i> <b>" . $msgstr["cerrar"] . "</b> $titulo_ant</a> ";
						} else {
							echo $msgstr["cerrar"];
						}
					}
				}
				if ($t[0] == "L") $display = "";
				if ($t[0] == "H" or $t[0] == "L") {
					if ($ivars > 0) {
						if ($t[0] == "H") {
							$secciones["myvar_$ivars"] = "myvar_$ivars";
							$titulo_ant = $titulo;
						} else {
							$titulo_ant = "*";
						}
					}
					if ($wrapperopen == true) echo "</div></div>"; // fecha wrapper anterior

					// Wrapper principal com ID
					echo "\n<div id='wrapper_$ivars'>";
					$wrapperopen = true;

					// O Link do cabeçalho
					if ($t[0] == "H" and $numero_secciones > 0)
						// Chamada simples. O JS resolve o ícone sozinho.
						echo "\n<a class=\"header-fdt\" href=\"javascript:switchMenu('myvar_$ivars');\">";
					else
						echo "\n<a class=\"header-fdt disabled\">";

					// O Ícone e o Título
					if (substr($titulo, 0, 1) != "<" and $numero_secciones > 0)
						// Importante: classes far fa-plus-square para o JS encontrar
						echo "<i class=\"far fa-plus-square\" style=\"vertical-align:middle\"></i> <b>$titulo</b>";
					else
						echo $titulo;

					echo "</a>";

					if (isset($t[17])) {
						if (trim($t[17]) != "") {
							echo "<div style='padding:5px 15px; color:#666; font-size:0.9em'>" . $hlp_tip[$t[17]] . "</div>";
						}
					}

					// A div de conteúdo (alvo do switchMenu)
					echo "\n<div id=\"myvar_$ivars\" style=\"$display;\" class=\"group-fields\">";
					$ixant = $ivars;
				}
				if ($t[0] == "XL") {
					$titulo_ant = $titulo;
					echo "\n<div id=\"wrapper\">";
					echo "\n<b>" . $titulo . "</b>\n";
					$titulo_ant = "";
				}
			}
			echo "\n<table  class=\"table-fdt\">\n";
			if (isset($t[14])) if (substr($t[13], 0, 1) != "@") $fe .= urlencode('`$$$`' . $t[14]);
			if ($t[0] == "H") {
				$ixTab = $ixTab + 1;
				$fondocelda = "";
				$a = $t[2];
				$pos = strpos($a, "[");
				if ($pos === false) {
				} else {
					$a = substr($a, 0, $pos);
				}
				$a = trim($a);
			} else {
				$tipo = $t[0];
				//This is for the changes that were made to the fdt regarding the field type and the input type
				switch ($t[0]) {
					case "OD":
						$t[0] = "F";
						$t[7] = "OD";
						break;
					case "OC":
						$t[0] = "F";
						$t[7] = "OC";
						break;
					case "ISO":
						$t[0] = "F";
						$t[7] = "ISO";
						break;
					case "DC":
						$t[0] = "F";
						$t[7] = "DC";
						break;
					case "AI":
						$t[0] = "F";
						$t[7] = "AI";
				}
				if (isset($t[1])) $tag = $t[1];
				if (isset($t[5])) $subc = rtrim($t[5]);
				if (isset($t[6])) $edit_subc = rtrim($t[6]);
				if (isset($t[5])) $nsc = strlen(rtrim($t[5]));
				$ksc = "";
				if (isset($t[2])) $titulo = $t[2];
				if (isset($t[7])) $entryType = $t[7];
				$tipo_e = "";
				if (isset($t[7])) if ($t[7] == "TB") $tipo_e = "TB";
				if ($tipo == "L") {
					//$lin01=$titulo;
					//if ($lin01=="") $lin01="&nbsp;";
					//    if ($t[7]!="I") echo "\n<tr><td width=10>&nbsp;</td><td width=10 align=right> &nbsp; </td><td colspan=2  ><b>".$lin01."</b></td></tr>\n";
				} else {
					if (!isset($valortag[$tag]))
						$valortag[$tag] = "";
					else
						$valortag[$tag] = str_replace('"', "&quot;", $valortag[$tag]);
					$ayuda = "";
					if (isset($valortag[$tag]) and $t[0] != "H" and $t[0] != "L") {
						if ($ver && $valortag[$tag] || !$ver) {
							if ($t[7] != "I") {
								echo "<tr><td class='table-fdt-one'><span class=\"badge\">";
								echo $tag . $ksc;
							} else {
								echo "&nbsp;";
							}
							if (isset($t[19]) and $t[19] == 1) {
								echo " <span style='color:red;font-size:150%;'>*</span>";
								$obligatorio = "S";
							}
							if ($t[7] != "I") echo "</span></td>\n";
							$subc = rtrim($t[5]);
							if (substr($subc, 0, 1) == "-") $subc = "_" . substr($subc, 1);
							if (substr($subc, 0, 1) == " ") $subc = "+" . substr($subc, 1);
							$delimsubc = rtrim($t[6]);
							if (substr($delimsubc, 0, 1) == " ") $delimsubc = "+" . substr($delimsubc, 1);
							if (substr($subc, 0, 1) == " ") $subc = "+" + substr($subc, 1);
							$a = trim($t[12]);
							$c = "";
							$separa = "";
							$autoridades = "";
							if ($t[10] == "D") {
								$autoridades = $t[11];
								if ($autoridades == "") $autoridades = $arrHttp["base"];
							}
							$Repetible = "";
							if ($t[4] == 1) $Repetible = "R";
							$postings = 1;
							if (!$ver) {
								echo "<td class='table-fdt-two'>";
								if ($t[7] != "COMBO" and $t[7] != "COMBORO") {
									if ($a != "" or $t[10] == "T") {  //es una lista de autoridades o un tesauro
										if ($t[7] != "I" and $t[7] != "TB" and $t[10] != "T" and ($t[10] != "")) echo "<a  class=\"bt-fdt\" href='javascript:AbrirIndiceAlfabetico(document.forma1.tag$tag,\"$a\",\"$c\",\"$separa\",\"$autoridades\",\"$autoridades.par\",\"tag$tag\",\"$postings\",\"$Repetible\",\"" . urlencode($fe) . "\")'><i class=\"fas fa-search\"></i></a>";
										if ($t[7] != "I" and $t[10] == "T") {
											echo "<a class=\"bt-fdt\" href='javascript:AbrirTesauro(\"tag$tag\",\"" . $t[11] . "\")'><i class=\"fas fa-cubes\"></i></a>";
											if (trim($a) != "")
												$autoridades = $arrHttp["base"];
											if ($t[12] != "") {
												echo "<br><a class=\"bt-fdt\" ";
												echo "href='javascript:AbrirIndiceAlfabetico(document.forma1.tag$tag,\"$a\",\"$c\",\"$separa\",\"$autoridades\",\"$autoridades.par\",\"tag$tag\",\"$postings\",\"$Repetible\",\"" . urlencode($fe) . "\")'>";
												echo "<i class=\"fas fa-search\"></i></a>";
											}
										}
									} else {
										if ($t[7] != "I") echo "";
									}
								}
								if ($tipo == "T"  and $tipo_e != "TB") {
									if (!isset($arrHttp["wks_a"]))
										$wks_a = "";
									else
										$wks_a = $arrHttp["wks_a"];
									if ($t[7] != "I") echo "<a  class=\"bt-fdt\" href='javascript:Campos(document.forma1.tag$tag,$ixicampo,\"$fe\",\"$Repetible\",\"$help_url\",\"" . $wks_a . "\")'><i class=\"fas fa-plus\"></i></a>";
								} else {
									if ($tipo == "M") {
										$fe = "";
										if (isset($arrHttp["wks_a"])) {
											$xxwk = explode('|', $arrHttp["wks_a"]);
											if (isset($xxwk[4])) {
												$fe = $xxwk[4];
											} else {
												if (isset($arrHttp["wks"]))
													$fe = $arrHttp["wks"];
												else
													$fe = "";
											}
										}
										if ($t[7] != "I") {
											echo "<a class=\"bt-fdt\" href='javascript:CampoFijo(document.forma1.tag$tag,$ixicampo,\"$fe\",\"$base\",\"\",\"$help_url\",\"$tag_tipol\",\"$tag_nivelr\")'><i class=\"fas fa-plus\"></i></a>";
										}
									}
									if ($tipo == "LDR") {
										if (isset($arrHttp["wks"]))
											$fe = $arrHttp["wks"];
										else
											$fe = "";
									}
								}
								$help = "";
								if (isset($t[16])) $help = $t[16];
								if (isset($hlp_tip[$tag]))
									echo "<a class=\"tooltip\"><i class=\"far fa-life-ring\"></i><span> " . $hlp_tip[$tag] . "</span></a>";
								if ($help == 1 or $help_url != "") {
									if ($help_url == "") {
										if ($t[7] != "I") echo "<a tabindex='-1' class=\"bt-fdt bt-fdt-question\" href=javascript:Ayuda($tag)><i class=\"fas fa-question\"></i></a>";
									} else {
										if ($t[7] != "I") echo "<a tabindex='-1' class=\"bt-fdt bt-fdt-question\" href='javascript:msgh=window.open(\"$help_url\",\"help\",\"width=600,height=400\");msgh.focus()'><i class=\"fas fa-question\"></i></a>";
									}
								} else {
									if ($t[7] != "I") echo "";
								}
								echo "</td>\n";
							}
							$nsc = strlen(rtrim($t[5]));
							$ixicampo = $ixicampo + 1;
							if ($tipo != "T" and $tipo != "M" and $tipo != "AI") $tipo_e = $entryType;
							//if ($tipo=="AI") $tipo_e="AI";
							if ($tipo == "T") $tipo_e = "E";
							if ($tipo == "M") $tipo_e = "FF";
							if ($tipo == "LDR") $tipo_e = "LDR";
							if ($tipo == "M5") $tipo_e = "M5";
							if ($entryType == "TB") $tipo_e = "TB";
							//if ($entryType=="ISO") $tipo_e="ISO";
							//if ($entryType=="RO")  $tipo_e="RO";
							//echo "<input type=hidden name=idtag value=$tag>";
							if (trim($tipo_e) == "") $tipo_e = "X";
							if (isset($t[15]))
								if (!$ver and $valortag[$tag] == "") $valortag[$tag] = $t[15];
							switch ($tipo_e) { // Switch on mix of field type/field type
								case "M5":    // Date(MARC 005) DATE OF LAST UPDATE (FIELD 005 MARC)
									$is_marc = "S";
									if (!isset($default_values) or $default_values != "S") {    //CHECK IF EDITING DEFAULT VALUES
										$campo = $valortag[$tag];
										echo "\n<td class='table-fdt-three'>";
										echo trim($titulo) . "</td>\n";
										echo "\n<td class='table-fdt-four input-fdt'>\n";
										if (!$ver) {
											$campo = date("YmdHi.s");
											echo "<input type=hidden name=tag$tag id=tag$tag  value=\"" . $campo . "\" >\n";
										}
										echo "<small>" . nl2br($campo) . "</small>";
										echo "</td><tr>\n";
									}
									break;
								case "OC":    // Operator (created)
									if (!isset($default_values) or $default_values != "S") {    //CHECK IF EDITING DEFAULT VALUES
										$campo = $valortag[$tag];
										echo "\n<td class='table-fdt-three'>";
										echo trim($titulo) . "</td>\n";
										echo "\n<td class='table-fdt-four input-fdt'>\n";
										if (!$ver) {
											if (trim($campo) == "")
												if ($arrHttp["Mfn"] == 'New') $campo = $_SESSION["login"];
											echo "<input tabindex='0' type=text name=tag$tag id=tag$tag value=\"" . $campo . "\" size=20 onfocus=blur()>\n";
										}
										echo "</td><tr>\n";
									}
									break;
								case "DC":    // Date (created)
									if (!isset($default_values) or $default_values != "S") {    //CHECK IF EDITING DEFAULT VALUES
										$campo = $valortag[$tag];
										echo "\n<td class='table-fdt-three'>";
										echo trim($titulo) . "</td>\n";
										echo "\n<td class='table-fdt-four input-fdt'>\n";
										if (!$ver) {
											if ($campo == "")
												if ($arrHttp["Mfn"] == 'New') $campo = date("Ymd h:i:s");
											echo "<input tabindex='0' type=text name=tag$tag id=tag$tag  value=\"" . $campo . "\" size=20 onfocus=blur()>\n";
										}
										echo "</td></td><tr>\n";
									}
									break;
								case "OD":    // Operator and Date
									if ($arrHttp["Opcion"] != "valdef") {    //CHECK IF EDITING DEFAULT VALUES
										$campo = $valortag[$tag];
										$campo_out = "";
										echo "\n<td class='table-fdt-three'>";
										if (trim($t[5]) == "") $t[5] = "do";
										echo trim($titulo) . "</td>\n";
										echo "\n<td class='table-fdt-four input-fdt'>\n";
										//Keep only the number of occurrences specified in the column row of the fdt
										$ix = 0;
										$ccc = explode("\n", $campo);
										if ($t[8] == 0 || $t[8] == "") $t[8] = 10;
										// Reduce less in case of "ver"(==standard display) or "actualizar". Not in case of "editar".
										if ($OpcionDeEntrada == "ver" || $OpcionDeEntrada == "actualizar") $t[8]++;
										if (count($ccc) >= $t[8]) {
											$campo = "";
											$ix = (int)(count($ccc)) - (int)$t[8] + 1;
											for ($yx = $ix; $yx < count($ccc); $yx++) {
												if ($campo == "")
													$campo = $ccc[$yx];
												else
													$campo .= "\n" . $ccc[$yx];
											}
										}
										if ($arrHttp["Opcion"] == "crear") $campo = "";
										if (!$ver) {
											if ($reintentar != "S") {
												if ($campo != "") {
													$campo .= "\n";
												}
												// 24 hour format
												$campo .= "^" . substr($t[5], 0, 1) . date("Ymd H:i:s") . "^" . substr($t[5], 1, 1) . $_SESSION["login"];
											}
											echo "<input type=hidden name=tag$tag id=tag$tag value=\"" . $campo . "\" >\n";
										}
										echo "<table>";
										echo "<tr><td style='width:150px;'>" . $msgstr["it_d"] . "</td><td>" . $msgstr["dboper"] . "</td></tr>\n";
										$campo_out = explode("\n", $campo);
										foreach ($campo_out as $var => $value) {
											if ($value != "") {
												$val = explode('^', $value);
												echo "<tr><td>";
												if (isset($val[1]))  echo substr($val[1], 1);
												echo "</td><td>";
												if (isset($val[2]))  echo substr($val[2], 1);
												echo "</td></tr>";
											}
										}
										echo "</table>\n";
										echo "</td></tr>\n";
									}
									break;
								case "LDR": // MARC-Leader
									$is_marc = "S";
									$filas = array();
									$linea01 = $vars[$ivars];
									$ksc = 0;
									$ldr_tit = array();
									echo "<td>$titulo</td><td><table class='table-fdt' cellpadding=0 cellspacing=0>";
									for ($ixsc = 1; $ixsc <= 100; $ixsc++) {
										$ivars = $ivars + 1;
										$linea = $vars[$ivars];
										if (substr($linea, 0, 1) != "S") {    //para detectar el fin de la descripción del leader
											$ivars = $ivars - 1;
											$ixsc = 999;
										} else {
											$ksc = $ksc + 1;
											$filas[] = rtrim($linea);
											$ld = explode('|', $linea);
											$ldr_tit[$ksc] =  "<tr><td>" . $ld[2] . " (" . $ld[1] . ")</td>";
											//echo "<td align=center>".$ld[2]." (".$ld[1].")</td>";
										}
									}
									$ksc = 0;
									foreach ($filas as $linea) {
										$ksc = $ksc + 1;
										echo $ldr_tit[$ksc];
										$ld = explode("|", $linea);
										echo "<td>";
										$ttmsel = "";
										if ($ld[1] == 3006) {
											if (isset($arrHttp["wk_tipom_1"])) {
												$ttmsel = $arrHttp["wk_tipom_1"];
											}
											if ($ttmsel == "") if (isset($valortag[$ld[1]])) $ttmsel = $valortag[$ld[1]];
										} else {
											if (isset($valortag[$ld[1]])) $ttmsel = $valortag[$ld[1]];
										}
										$ldr_tag[$ksc] = $ld[1];
										echo "<select name=tag" . $ld[1] . " id=tag" . $ld[1] . ">\n";
										echo "<option value=\"\"></option>\n";
										$fpleader = array();
										if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $ld[11]))
											$fpleader = file($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . $ld[11]);
										else
											$fpleader = file($db_path . $arrHttp["base"] . "/def/" . $lang_db . "/" . $ld[11]);
										foreach ($fpleader as $value) {
											$value = trim($value);
											if ($value != "") {
												$v = explode("|", $value . "|||");
												$selected = "";
												if (trim($v[0]) == trim($ttmsel)) $selected = " selected";
												echo "<option value=" . $v[0] . "|" . $v[2] . " $selected>" . trim($v[1]) . "(" . $v[0] . ")</option>\n";
											}
										}
										echo  "</select>";
										if (isset($_SESSION["permiso"])) {
											if (
												isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or
												isset($_SESSION["permiso"][$base . "_CENTRAL_ALL"])  or
												isset($_SESSION["permiso"][$base . "_CENTRAL_ACTPICKLIST"])
											) {
												echo " <a class=\"bt-fdt\" href=\"javascript:AgregarPicklist('" . $ld[11] . "','tag" . $ld[1] . "','')\"><i class=\"fas fa-edit\" title='" . $msgstr["mod_picklist"] . "'></i></a>";
											}
											echo " <a class=\"bt-fdt\" href=\"javascript:RefrescarPicklist('" . $ld[11] . "','tag" . $ld[1] . "','')\"><i class=\"fas fa-redo\" title='" . $msgstr["reload_picklist"] . "' ></i></a> &nbsp; ";
										}
										echo "\n<input type=hidden name=eti$tag value=\"$linea01\">\n";
										echo "</td>\n";
									}
									echo "</table><br></td></tr>";
									break;
								case "B":    // External HTML
									if ($arrHttp["Mfn"] == "New") {
										echo "<td><h3><font color=red>you must load the full document as soon as this record is created, using the load icon in the record toolbar</font></h3>";
									} else {
										echo "\n<td width=150><a href=internal_html.php?base=" . $arrHttp["base"] . "&Mfn=" . $arrHttp["Mfn"] . "&tag=$tag target=_blank>$titulo</a>\n";
									}
									//echo "<!-- &nbsp; &nbsp;<a href=javascript:CopiarHtml(".$tag.",'B','".$arrHttp["Mfn"]."')>upload file</a>-->";
									echo "</td><td>";
									echo "</td></tr>";
									break;
								case "A":    //HTML area
									echo "<td class=\"table-fdt-three\">";
									echo "$titulo";
									echo "</td>\n";
									if (!$ver) {
										DibujarHtmlArea($tag, $vars[$ivars], $t[8], $tipo_e);
										$a = str_replace("'", "\"", $valortag[$tag]);
									} else {
										echo "<br><font class=td>" . $valortag[$tag];
									}
									echo "</tr>\n";
									break;
								case "D":    // Date
									$campo = $valortag[$tag];
									echo "\n<td class=\"table-fdt-three\">";
									echo trim($titulo) . "</td>\n";
									echo "\n<td>\n";
									if (!$ver) {
										$nextfieldexists = false;
										if (isset($vars[$ivars + 1])) {
											//there is another line in the fdt
											$nextfieldexists = true;
											$next_field = explode('|', $vars[$ivars + 1]);  //IF THE NEXT FIELD IS AN ISO FIELD CALL THE CONVERSION PROCEDURE
										}
										if ($nextfieldexists && trim($next_field[7]) == "ISO") {
											$date_tag = "tag$tag"; //NAME OF THE ACTUAL FIELD FOR GENERATING THE ISO DATE
											$iso_tag = "tag" . $next_field[1]; //NAME OF THE ISO FIELD
										} else {
											$curr_field = explode('|', $vars[$ivars]);
											if (trim($curr_field[0]) == "ISO") {
												$date_tag = "tag$tag"; //NAME OF THE ACTUAL FIELD FOR GENERATING THE ISO DATE
												$iso_tag = "tag" . $curr_field[1]; //NAME OF THE ISO FIELD
											} else {
												$date_tag = "";
												$iso_tag = "";
											}
										}
										//calendar attaches to existing form element
	?>
										<input tabindex="0" type="text" name="tag<?php echo $tag; ?>"
											id="tag<?php echo $tag; ?>_c"
											value="<?php echo $campo ?>"
											<?php if ($iso_tag != "") { ?>
											onChange='Javascript:DateToIso(this.value,document.forma1.<?php echo $iso_tag ?>)'
											<?php }; ?>>
										<a class="bt-fdt" id="f_tag<?php echo $tag; ?>">
											<i class="far fa-calendar-alt" title="Date selector"></i></a>
										<script>
											Calendar.setup({
												inputField: "tag<?php echo $tag ?>_c", // id of the input field
												ifFormat: "<?php $format = ConvertDateSpec($config_date_format);
															echo $format ?>",
												button: "f_tag<?php echo $tag ?>", // trigger for the calendar (button ID)
												align: '', // alignment (defaults to \"Bl\")
												singleClick: true
											});
										</script>
									<?php
									} else {
										echo $campo;
									}
									echo "</td></tr>\n";
									break;
								case "ISO":    // ISO date
									$campo = $valortag[$tag];
									echo "\n<td class=\"table-fdt-three\">";
									echo trim($titulo) . "</td>\n";
									if ($t[4] == 1) {    //SI ES REPETIBLE
										$field_t = $vars[$ivars];
										DibujarTextRepetible($tag, $fondocelda, $field_t);
										break;
									}
									echo "\n<td>\n";
									if (!$ver) {
										//calendar attaches to existing form element
									?>
										<input tabindex="0" type="text" name="tag<?php echo $tag; ?>"
											id="tag<?php echo $tag ?>_c"
											value="<?php echo $campo ?>"
											onChange='Javascript:DateToIso(this.value,document.forma1.tag<?php echo $tag ?>)'>
										<a class="bt-fdt" id="f_tag<?php echo $tag ?>">
											<i class="far fa-calendar-alt" title="Date selector"></i></a>
										<script>
											Calendar.setup({
												inputField: "tag<?php echo $tag; ?>_c", // id of the input field
												ifFormat: "<?php $format = ConvertDateSpec($config_date_format);
															echo $format ?>",
												button: "f_tag<?php echo $tag ?>", // trigger for the calendar (button ID)
												align: '', // alignment (defaults to \"Bl\")
												singleClick: true
											});
										</script>
<?php
									} else {
										echo $campo;
									}
									if ($Repetible == "R") {
										echo "<td>\n</table>";
										echo "<a href=javascript:addRow('" . $tag . "','')>" . $msgstr["add"] . "</a><br><br></td>";
									}
									echo "</td></tr>\n";
									break;
								case "FF":    //MARC Campo fijo Marc Fixed field
									$is_marc = "S";
									$filas = array();
									$linea01 = rtrim($vars[$ivars]);
									$ksc = 0;
									for ($ixsc = 1; $ixsc <= 100; $ixsc++) {
										$ivars = $ivars + 1;
										$linea = $vars[$ivars];
										if (substr($linea, 0, 1) != "S") {
											$ivars = $ivars - 1;
											$ixsc = 999;
										} else {
											$ksc = $ksc + 1;
											$filas[] = rtrim($linea);
										}
									}
									if (substr($valortag[$tag], 0, 6) == "aammdd" and isset($arrHttp["Mfn"]) and $arrHttp["Mfn"] == "New") {
										$valortag[$tag] = date("ymd") . substr($valortag[$tag], 6);
									}
									TextBox($linea01, $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, "");
									// The text box opens a table. Following input statements require an table cell
									echo "\n<tr style='display:none'><td colspan=4>";
									echo "<input type=hidden name=eti$tag  value=\"" . trim($linea01) . "\">\n";
									foreach ($filas as $lin) {
										$lin = trim($lin);
										echo "<input type=hidden name=eti$tag value=\"$lin\">\n";
									}
									echo "\n</td></tr>\n";
									break;
								case "E": // Group??
									GroupRenderer::render($vars[$ivars], $fondocelda, $titulo, $ver, $len, $tag, $ksc, $tipo, $delrep, $ayuda, $vars, $ivars, $nsc);
									break;
								case "T":
								case "TB":    // Table
									$filas = array();
									$field_t = $vars[$ivars];
									if ($nsc == 0) {
										echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
										DibujarTextRepetible($tag, $fondocelda, $field_t);
									} else {
										for ($ixsc = 1; $ixsc <= $nsc; $ixsc++) {
											$ivars = $ivars + 1;
											$linea = $vars[$ivars];
											$filas[] = rtrim($linea);
										}
										DibujarTabla($filas, $tag, $fondocelda, $field_t);
									}
									echo "\n";
									break;
								case "R":    //Radio button
									$tope = $t[9];
									echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
									$opciones = trim($t[11]);
									$lt = array();
									$lin = "";
									$filas = array();
									DibujarCheck($filas, $fondocelda, $valortag[$tag], $tag, $opciones, $tope, $tipo_e, $t[5]);
									break;
								case "C":    // Check box
									$tope = $t[9];
									echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
									$opciones = trim($t[11]);
									$lt = array();
									DibujarCheck($linea, $fondocelda, $valortag[$tag], $tag, $opciones, $tope, $tipo_e, $t[5]);
									break;
								case "S":    // Select simple
								case "SRO":  // Select simple read only
								case "M":    // Select multiple
								case "MRO":  // Select multiple read only
									if ($t[10] == "D") {
										TextBox($vars[$ivars], $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delrep, $ayuda);
										break;
									}
									echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
									$opciones = trim($t[11]);
									DibujarSelect($linea, $fondocelda, $valortag[$tag], $tag, $ksc, $opciones, $rep, $t[5]);
									break;
								case "RP":    // Protect record
									$tope = 1;
									$filas = array();
									echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
									$opciones = 1;
									DibujarCheck($filas, $fondocelda, $valortag[$tag], $tag, $opciones, $tope, $tipo_e, $t[5]);
									break;
								case "COMBO":    // Combo box
								case "COMBORO":  // Combo box read only
									echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
									$width = $t[9];
									if ($width == 0 || $width == "") $width = 50;
									$width = $width * 5.5;
									echo "<td>";
									if ($t[11] == "")
										$p_combo = $arrHttp["base"];
									else
										$p_combo = $t[11];
									ComboBox($tipo_e, $tag, $width, $rep, $t[10], $p_combo, $t[13], $t[12], $db_path, $arrHttp["base"], $valortag[$tag]);
									break;
								case "AI":    // Auto increment 
									if (
										$arrHttp["Mfn"] == "New" and !isset($valortag[$tag]) or $OpcionDeEntrada == "presentar_captura"  or
										$OpcionDeEntrada == "captura_bd"  or $OpcionDeEntrada == "capturar"
									)
										$valortag[$tag] = "";
								case "X":    // Text/Textarea
								case "U":    // Upload file
								default:
									if (!isset($ayuda)) $ayuda = "";
									$t = explode('|', $vars[$ivars]);
									if ($t[9] == 0 || $t[9] == "") $t[9] = 100;
									$len = explode('/', $t[9]);
									if (count($len) == 1) {
										TextBox($vars[$ivars], $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delrep, $ayuda);
									} else {
										if ($Repetible == "R") {
											DibujarTextRepetible($tag, $fondocelda, $vars[$ivars]);
										} else {
											TextBox($vars[$ivars], $fondocelda, $titulo, $ver, $len, $tag, $ksc, $rep, $delrep, $ayuda);
										}
									}
									break;
							} //end switch tipo_e
						}
					}
				}
			}
			echo "\n</table>\n";
			//if ($t[0]=="H" or $t[0]=="L"  ) echo "</div>";
			if ($t[0] == "XL") echo "</div>xl";
		}
	} //end for loop $ivars
	if ($cargar_texto == "S") {
		echo "Cargar texto";
	}
	if (isset($titulo_ant) and $titulo_ant != "*" and $numero_secciones > 1) {
		echo "\n<a  tabindex='-1' href=\"javascript:switchMenu('myvar_$ixant');\" style=\"text-decoration:none \">";
		if (substr($titulo_ant, 0, 1) != "<") {
			echo "<i class=\"far fa-minus-square\" style=\"vertical-align:middle\"></i> <b>" . $msgstr["cerrar"] . "</b> $titulo_ant</a> ";
		} else {
			echo $msgstr["cerrar"];
		}
	}
	if ($wrapperopen == true) echo "</div></div>"; // closes the wrapper and div myvar
	if (isset($tabopen) && $tabopen == true) echo "</div>"; // FECHA A ÚLTIMA ABA
	echo "</td></tr></table></form>\n"; // Closing open elements


	echo "<br>\n";
	if (file_exists($db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/" . "tesaurus.rel")) {
		include("../tesaurus/dataentry.php");
		$tesaurus = "S";
	}
	if ($obligatorio == "S")
		echo "<span style='color:red;'>" . $msgstr["mandatory_field"] . "</span>";
	echo "\n<script>
    function OpenAll(){
	secciones=new Array()
	ixs=-1\n";
	foreach ($secciones as $value) {
		echo "
	ixs=ixs+1
	secciones[ixs]='$value'\n";
	}
	echo "
	for (isecc=0;isecc<secciones.length;isecc++){
	    nx=secciones[isecc]
	    switchMenu(nx,isecc)
	}
    ";
	echo "}
    </script>\n";
	echo "<script>
    tesaurus=\"$tesaurus\"\n
    </script>\n";

	require_once("../dataentry/javascript_validation.php");
}
// --- INÍCIO DA LÓGICA DE ABAS DINÂMICAS (VERSÃO FINAL CLEAN) ---
?>
<style>
	/* Barra de Navegação Estilo Koha */
	.abcd-tabs-nav {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
		margin: 10px 0 20px 0;
		border-bottom: 1px solid #b9d8d9;
		padding-left: 5px;
	}

	.abcd-tab-btn {
		padding: 10px 20px;
		background: #e8e8e8;
		border: 1px solid #ccc;
		border-bottom: none;
		cursor: pointer;
		font-weight: bold;
		color: #555;
		border-radius: 4px 4px 0 0;
		margin-bottom: -1px;
		font-size: 12px;
	}

	.abcd-tab-btn:hover {
		background: #f0f0f0;
	}

	.abcd-tab-btn.active {
		background: #fff;
		color: #005aa9;
		border-top: 3px solid #005aa9;
		border-left: 1px solid #b9d8d9;
		border-right: 1px solid #b9d8d9;
		border-bottom: 1px solid #fff;
	}

	/* --- LIMPEZA DE ELEMENTOS LEGACY EM MODO ABAS --- */

	/* 1. Esconde o botão "+" (Campos) da coluna de ações */
	.has-tabs .table-fdt-two a[href^="javascript:Campos"],
	#expand_colapse_btn {
		display: none !important;
	}

	/* 2. Esconde o link "Cerrar" e o texto em negrito do acordeão original */
	.has-tabs a[href^="javascript:switchMenu"]:not(.header-fdt),
	.has-tabs a[href^="javascript:switchMenu"] b,
	.has-tabs a[href^="javascript:switchMenu"] i {
		display: none !important;
	}

	/* 3. Neutraliza o cabeçalho H (torna-o um título H6 simples) */
	.has-tabs .header-fdt {
		pointer-events: none !important;
		background: transparent !important;
		color: #333 !important;
		border: none !important;
		border-bottom: 2px solid #eee !important;
		margin: 30px 0 15px 0 !important;
		padding: 0 0 8px 0 !important;
		font-size: 14px !important;
		font-weight: bold !important;
		display: block !important;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}

	/* Força a exibição de todas as secções dentro da aba */
	.has-tabs .abcd-tab-pane .group-fields {
		display: block !important;
		border: none !important;
		padding: 0 !important;
	}
</style>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		const panes = document.querySelectorAll('.abcd-tab-pane');
		if (panes.length > 0) {
			const form = document.querySelector('form[name="forma1"]');
			if (form) form.classList.add('has-tabs');

			const nav = document.createElement('div');
			nav.className = 'abcd-tabs-nav';

			panes.forEach((pane, idx) => {
				const btn = document.createElement('div');
				btn.className = 'abcd-tab-btn';
				btn.textContent = pane.getAttribute('data-tab-title');

				if (idx === 0) {
					btn.classList.add('active');
					pane.style.display = 'block';
				}

				btn.onclick = () => {
					document.querySelectorAll('.abcd-tab-btn').forEach(b => b.classList.remove('active'));
					document.querySelectorAll('.abcd-tab-pane').forEach(p => p.style.display = 'none');
					btn.classList.add('active');
					pane.style.display = 'block';
				};
				nav.appendChild(btn);
			});

			const firstPane = panes[0];
			if (firstPane && firstPane.parentNode) {
				firstPane.parentNode.insertBefore(nav, firstPane);
			}
		}
	});
</script>
<script src="editor/js/clear_field.js"></script>