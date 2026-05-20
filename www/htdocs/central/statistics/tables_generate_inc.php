<?php
/*
* @file        tables_generate_inc.php
* @author      Roger Craveiro Guilherme
* @date        2026-05-20
* @description Refactored version of the original tables_generate.php, now acting as an Orchestrator for the Statistics Module. It processes AJAX requests, decodes parameters, and triggers the generation of statistics tables isolating each Table with its respective Expression.

* @changelog   2022-02-20: fho4abcd - Removed option to search by date+extra translations
* @changelog   2022-07-13: fho4abcd - Use $actparfolder as location for .par files
* @changelog   2026-05-20: Refactored by Roger C. Guilherme - Initial version.
*/

function toPageEncoding($str)
{
	global $charset, $meta_encoding;
	// Discover the official ABCD encoding (Standard: ISO-8859-1)
	$target = 'ISO-8859-1';
	if (isset($charset) && trim($charset) != "") $target = strtoupper(trim($charset));
	elseif (isset($meta_encoding) && trim($meta_encoding) != "") $target = strtoupper(trim($meta_encoding));

	if ($str === null || $str === "") return "";
	$str = (string)$str;

	// Detect whether the incoming string is UTF-8 (based on the formatting or the characteristic “Ã” in ISO)
	$is_utf8 = @mb_check_encoding($str, 'UTF-8') || strpos($str, "\xC3") !== false;

	if (strpos($target, 'UTF') !== false) {
		// If ABCD is in UTF-8, convert only what is still in ISO
		return $is_utf8 ? $str : @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
	} else {
		// If ABCD is in ISO-8859-1, simply convert it to UTF-8
		return $is_utf8 ? @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8') : $str;
	}
}

function toChartEncoding($str)
{
	if ($str === null || $str === "") return "";
	$str = (string)$str;
	// The ECharts chart STRICTLY requires UTF-8 to prevent JSON from breaking
	$is_utf8 = @mb_check_encoding($str, 'UTF-8') || strpos($str, "\xC3") !== false;
	return $is_utf8 ? $str : @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
}

// ===================================================================================

function LeerVariables($db_path, $arrHttp, $lang_db)
{
	$base = isset($arrHttp["base"]) ? $arrHttp["base"] : "";
	if ($base == "") return array();

	$file = $db_path . $base . "/def/" . $_SESSION["lang"] . "/stat.cfg";
	if (!file_exists($file)) $file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/stat.cfg";
	if (!file_exists($file)) {
		$error = "S";
	} else {
		$tab_vars = file($file);
	}
	return $tab_vars;
}

function LeerRegistros($contenido)
{
	global $trow, $tcol, $rows, $cols, $tabs, $tab, $tipo, $filter_date;
	$descartar = "";
	$ix = -1;

	if (!is_array($contenido)) return;

	foreach ($contenido as $value) {
		if (trim($value) != "" and trim($value) != '$$$$') {
			$rec = explode('****', $value);
			$i = -1;
			foreach ($rec as $linea) {
				$i = $i + 1;
				$fecha_comp = "";
				$len = 0;
				if (isset($_REQUEST["year_from"]) and trim($_REQUEST["year_from"]) != "") {
					$fecha_comp = $_REQUEST["year_from"];
					if (isset($_REQUEST["month_from"]) and $_REQUEST["month_from"] != "")
						$fecha_comp .= $_REQUEST["month_from"];
					$fecha_comp = str_replace('$', "", $fecha_comp);
					$len = strlen($fecha_comp);
				}

				$linea = trim($linea);
				if (!isset($tabs[$i])) continue;

				$x = explode('|', $tabs[$i]);
				$trow = isset($x[1]) ? $x[1] : "";
				if (isset($x[2]) and $x[2] != "LMP") $tcol = $x[2];
				else $tcol = "";

				$row_col = explode('¬¬¬¬¬', $linea);
				foreach ($row_col as $rrcc) {
					if (trim($rrcc) == "") continue;
					$rrcc .= '$$$$';
					$t = explode('$$$$', $rrcc);
					if (!isset($t[0])) $t[0] = "";

					$descartar = "";
					if (isset($filter_date[$i]) and isset($fecha_comp)) {
						switch ($filter_date[$i]) {
							case "rows":
							case "r":
								if (substr($t[0], 0, $len) != $fecha_comp) $descartar = "Y";
								break;
							case "cols":
								if (substr($t[1], 0, $len) != $fecha_comp) $descartar = "Y";
								break;
						}
					}
					if ($descartar == "Y") continue;
					if (isset($t[0]) and !isset($t[1])) $t[1] = "";
					if ($trow != "" and $tcol != "") {
						if (!isset($tab[$i][$t[0]][$t[1]])) $tab[$i][$t[0]][$t[1]] = 1;
						else $tab[$i][$t[0]][$t[1]] = $tab[$i][$t[0]][$t[1]] + 1;
						if (!isset($rows[$i][$t[0]])) $rows[$i][$t[0]] = $t[0];
						if (!isset($cols[$i][$t[1]])) $cols[$i][$t[1]] = $t[1];
					} else {
						if ($trow != "") {
							if (!isset($tab[$i][$t[0]])) $tab[$i][$t[0]][""] = 1;
							else $tab[$i][$t[0]][""] = $tab[$i][$t[0]][""] + 1;
							$rows[$i][$t[0]] = $t[0];
						} else {
							if (!isset($tab[$i][""][$t[1]])) $tab[$i][""][$t[1]] = 1;
							else $tab[$i][""][$t[1]] = $tab[$i][""][$t[1]] + 1;
							$cols[$i][$t[1]] = $t[1];
						}
					}
				}
			}
		}
	}
}

function Frecuencia($rc)
{
	$rc = trim(stripslashes($rc));
	$tabla = explode('|', $rc);
	$Formato = $tabla[0];
	$trow = $tabla[1];
	$tcol = $tabla[2];
	$Formato = $tabla[1];
	$Formato .= ",'$$$$'," . $trow . ",'$$$$'," . $tcol;
	if (strpos($Formato, "/") === false) $Formato .= ",'$$$$',/";
	return ($Formato);
}

function Contingencia($tabla_L, $tab_vars)
{
	$filter_d = "";
	$lmp = "";
	$excluir = "";

	if (empty($tabla_L)) return array("", "", "", "", "", "");

	$tabla_L = urldecode($tabla_L);
	$tabla = explode('|', $tabla_L);

	$nome_linha = isset($tabla[1]) ? trim($tabla[1]) : "";
	$nome_coluna = isset($tabla[2]) ? trim($tabla[2]) : "";

	$pft_row = "";
	$pft_col = "";

	if (isset($tabla[1]) && trim($tabla[1]) != "") {
		$busca_linha = trim($tabla[1]);
		foreach ($tab_vars as $value) {
			$value = trim($value);
			if ($value != "") {
				$t = explode('|', $value);
				$var_name = trim($t[0]);
				$var_id = isset($t[3]) ? trim($t[3]) : "";

				if ($busca_linha === $var_id || $busca_linha === $var_name) {
					$pft_row = isset($t[1]) ? trim($t[1]) : "";
					$nome_linha = $var_name;

					if (isset($t[2]) and $t[2] == "LMP") {
						$lmp = $t[2];
						$excluir = isset($t[3]) ? $t[3] : "";
					} else {
						if (isset($t[2]) and $t[2] == "true") $filter_d = "rows";
					}
					break;
				}
			}
		}
	}

	if (isset($tabla[2]) && trim($tabla[2]) != "") {
		$busca_coluna = trim($tabla[2]);
		foreach ($tab_vars as $value) {
			$value = trim($value);
			if ($value != "") {
				$t = explode('|', $value);
				$var_name = trim($t[0]);
				$var_id = isset($t[3]) ? trim($t[3]) : "";

				if ($busca_coluna === $var_id || $busca_coluna === $var_name) {
					$pft_col = isset($t[1]) ? trim($t[1]) : "";
					$nome_coluna = $var_name;
					if (isset($t[2]) and $t[2] == "true") $filter_d = "cols";
					break;
				}
			}
		}
	}

	$pft_row = trim($pft_row);
	$pft_col = trim($pft_col);

	$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_row), '/');
	$inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_col), '/');

	$is_rep = preg_match('/^\(.*\)$/', $pft_row) || ($pft_col != "" && preg_match('/^\(.*\)$/', $pft_col));

	if ($pft_col == "") {
		if ($is_rep) $Formato = "(" . $inner_row . ",'$$$$'/)";
		else $Formato = $pft_row . ",'$$$$'/";
	} else {
		if ($is_rep) $Formato = "(" . $inner_row . ",'$$$$'," . $inner_col . ",'$$$$'/)";
		else $Formato = $pft_row . ",'$$$$'," . $pft_col . ",'$$$$'/";
	}

	return array($Formato, $lmp, $excluir, $filter_d, $nome_linha, $nome_coluna);
}

function LosMasPrestados($tab, $maximo)
{
	global $msgstr;
	foreach ($tab as $key => $value) {
		if ($value[""] > $maximo)
			$arreglo[$key] = $value[""];
	}
	if (isset($arreglo) && is_array($arreglo)) {
		arsort($arreglo);
		foreach ($arreglo as $key => $value) {
			echo "<tr><td bgcolor=#ffffff>" . toPageEncoding($key) . "</td>";
			echo "<td bgcolor=#ffffff>" . $value . "</td></tr>\n";
			if (!isset($total_cols)) $total_cols = $value;
			else $total_cols = $total_cols + $value;
		}
		echo "<tr><td style='font-weight:bold; background:#f4f4f4;'>" . toPageEncoding($msgstr["total"]) . "</td><td style='font-weight:bold; background:#f4f4f4;'>$total_cols</td></tr></table>\n";
	}
}

function ConstruirFormato($arrHttp, $lang_db, $tab_vars, $db_path)
{
	global $tabla, $tit_proc, $tabs, $tipo, $filter_date;
	$filter_date = array();

	// Error Prevention in PHP 8
	$accion = isset($_REQUEST["Accion"]) ? $_REQUEST["Accion"] : "";

	switch ($accion) {
		case "Procesos":
			$file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tabs.cfg";
			if (!file_exists($file)) $file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tabs.cfg";
			if (file_exists($file)) {
				$file_lines = file($file);
				foreach ($file_lines as $value) {
					$value = trim($value);
					if ($value != "") {
						$v = explode("|", $value);
						$tabla[trim($v[0])] = $value;
						if (isset($v[3]) && trim($v[3]) != "") $tabla[trim($v[3])] = $value;
					}
				}
			}
			$file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tables.cfg";
			if (!file_exists($file)) $file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tables.cfg";
			if (file_exists($file)) {
				$file_lines = file($file);
				foreach ($file_lines as $value) {
					$value = trim($value);
					if ($value != "") {
						$v = explode("|", $value);
						$tabla[trim($v[0])] = $value;
						if (isset($v[3]) && trim($v[3]) != "") $tabla[trim($v[3])] = $value;
					}
				}
			}

			$request_proc = isset($_REQUEST["proc"]) ? urldecode($_REQUEST["proc"]) : "";
			$proc_parts = explode("||", $request_proc);
			$tit_proc = isset($proc_parts[0]) ? trim($proc_parts[0]) : "**";
			$tables_string = isset($proc_parts[1]) ? trim($proc_parts[1]) : "";

			// If the process has a search expression configured (e.g., “Most Borrowed”), it injects the search parameters so they can be processed later
			if (count($proc_parts) >= 4 && trim($proc_parts[2]) != "") {
				$_REQUEST["Opcion"] = "BUSQUEDA";
				$_REQUEST["Expresion"] = trim($proc_parts[2]);
			}

			$proc = explode("|", $tables_string);
			$Formulas = "";

			foreach ($proc as $value) {
				$value = trim($value);
				if ($value != "") {
					$txx = explode('{{', $value);
					$value_key = $txx[0];
					$PFT = isset($txx[1]) ? "PFT" : "";

					$linha_tabela = isset($tabla[$value_key]) ? $tabla[$value_key] : null;

					if ($linha_tabela) {
						if ($PFT == "PFT") {
							$tabs[] = $linha_tabela;
							$For = explode('|', $linha_tabela);
							$Formato = str_replace("/", ",'¬¬¬¬¬',", $For[3]);
							$tipo[] = $For[1] . '|' . $For[2];
							if (isset($For[4])) $filter_date[] = $For[4];
						} else {
							$For = Contingencia($linha_tabela, $tab_vars);
							$Formato = $For[0];
							$tipo[] = $For[1] . '|' . $For[2];
							if (isset($For[3]) && $For[3] != "")
								$filter_date[] = $For[3];
							$Formato = str_replace("/", "", $Formato);

							$t_parts = explode('|', $linha_tabela);
							$tabs[] = $t_parts[0] . "|" . $For[4] . "|" . $For[5];
						}

						if ($Formulas == "") $Formulas = $Formato;
						else $Formulas .= ",'****'," . $Formato;
					}
				}
			}
			$Formato = isset($Formulas) ? $Formulas . "/" : "";
			break;

		case 'Tablas':
			$val = isset($_REQUEST["tables"]) ? urldecode(stripslashes($_REQUEST["tables"])) : "";
			$txx = explode('{{', $val);
			if (isset($txx[1]) and $txx[1] == "PFT") {
				$tabs[] = $val;
				$For = explode('|', $txx[0]);
				$Formato = $For[3] . "/";
				$tipo[] = $For[1] . '|' . $For[2];
				if (isset($For[4])) $filter_date[] = $For[4];
			} else {
				$For = Contingencia(urldecode($_REQUEST["tables"]), $tab_vars);
				$Formato = $For[0];
				$tipo[] = $For[1] . '|' . $For[2];
				if (isset($For[3]) && $For[3] != "") $filter_date[] = $For[3];

				$t_parts = explode('|', $val);
				$tabs[] = $t_parts[0] . "|" . $For[4] . "|" . $For[5];
			}
			break;

		case 'Variables':
			$rows_val = isset($_REQUEST["rows"]) ? urldecode(stripslashes($_REQUEST["rows"])) : "";
			$cols_val = isset($_REQUEST["cols"]) ? urldecode(stripslashes($_REQUEST["cols"])) : "";

			if ($rows_val != "" && $cols_val != "") {
				$r_parts = explode('|', $rows_val);
				$c_parts = explode('|', $cols_val);

				$r_tit = isset($r_parts[0]) ? $r_parts[0] : "";
				$c_tit = isset($c_parts[0]) ? $c_parts[0] : "";
				$titulo = $r_tit . '/' . $c_tit;
				$pft_rows = isset($r_parts[1]) ? trim($r_parts[1]) : "";
				$pft_cols = isset($c_parts[1]) ? trim($c_parts[1]) : "";

				$tabs[0] = $titulo . "|" . $r_tit . "|" . $c_tit;
				$tabla[$titulo] = $titulo . "||" . $c_tit;
				$arrHttp["tables"] = $tabs[0];
				$tipo[] = "";

				$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_rows), '/');
				$inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_cols), '/');
				$is_rep = preg_match('/^\(.*\)$/', $pft_rows) || preg_match('/^\(.*\)$/', $pft_cols);

				if ($is_rep) {
					$Formato = "(" . $inner_row . ",'$$$$'," . $inner_col . ",'$$$$'/)";
				} else {
					$Formato = $pft_rows . ",'$$$$'," . $pft_cols . ",'$$$$'/";
				}
			} elseif ($rows_val != "") {
				$r_parts = explode('|', $rows_val);
				$titulo = isset($r_parts[0]) ? $r_parts[0] : "";
				$pft_rows = isset($r_parts[1]) ? trim($r_parts[1]) : "";

				$xlmpx = "";
				if (isset($r_parts[2]) and $r_parts[2] == "LMP") {
					$xlmpx = $r_parts[2] . "|" . (isset($r_parts[3]) ? $r_parts[3] : "");
				}
				$tabs[] = $titulo . "|" . $titulo . "|" . $xlmpx;
				$tipo[] = $xlmpx;
				$tabla[$titulo] = $titulo . "|" . $titulo . "|" . $xlmpx;

				$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_rows), '/');
				if (preg_match('/^\(.*\)$/', $pft_rows)) {
					$Formato = "(" . $inner_row . ",'$$$$'/)";
				} else {
					$Formato = $pft_rows . ",'$$$$'/";
				}
			} elseif ($cols_val != "") {
				$c_parts = explode('|', $cols_val);
				$titulo = isset($c_parts[0]) ? $c_parts[0] : "";
				$pft_cols = isset($c_parts[1]) ? trim($c_parts[1]) : "";

				$tabs[] = $titulo . "||" . $titulo;
				$tipo[] = "";

				$inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_cols), '/');
				if (preg_match('/^\(.*\)$/', $pft_cols)) {
					$Formato = "('$$$$'," . $inner_col . ",'$$$$'/)";
				} else {
					$Formato = "'$$$$'," . $pft_cols . ",'$$$$'/";
				}
			}
			break;
	}
	return isset($Formato) ? $Formato : "";
}

function SeleccionarRegistros($arrHttp, $db_path, $Formato, $xWxis)
{
	global $msgstr, $actparfolder;

	$opcion = isset($_REQUEST["Opcion"]) ? $_REQUEST["Opcion"] : "";
	switch ($opcion) {
		case "MFN":
			$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Opcion=rango&Formato=" . $Formato;
			$query .= "&from=" . $arrHttp["Mfn"] . "&to=" . $arrHttp["to"];
			break;
		case "BUSQUEDA":
			$Expresion = isset($_REQUEST["Expresion"]) ? urlencode($_REQUEST["Expresion"]) : "";
			$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Opcion=buscar&Formato=" . $Formato;
			$query .= "&Expresion=$Expresion";
			break;
	}
	$IsisScript = $xWxis . "imprime.xis";
	$contenido = WxisLlamar($IsisScript, $query);
	return $contenido;
}

function ConstruirSalida($tab, $tabs, $tipo, $rows, $cols)
{
	global $msgstr;
	$count_tab = is_array($tab) ? count($tab) : 0;

	echo "<style>
        .analytics-card .statTable th { background-color: #f0f2f5 !important; color: #333 !important; font-weight: bold !important; white-space: nowrap !important; padding: 10px !important; border: 1px solid #ccc !important; }
        .analytics-card .statTable td { white-space: nowrap !important; padding: 8px !important; border: 1px solid #ddd !important; background-color: #fff !important; color: #444 !important; }
        .analytics-card .table-responsive { overflow-x: auto; width: 100%; border: 1px solid #eee; margin-bottom: 20px; }
    </style>";

	for ($i = 0; $i < $count_tab; $i++) {
		if (!isset($tabs[$i])) continue;

		$t = explode("|", $tabs[$i]);

		$tit = isset($t[0]) ? $t[0] : "";
		$filas_label_header = isset($t[1]) ? $t[1] : "";
		$cols_label_header = isset($t[2]) ? $t[2] : "";

		$lmp = "";
		$maximo = "";
		$columnas = "";

		$x = isset($tipo[$i]) ? explode('|', $tipo[$i]) : array("");
		if (isset($x[0]) && $x[0] == "LMP") {
			$lmp = "S";
			$maximo = isset($x[1]) ? $x[1] : "";
		} else {
			if (isset($t[2]) && $t[2] == "LMP") {
				$lmp = "S";
				$maximo = isset($t[3]) ? $t[3] : "";
			} else {
				$columnas = $cols_label_header;
			}
		}

		$rows_label = isset($rows[$i]) ? $rows[$i] : array();
		$cols_label = isset($cols[$i]) ? $cols[$i] : null;
		if (is_array($rows_label)) ksort($rows_label);
		if (is_array($cols_label)) ksort($cols_label);

		$enproceso = isset($tab[$i]) ? $tab[$i] : array();

		// --- CÁPSULA BASE64 ---
		$chartData = array(
			'title' => toChartEncoding($tit),
			'labels' => array(),
			'series' => array()
		);

		if (!is_array($cols_label)) {
			$serie_data = array();
			foreach ($rows_label as $ixrow) {
				$val = isset($enproceso[$ixrow][""]) ? $enproceso[$ixrow][""] : 0;
				$chartData['labels'][] = toChartEncoding($ixrow);
				$serie_data[] = $val;
			}
			$chartData['series'][] = array('name' => toChartEncoding($tit), 'data' => $serie_data, 'type' => 'bar');
		} else {
			foreach (array_keys($cols_label) as $lbl) {
				$chartData['labels'][] = toChartEncoding($lbl);
			}
			foreach ($rows_label as $ixrow) {
				$serie_data = array();
				foreach ($cols_label as $ixcol) {
					$serie_data[] = isset($enproceso[$ixrow][$ixcol]) ? $enproceso[$ixrow][$ixcol] : 0;
				}
				$chartData['series'][] = array('name' => toChartEncoding($ixrow), 'data' => $serie_data, 'type' => 'bar', 'stack' => 'total');
			}
		}

		$jsonChart = json_encode($chartData);
		$base64Chart = base64_encode($jsonChart);

		// --- IMPRESSÃO BLINDADA COM toPageEncoding ---
		echo "<div class='analytics-card' style='margin-bottom:50px; background:#fff; border:1px solid #eee; padding:20px; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>";
	
		$sub_title_html = "";
		$opcion = isset($_REQUEST["Opcion"]) ? $_REQUEST["Opcion"] : "";

		if ($opcion == "MFN") {
			$mfn_from = isset($_REQUEST["Mfn"]) ? $_REQUEST["Mfn"] : "";
			$mfn_to = isset($_REQUEST["to"]) ? $_REQUEST["to"] : "";
			$sub_title_html = "<small style='display:block; font-size:13px; color:#777; font-weight:normal; margin-top:6px;'><i class='fas fa-list-ol'></i> MFN: " . $mfn_from . " a " . $mfn_to . "</small>";
		} elseif ($opcion == "BUSQUEDA") {
			$expr_text = isset($_REQUEST["Expresion"]) ? stripslashes($_REQUEST["Expresion"]) : "";
			if (trim($expr_text) != "") {
				$sub_title_html = "<small style='display:block; font-size:13px; color:#777; font-weight:normal; margin-top:6px;'><i class='fas fa-search'></i> Filtro: " . toPageEncoding($expr_text) . "</small>";
			}
		}

		echo "<div class='analytics-card' style='margin-bottom:50px; background:#fff; border:1px solid #eee; padding:20px; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>";
		echo "<h3 style='margin-top:0; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;'>" . toPageEncoding($tit) . $sub_title_html . "</h3>";
	
		if ($lmp != "S") {
			$chartId = "chart_" . $i . "_" . time();
			echo "<div id='$chartId' class='echart-container' data-chart='" . $base64Chart . "' style='width:100%; height:400px; margin-bottom:20px;'></div>";
		}

		echo "<div class='table-responsive'>";
		echo "<table border class=statTable cellpadding=5 style='width:100%; border-collapse:collapse;'>\n";

		$ix = is_array($cols_label) ? count($cols_label) : 1;
		if (is_array($cols_label)) echo "<tr><th>&nbsp;</th><th colspan=$ix align=center>" . toPageEncoding($columnas) . "</th><th>&nbsp;</th></tr>";
		echo "<tr><th>" . toPageEncoding($filas_label_header) . "</th>";

		if (is_array($cols_label)) {
			foreach ($cols_label as $key => $c) echo "<th>" . toPageEncoding($key) . "</th>";
		}
		echo "<th>" . toPageEncoding($msgstr["total"]) . "</th>\n</tr>";

		$total_cols = array();
		if (is_array($cols_label)) {
			foreach ($cols_label as $k => $v) $total_cols[$k] = 0;
		} else {
			$total_cols[0] = 0;
		}

		if ($lmp == "S") {
			LosMasPrestados($enproceso, $maximo);
			echo "</div></div>";
			continue;
		}

		if (is_array($rows_label)) {
			foreach ($rows_label as $ixrow) {
				echo "<tr><td>" . toPageEncoding($ixrow) . "</td>";
				$total_fila = 0;
				if (is_array($cols_label)) {
					foreach ($cols_label as $ixcol) {
						if (isset($enproceso[$ixrow][$ixcol])) {
							$cell = $enproceso[$ixrow][$ixcol];
							$total_fila += $cell;
							echo "<td>" . $cell . "</td>";
							$total_cols[$ixcol] = (isset($total_cols[$ixcol]) ? $total_cols[$ixcol] : 0) + $cell;
						} else {
							echo "<td></td>";
						}
					}
					echo "<td style='font-weight:bold; background:#f9f9f9 !important;'>" . $total_fila . "</td>";
				} else {
					$cell = isset($enproceso[$ixrow][""]) ? $enproceso[$ixrow][""] : 0;
					echo "<td>" . $cell . "</td>";
					$total_cols[0] = (isset($total_cols[0]) ? $total_cols[0] : 0) + $cell;
				}
				echo "</tr>\n";
			}
		}

		echo "<tr><td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>" . toPageEncoding($msgstr["total"]) . "</td>";
		$tgen = 0;
		if (isset($cols_label)) {
			foreach ($cols_label as $ixcol) {
				echo "<td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>" . $total_cols[$ixcol] . "</td>";
				$tgen = $tgen + $total_cols[$ixcol];
			}
		} else {
			echo "<td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>" . $total_cols[0] . "</td>";
			$tgen = $tgen + $total_cols[0];
		}
		if (isset($cols_label)) {
			echo "<td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>$tgen</td>";
		}
		echo "</tr>\n</table></div></div>";
	}
}

function WxisLlamar($IsisScript, $query)
{
	global $db_path, $xWxis, $Wxis, $wxisUrl, $arrHttp;
	include("../common/wxis_llamar.php");
	return $contenido;
}
