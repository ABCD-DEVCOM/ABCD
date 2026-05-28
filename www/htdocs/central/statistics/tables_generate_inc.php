<?php
/*
* @file        tables_generate_inc.php
* @author      Roger Craveiro Guilherme
* @date        2026-05-20
* @description Refactored version of the original tables_generate.php, now acting as an Orchestrator for the Statistics Module. It processes AJAX requests, decodes parameters, and triggers the generation of statistics tables isolating each Tab	le with its respective Expression.
* changes:
* 2026-05-20: Initial refactor to separate concerns and improve maintainability. The original tables_generate.php is now stats_ajax.php, which includes this file for the core logic.
* 2026-05-21: Added detailed comments and documentation for better understanding of the code flow and logic.
* 2026-05-22: Implemented character encoding handling to ensure proper display of special characters in the generated tables.
* 2026-05-23: Optimized the execution flow to handle multiple tables and expressions more efficiently, reducing redundant processing and improving response times.
* 2026-05-24: Added error handling and user feedback for cases where no data is found or when there are issues with the provided parameters, enhancing the user experience.
* 2026-05-25: Finalized the refactor and tested the integration with the front-end, ensuring that the AJAX requests are properly processed and the resulting tables are displayed correctly in the user interface.
* 
*/

function toPageEncoding($str)
{
	global $charset, $meta_encoding;
	$target = 'ISO-8859-1';
	if (isset($charset) && trim($charset) != "") $target = strtoupper(trim($charset));
	elseif (isset($meta_encoding) && trim($meta_encoding) != "") $target = strtoupper(trim($meta_encoding));

	if ($str === null || $str === "") return "";
	$str = (string)$str;

	$is_utf8 = @mb_check_encoding($str, 'UTF-8') || strpos($str, "\xC3") !== false;

	if (strpos($target, 'UTF') !== false) {
		return $is_utf8 ? $str : @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
	} else {
		return $is_utf8 ? @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8') : $str;
	}
}

function toChartEncoding($str)
{
	if ($str === null || $str === "") return "";
	$str = (string)$str;
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
	if (!file_exists($file)) return array();
	
	return file($file);
}

function LeerRegistrosSingle($contenido, $idx, $arrHttp)
{
	global $trow, $tcol, $rows, $cols, $tabs, $tab, $tipo, $filter_date;

	if (!is_array($contenido)) return;

	$fecha_comp = "";
	$len = 0;
	if (isset($arrHttp["year_from"]) and trim($arrHttp["year_from"]) != "") {
		$fecha_comp = $arrHttp["year_from"];
		if (isset($arrHttp["month_from"]) and $arrHttp["month_from"] != "") $fecha_comp .= $arrHttp["month_from"];
		$fecha_comp = str_replace('$', "", $fecha_comp);
		$len = strlen($fecha_comp);
	}

	if (!isset($tabs[$idx])) return;
	$x = explode('|', $tabs[$idx]);
	$trow = isset($x[1]) ? $x[1] : "";
	$tcol = (isset($x[2]) and $x[2] != "LMP") ? $x[2] : "";

	foreach ($contenido as $value) {
		$linea = trim($value);
		if ($linea != "" and $linea != '$$$$') {
			$row_col = explode('¬¬¬¬¬', $linea);
			foreach ($row_col as $rrcc) {
				if (trim($rrcc) == "") continue;
				$rrcc .= '$$$$';
				$t = explode('$$$$', $rrcc);
				if (!isset($t[0])) $t[0] = "";

				$descartar = "";
				if (isset($filter_date[$idx]) and isset($fecha_comp)) {
					switch ($filter_date[$idx]) {
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
					if (!isset($tab[$idx][$t[0]][$t[1]])) $tab[$idx][$t[0]][$t[1]] = 1;
					else $tab[$idx][$t[0]][$t[1]]++;
					if (!isset($rows[$idx][$t[0]])) $rows[$idx][$t[0]] = $t[0];
					if (!isset($cols[$idx][$t[1]])) $cols[$idx][$t[1]] = $t[1];
				} else {
					if ($trow != "") {
						if (!isset($tab[$idx][$t[0]])) $tab[$idx][$t[0]][""] = 1;
						else $tab[$idx][$t[0]][""]++;
						$rows[$idx][$t[0]] = $t[0];
					} else {
						if (!isset($tab[$idx][""][$t[1]])) $tab[$idx][""][$t[1]] = 1;
						else $tab[$idx][""][$t[1]]++;
						$cols[$idx][$t[1]] = $t[1];
					}
				}
			}
		}
	}
}

function Contingencia($tabla_L, $tab_vars)
{
	$filter_d = ""; $lmp = ""; $excluir = "";
	if (empty($tabla_L)) return array("", "", "", "", "", "");

	$tabla = explode('|', $tabla_L);
	$nome_linha = isset($tabla[1]) ? trim($tabla[1]) : "";
	$nome_coluna = isset($tabla[2]) ? trim($tabla[2]) : "";
	$pft_row = ""; $pft_col = "";

	if (isset($tabla[1]) && trim($tabla[1]) != "") {
		$busca_linha = trim($tabla[1]);
		foreach ($tab_vars as $value) {
			$t = explode('|', trim($value));
			if (isset($t[0]) && (trim($busca_linha) === (isset($t[3])?trim($t[3]):"") || trim($busca_linha) === trim($t[0]))) {
				$pft_row = isset($t[1]) ? trim($t[1]) : "";
				$nome_linha = trim($t[0]);
				if (isset($t[2]) and $t[2] == "LMP") { $lmp = $t[2]; $excluir = isset($t[3]) ? $t[3] : ""; } 
				else if (isset($t[2]) and $t[2] == "true") $filter_d = "rows";
				break;
			}
		}
	}

	if (isset($tabla[2]) && trim($tabla[2]) != "") {
		$busca_coluna = trim($tabla[2]);
		foreach ($tab_vars as $value) {
			$t = explode('|', trim($value));
			if (isset($t[0]) && (trim($busca_coluna) === (isset($t[3])?trim($t[3]):"") || trim($busca_coluna) === trim($t[0]))) {
				$pft_col = isset($t[1]) ? trim($t[1]) : "";
				$nome_coluna = trim($t[0]);
				if (isset($t[2]) and $t[2] == "true") $filter_d = "cols";
				break;
			}
		}
	}

	$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_row), '/');
	$inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_col), '/');
	$is_rep = preg_match('/^\(.*\)$/', $pft_row) || ($pft_col != "" && preg_match('/^\(.*\)$/', $pft_col));

	if ($pft_col == "") {
		if ($is_rep) $Formato = "(" . $inner_row . ",'$$$$'/)"; else $Formato = $pft_row . ",'$$$$'/";
	} else {
		if ($is_rep) $Formato = "(" . $inner_row . ",'$$$$'," . $inner_col . ",'$$$$'/)"; else $Formato = $pft_row . ",'$$$$'," . $pft_col . ",'$$$$'/";
	}

	return array($Formato, $lmp, $excluir, $filter_d, $nome_linha, $nome_coluna);
}

function ConstruirFormatoVariables($arrHttp)
{
	global $tabs, $tabla, $tipo;
	$Formato = "";
	$rows_val = isset($arrHttp["rows"]) ? stripslashes($arrHttp["rows"]) : "";
	$cols_val = isset($arrHttp["cols"]) ? stripslashes($arrHttp["cols"]) : "";

	if ($rows_val != "" && $cols_val != "") {
		$r_parts = explode('|', $rows_val); $c_parts = explode('|', $cols_val);
		$r_tit = isset($r_parts[0]) ? $r_parts[0] : ""; $c_tit = isset($c_parts[0]) ? $c_parts[0] : "";
		$titulo = $r_tit . '/' . $c_tit;
		$pft_rows = isset($r_parts[1]) ? trim($r_parts[1]) : ""; $pft_cols = isset($c_parts[1]) ? trim($c_parts[1]) : "";

		$tabs[0] = $titulo . "|" . $r_tit . "|" . $c_tit; $tabla[$titulo] = $titulo . "||" . $c_tit; $tipo[] = "";

		$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_rows), '/'); $inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_cols), '/');
		$is_rep = preg_match('/^\(.*\)$/', $pft_rows) || preg_match('/^\(.*\)$/', $pft_cols);

		if ($is_rep) $Formato = "(" . $inner_row . ",'$$$$'," . $inner_col . ",'$$$$'/)"; else $Formato = $pft_rows . ",'$$$$'," . $pft_cols . ",'$$$$'/";
	} elseif ($rows_val != "") {
		$r_parts = explode('|', $rows_val);
		$titulo = isset($r_parts[0]) ? $r_parts[0] : ""; $pft_rows = isset($r_parts[1]) ? trim($r_parts[1]) : "";

		$xlmpx = (isset($r_parts[2]) and $r_parts[2] == "LMP") ? $r_parts[2] . "|" . (isset($r_parts[3]) ? $r_parts[3] : "") : "";
		$tabs[] = $titulo . "|" . $titulo . "|" . $xlmpx; $tipo[] = $xlmpx; $tabla[$titulo] = $titulo . "|" . $titulo . "|" . $xlmpx;

		$inner_row = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_rows), '/');
		if (preg_match('/^\(.*\)$/', $pft_rows)) $Formato = "(" . $inner_row . ",'$$$$'/)"; else $Formato = $pft_rows . ",'$$$$'/";
	} elseif ($cols_val != "") {
		$c_parts = explode('|', $cols_val);
		$titulo = isset($c_parts[0]) ? $c_parts[0] : ""; $pft_cols = isset($c_parts[1]) ? trim($c_parts[1]) : "";

		$tabs[] = $titulo . "||" . $titulo; $tipo[] = "";

		$inner_col = rtrim(preg_replace('/^\((.*?)\)$/', '$1', $pft_cols), '/');
		if (preg_match('/^\(.*\)$/', $pft_cols)) $Formato = "('$$$$'," . $inner_col . ",'$$$$'/)"; else $Formato = "'$$$$'," . $pft_cols . ",'$$$$'/";
	}
	return $Formato;
}

// ===================================================================================
// MOTOR ORQUESTRADOR MULTI-LOOP
// ===================================================================================
function ExecutarEstatisticasMultiLoop($arrHttp, $lang_db, $tab_vars, $db_path, $xWxis)
{
	global $tabla, $tit_proc, $tabs, $tipo, $filter_date;
	global $tab, $rows, $cols, $actparfolder;
	global $execution_tasks;

	$filter_date = array(); $execution_tasks = array(); $linhas_tabelas = array(); $tabla = array();

	// LOAD THE ALBUM'S MASTER TABLES (PRESERVES PLUS SIGNS AND ACCENTS)
	$files = [
		$db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tabs.cfg",
		$db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tabs.cfg",
		$db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tables.cfg",
		$db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tables.cfg"
	];

	foreach ($files as $file) {
		if (file_exists($file)) {
			foreach (file($file) as $value) {
				$value = trim($value);
				if ($value != "") {
					$v = explode("|", $value);
					$tabla[trim($v[0])] = $value;
					if (isset($v[3]) && trim($v[3]) != "") $tabla[trim($v[3])] = $value;
				}
			}
		}
	}

	$accion = isset($arrHttp["Accion"]) ? $arrHttp["Accion"] : "";
	$global_expr = isset($arrHttp["Expresion"]) ? stripslashes($arrHttp["Expresion"]) : "";
	$global_opcion = isset($arrHttp["Opcion"]) ? $arrHttp["Opcion"] : "";

	// CREATE THE PLAYLIST INDEPENDENTLY
	if ($accion == "Procesos") {
		$request_proc = isset($arrHttp["proc"]) ? $arrHttp["proc"] : "";
		$proc_parts = explode("||", $request_proc);
		$tit_proc = isset($proc_parts[0]) ? trim($proc_parts[0]) : "**";
		$tables_string = isset($proc_parts[1]) ? trim($proc_parts[1]) : "";
		$proc_keys = explode("|", $tables_string);

		foreach ($proc_keys as $k) {
			if (trim($k) != "") {
				$txx = explode('{{', trim($k));
				$key = trim($txx[0]);
				$linha = isset($tabla[$key]) ? $tabla[$key] : null;
				if ($linha) {
					$linhas_tabelas[] = array('line' => $linha, 'is_pft' => isset($txx[1]));
				}
			}
		}
	} elseif ($accion == "Tablas") {
		$val = isset($arrHttp["tables"]) ? $arrHttp["tables"] : "";
		$txx = explode('{{', $val);
		if (trim($txx[0]) != "") {
			// Restores the clean version of the configuration file to prevent data loss due to double decoding
			$parts = explode('|', $txx[0]);
			$title_key = trim($parts[0]);
			$linha = isset($tabla[$title_key]) ? $tabla[$title_key] : $txx[0];
			$linhas_tabelas[] = array('line' => $linha, 'is_pft' => (isset($txx[1]) && trim($txx[1]) == "PFT"));
		}
	} elseif ($accion == "Variables") {
		$Formato = ConstruirFormatoVariables($arrHttp);
		if ($Formato != "") $execution_tasks[] = array('Formato' => $Formato . "/", 'Expresion' => $global_expr, 'Opcion' => $global_opcion);
	}

	// COMBINATION OF EXPRESSIONS AND REQUIRED LOGICAL ORDER OF PRECEDENCE
	if (!empty($linhas_tabelas)) {
		foreach ($linhas_tabelas as $tk) {
			$linha_tabela = $tk['line']; $is_pft = $tk['is_pft'];
			$t_parts = explode('|', $linha_tabela);
			$table_expr = isset($t_parts[4]) ? trim($t_parts[4]) : "";

			if ($is_pft) {
				$tabs[] = $linha_tabela;
				$For = explode('|', $linha_tabela);
				$Formato = str_replace("/", ",'¬¬¬¬¬',", $For[3]) . "/";
				$tipo[] = $For[1] . '|' . $For[2];
				if (isset($For[4])) $filter_date[] = $For[4];
			} else {
				$For = Contingencia($linha_tabela, $tab_vars);
				$Formato = $For[0];
				$tipo[] = $For[1] . '|' . $For[2];
				if (isset($For[3]) && $For[3] != "") $filter_date[] = $For[3];
				$tabs[] = $t_parts[0] . "|" . $For[4] . "|" . $For[5];
			}

			// THE EXACT RULES OF PRECEDENCE:
			if ($table_expr != "") {
				// If there is a search expression in the table, execute the search expression itself.
				$task_opcion = "BUSQUEDA";
				$task_expr = $table_expr;
			} else {
				// If there is no search term, run the selected option (MFN range or Search).
				$task_opcion = $global_opcion;
				$task_expr = $global_expr;
			}
			$execution_tasks[] = array('Formato' => $Formato, 'Expresion' => $task_expr, 'Opcion' => $task_opcion);
		}
	}

	// RUN WXIS IN ISOLATED MODE
	foreach ($execution_tasks as $i => $task) {
		$query = "";
		if ($task['Opcion'] == "MFN") {
			$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Opcion=rango&Formato=" . $task['Formato'] . "&from=" . $arrHttp["Mfn"] . "&to=" . $arrHttp["to"];
		} elseif ($task['Opcion'] == "BUSQUEDA") {
			$query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["cipar"] . "&Opcion=buscar&Formato=" . $task['Formato'] . "&Expresion=" . urlencode(stripslashes($task['Expresion']));
		}

		if ($query != "") {
			$IsisScript = $xWxis . "imprime.xis";
			$contenido = WxisLlamar($IsisScript, $query);
			LeerRegistrosSingle($contenido, $i, $arrHttp);
		}
	}
}

function LosMasPrestados($tab, $maximo)
{
	global $msgstr;
	foreach ($tab as $key => $value) {
		if ($value[""] > $maximo) $arreglo[$key] = $value[""];
	}
	if (isset($arreglo) && is_array($arreglo)) {
		arsort($arreglo);
		foreach ($arreglo as $key => $value) {
			echo "<tr><td bgcolor=#ffffff>" . $key . "</td><td bgcolor=#ffffff>" . $value . "</td></tr>\n";
			if (!isset($total_cols)) $total_cols = $value;
			else $total_cols = $total_cols + $value;
		}
		$lbl_total = isset($msgstr["total"]) ? $msgstr["total"] : "Total";
		echo "<tr><td style='font-weight:bold; background:#f4f4f4;'>" . toPageEncoding($lbl_total) . "</td><td style='font-weight:bold; background:#f4f4f4;'>$total_cols</td></tr></table>\n";
	}
}

function ConstruirSalida($tab, $tabs, $tipo, $rows, $cols)
{
	global $msgstr, $execution_tasks;
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
		$lmp = ""; $maximo = ""; $columnas = "";

		$x = isset($tipo[$i]) ? explode('|', $tipo[$i]) : array("");
		if (isset($x[0]) && $x[0] == "LMP") { $lmp = "S"; $maximo = isset($x[1]) ? $x[1] : ""; } 
        else { if (isset($t[2]) && $t[2] == "LMP") { $lmp = "S"; $maximo = isset($t[3]) ? $t[3] : ""; } else { $columnas = $cols_label_header; } }

		$rows_label = isset($rows[$i]) ? $rows[$i] : array();
		$cols_label = isset($cols[$i]) ? $cols[$i] : null;
		if (is_array($rows_label)) ksort($rows_label);
		if (is_array($cols_label)) ksort($cols_label);

		$enproceso = isset($tab[$i]) ? $tab[$i] : array();

		// Base64 Encoding for ECharts Charts
		$chartData = array('title' => toChartEncoding($tit), 'labels' => array(), 'series' => array());

		if (!is_array($cols_label)) {
			$serie_data = array();
			foreach ($rows_label as $ixrow) {
				$val = isset($enproceso[$ixrow][""]) ? $enproceso[$ixrow][""] : 0;
				$chartData['labels'][] = toChartEncoding($ixrow);
				$serie_data[] = $val;
			}
			$chartData['series'][] = array('name' => toChartEncoding($tit), 'data' => $serie_data, 'type' => 'bar');
		} else {
			foreach (array_keys($cols_label) as $lbl) { $chartData['labels'][] = toChartEncoding($lbl); }
			foreach ($rows_label as $ixrow) {
				$serie_data = array();
				foreach ($cols_label as $ixcol) { $serie_data[] = isset($enproceso[$ixrow][$ixcol]) ? $enproceso[$ixrow][$ixcol] : 0; }
				$chartData['series'][] = array('name' => toChartEncoding($ixrow), 'data' => $serie_data, 'type' => 'bar', 'stack' => 'total');
			}
		}

		$jsonChart = json_encode($chartData);
		$base64Chart = base64_encode($jsonChart);

		// Transparency Subtitle Logic
		$sub_title_html = "";
		if (isset($execution_tasks[$i])) {
			$t_opc = $execution_tasks[$i]['Opcion'];
			if ($t_opc == "MFN") {
				$mfn_from = isset($_REQUEST["Mfn"]) ? $_REQUEST["Mfn"] : "";
				$mfn_to = isset($_REQUEST["to"]) ? $_REQUEST["to"] : "";
				$sub_title_html = "<small style='display:block; font-size:13px; color:#777; font-weight:normal; margin-top:6px;'><i class='fas fa-list-ol'></i> MFN: " . $mfn_from . " a " . $mfn_to . "</small>";
			} elseif ($t_opc == "BUSQUEDA") {
				$expr_text = $execution_tasks[$i]['Expresion'];
				if (trim($expr_text) != "") {
					$lbl_filtro = isset($msgstr["filter"]) ? $msgstr["filter"] : "Filtro";
					$sub_title_html = "<small style='display:block; font-size:13px; color:#777; font-weight:normal; margin-top:6px;'><i class='fas fa-search'></i> " . toPageEncoding($lbl_filtro) . ": " . toPageEncoding($expr_text) . "</small>";
				}
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
			foreach ($cols_label as $key => $c) echo "<th>" . $key . "</th>"; 
		}
		
		$lbl_total = isset($msgstr["total"]) ? $msgstr["total"] : "Total";
		echo "<th>" . toPageEncoding($lbl_total) . "</th>\n</tr>";

		$total_cols = array();
		if (is_array($cols_label)) { foreach ($cols_label as $k => $v) $total_cols[$k] = 0; } else { $total_cols[0] = 0; }

		if ($lmp == "S") {
			LosMasPrestados($enproceso, $maximo);
			echo "</div></div>";
			continue;
		}

		if (is_array($rows_label)) {
			foreach ($rows_label as $ixrow) {
				echo "<tr><td>" . $ixrow . "</td>";
				$total_fila = 0;
				if (is_array($cols_label)) {
					foreach ($cols_label as $ixcol) {
						if (isset($enproceso[$ixrow][$ixcol])) {
							$cell = $enproceso[$ixrow][$ixcol];
							$total_fila += $cell;
							echo "<td>" . $cell . "</td>";
							$total_cols[$ixcol] = (isset($total_cols[$ixcol]) ? $total_cols[$ixcol] : 0) + $cell;
						} else { echo "<td></td>"; }
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

		echo "<tr><td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>" . toPageEncoding($lbl_total) . "</td>";
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
		if (isset($cols_label)) { echo "<td style='font-weight:bold; background:#f0f2f5 !important; color:#333 !important;'>$tgen</td>"; }
		echo "</tr>\n</table></div></div>"; 
	}
}

function WxisLlamar($IsisScript, $query) {
	global $db_path, $xWxis, $Wxis, $wxisUrl, $arrHttp;
	include("../common/wxis_llamar.php");
	return $contenido;
}
?>