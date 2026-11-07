<?php
/*
20261102 Conversion tables with a header row ("[HEADER]" mode) and loading the text from a file.
Included by carga_txt.php, carga_txt_cnv.php, carga_txt_ver.php and carga_txt_ex.php.

1. Default texts. They are used only when the language files do not define the same key,
   so they can be moved to lang/<lang>/admin.php (or soporte.php) at any time.
2. Functions that read a CSV/TSV text whose first row holds the column names.

Format of the .cnv file in this mode (first line = [HEADER]; the "label" is the column name):
[HEADER]
Title|245||||
Author|700|||/|
*/

include("../lang/soporte.php");

// Escapes only & < > " ' (single-byte charset => bytes of UTF-8 or ISO-8859-1 pass untouched)
function TxtImp_E($s)
{
	return htmlspecialchars($s, ENT_QUOTES, 'ISO-8859-1');
}

// Normalizes a column name / label for comparison: trim, BOM, nbsp, repeated spaces, lower case
function TxtImp_Norm($s)
{
	if (substr($s, 0, 3) == "\xEF\xBB\xBF") $s = substr($s, 3);
	$s = str_replace("\xC2\xA0", " ", $s);
	$s = trim(preg_replace('/\s+/', ' ', $s));
	if (function_exists('mb_strtolower') && function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8'))
		return mb_strtolower($s, 'UTF-8');
	return strtolower($s);
}

// Guesses the delimiter from the first non-empty line (text between quotes is ignored)
function TxtImp_DetectDelimiter($text)
{
	$tab = 0;
	$sc = 0;
	$co = 0;
	$inq = false;
	$seen = false;
	$len = strlen($text);
	for ($i = 0; $i < $len; $i++) {
		$c = $text[$i];
		if ($c == '"') {
			$inq = !$inq;
			$seen = true;
			continue;
		}
		if ($inq) continue;
		if ($c == "\n" || $c == "\r") {
			if ($seen) break;
			continue;
		}
		$seen = true;
		if ($c == "\t") $tab++;
		elseif ($c == ";") $sc++;
		elseif ($c == ",") $co++;
	}
	if ($tab > 0) return "\t";
	if ($sc > 0 || $co > 0) return ($sc >= $co) ? ";" : ",";
	return "\t";
}

// Splits CSV/TSV text into rows of cells. Handles "quoted cells", "" inside quotes and line breaks inside quotes.
function TxtImp_ParseDelimited($text, $delim)
{
	$rows = array();
	$row = array();
	$field = "";
	$inq = false;
	$len = strlen($text);
	for ($i = 0; $i < $len; $i++) {
		$c = $text[$i];
		if ($inq) {
			if ($c == '"') {
				if ($i + 1 < $len && $text[$i + 1] == '"') {
					$field .= '"';
					$i++;
				} else {
					$inq = false;
				}
			} else {
				$field .= $c;
			}
		} else {
			if ($c == '"' && $field === "") {
				$inq = true;
			} elseif ($c == $delim) {
				$row[] = $field;
				$field = "";
			} elseif ($c == "\n" || $c == "\r") {
				if ($c == "\r" && $i + 1 < $len && $text[$i + 1] == "\n") $i++;
				$row[] = $field;
				$field = "";
				$rows[] = $row;
				$row = array();
			} else {
				$field .= $c;
			}
		}
	}
	if ($field !== "" || count($row) > 0) {
		$row[] = $field;
		$rows[] = $row;
	}
	return $rows;
}

/*
Reads a text whose first row has the column names.
$rotulo : conversion table as returned by LeerTablaCnv() ($rotulo[tag] = array(label, tag, subfields, delimiters, occurrence separator, format))
$info   : filled with delimiter, headers, ignored columns and number of matched columns
Returns one element per data row, in the structure that ProcesarBD() already understands:
   array( tag => array(value1, value2, ...), ... )
Columns with the same name are added to the same tag (repeated occurrences).
*/
function TxtImp_ReadHeaderTable($text, $rotulo, &$info)
{
	$info = array("delimiter" => "", "headers" => array(), "ignored" => array(), "matched" => 0);
	if (substr($text, 0, 3) == "\xEF\xBB\xBF") $text = substr($text, 3);
	$delim = TxtImp_DetectDelimiter($text);
	$info["delimiter"] = $delim;
	$all = TxtImp_ParseDelimited($text, $delim);

	// column name (normalized) => tag
	$map = array();
	foreach ($rotulo as $def) {
		if (!isset($def[0]) || !isset($def[1])) continue;
		$name = TxtImp_Norm($def[0]);
		if ($name !== "" && !isset($map[$name])) $map[$name] = $def[1];
	}

	$hdr = null;
	$coltag = array();
	$rows = array();
	foreach ($all as $cells) {
		if ($hdr === null) {
			// first non-empty row = header
			if (count($cells) == 1 && trim($cells[0]) === "") continue;
			$hdr = $cells;
			foreach ($hdr as $j => $h) {
				$n = TxtImp_Norm($h);
				if ($n === "") continue;
				$info["headers"][] = trim($h);
				if (isset($map[$n])) {
					$coltag[$j] = $map[$n];
					$info["matched"]++;
				} else {
					$info["ignored"][] = trim($h);
				}
			}
			continue;
		}
		$out = array();
		foreach ($coltag as $j => $tag) {
			if (!isset($cells[$j])) continue;
			$v = trim(str_replace(array("\r\n", "\r", "\n"), " ", $cells[$j]));
			if ($v !== "") $out[$tag][] = $v;
		}
		if (count($out) > 0) $rows[] = $out;
	}
	$info["ignored"] = array_values(array_unique($info["ignored"]));
	return $rows;
}

// Shows the header that was read and the columns that will not be imported
function TxtImp_PrintInfo($info)
{
	global $msgstr;
	$d = ($info["delimiter"] == "\t") ? "TAB" : $info["delimiter"];
	echo "<div style='margin:8px 0; font-size:0.9em;'>";
	echo "<b>" . $msgstr["txtimp_hdr_read"] . "</b> (" . $msgstr["txtimp_delim"] . ": " . TxtImp_E($d) . "): ";
	echo TxtImp_E(implode(" | ", $info["headers"]));
	if (count($info["ignored"]) > 0) {
		echo "<br><font color=red><b>" . $msgstr["txtimp_cols_ignored"] . ":</b> " . TxtImp_E(implode(" | ", $info["ignored"])) . "</font>";
	}
	echo "</div>\n";
}
