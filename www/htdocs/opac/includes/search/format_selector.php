<?php
/**
 * -------------------------------------------------------------------------
 * Helper: Format Selector
 * Author: Roger C. Guilherme
 * Date: 2026-07-28
 * -------------------------------------------------------------------------
 */

// --- AUXILIARY FUNCTIONS ---
function prefixFieldsWithV($fields_string)
{
	if (empty($fields_string)) return "";
	$tags = explode(',', $fields_string);
	$prefixed_tags = [];
	foreach ($tags as $tag) {
		$tag = trim($tag);
		if (is_numeric($tag) || strpos($tag, '^') !== false) {
			$prefixed_tags[] = 'v' . $tag;
		} else {
			$prefixed_tags[] = $tag;
		}
	}
	return implode(", ", $prefixed_tags);
}



// DETECTS AVAILABLE FORMATS BY BASE AND SELECTS THE DEFAULT FORMAT
function getDefaultFormatForBase($base, $db_path, $lang)
{
	$formatos_file = $db_path . $base . "/opac/" . $lang . "/" . $base . "_formatos.dat";
	if (!file_exists($formatos_file)) $formatos_file = $db_path . $base . "/opac/" . $base . "_formatos.dat";

	$default_format = null;
	$first_format = null; // Save the first format as a fallback

	if (file_exists($formatos_file)) {
		$lines = file($formatos_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			if (trim($line) != "") {
				$parts = explode('|', $line);
				$format_name = trim($parts[0]);
				if (substr($format_name, -4) == ".pft") $format_name = substr($format_name, 0, -4);

				// Save the first format found
				if ($first_format === null) {
					$first_format = $format_name;
				}

				// Checks whether the third column exists and is "Y" (case-insensitive)
				if (isset($parts[2]) && strtoupper(trim($parts[2])) === 'Y') {
					$default_format = $format_name;
					break; // Once you've found the pattern, you can stop searching.
				}
			}
		}
	}

	// If you find one marked with Y, return it.
	if ($default_format !== null) {
		return $default_format;
	}
	// If you did not find Y, but found some format, return the first one.
	elseif ($first_format !== null) {
		return $first_format;
	}
	// Final fallback: returns the base name if the file was empty or does not exist.
	else {
		return $base;
	}
}



function SelectFormato($base, $db_path, $msgstr)
{
	global $lang;
	$archivo = $base . "_formatos.dat";
	$fp = null;

	// Tenta ler o arquivo de formatos no idioma atual ou no padrão
	if (file_exists($db_path . $base . "/opac/" . $lang . "/" . $archivo))
		$fp = file($db_path . $base . "/opac/" . $lang . "/" . $archivo);
	elseif (file_exists($db_path . $base . "/opac/" . $archivo))
		$fp = file($db_path . $base . "/opac/" . $archivo);

	// Se não houver arquivo de formatos, retorna vazio (usa padrão interno)
	// Mas para não quebrar o list(), retornamos array com erro ou vazio
	if (!$fp) return array("", "");

	$formatos_disponiveis = [];
	$formato_padrao_Y = null;
	$primeiro_formato_lista = null;

	// --- Etapa 1: Ler todos os formatos e identificar o padrão 'Y' ---
	foreach ($fp as $linea) {
		if (trim($linea) != "") {
			$f = explode('|', $linea);
			$format_name = trim($f[0]);
			if (substr($format_name, -4) == ".pft") $format_name = substr($format_name, 0, -4);

			$label = isset($f[1]) && trim($f[1]) != "" ? trim($f[1]) : $format_name;
			$is_default = isset($f[2]) && strtoupper(trim($f[2])) === 'Y';

			$formatos_disponiveis[] = ['name' => $format_name, 'label' => $label, 'is_default' => $is_default];

			if ($is_default) {
				$formato_padrao_Y = $format_name;
			}
			if ($primeiro_formato_lista === null) {
				$primeiro_formato_lista = $format_name;
			}
		}
	}

	// --- Etapa 2: Determinar qual formato deve estar ativo ---
	$formato_ativo = null;
	if (isset($_REQUEST["Formato"])) {
		foreach ($formatos_disponiveis as $fmt) {
			if ($fmt['name'] == $_REQUEST["Formato"]) {
				$formato_ativo = $_REQUEST["Formato"];
				break;
			}
		}
	}

	// Fallback se não vier na URL
	if ($formato_ativo === null) {
		if ($formato_padrao_Y !== null) {
			$formato_ativo = $formato_padrao_Y;
		} else {
			$formato_ativo = $primeiro_formato_lista;
		}
	}

	// --- Etapa 3: Construir o HTML do Dropdown ---

	// Adiciona campos hidden para submeter o form ao trocar o formato
	$hidden_fields = "";
	$parametros = $_GET;
	unset($parametros['Formato'], $parametros['desde'], $parametros['pagina']);

	foreach ($parametros as $key => $value) {
		if (is_array($value)) {
			foreach ($value as $item) {
				$hidden_fields .= '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($item) . '">';
			}
		} else {
			$hidden_fields .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
		}
	}

	// Reseta paginação
	$hidden_fields .= '<input type="hidden" name="desde" value="1">';
	$hidden_fields .= '<input type="hidden" name="pagina" value="1">';

	// Monta o HTML final
	$salida = "<div class='d-inline-block'>"; // Container para alinhar
	$salida .= "<form name='cambio_formato' method='get' action='./' class='m-0 d-flex align-items-center'>";
	$salida .= $hidden_fields;

	// Label (opcional, pode remover se ocupar muito espaço)
	$label_fmt = isset($msgstr["front_formato_exibicao"]) ? $msgstr["front_formato_exibicao"] : "Formato";
	$salida .= "<label class='me-2 text-nowrap'>" . $label_fmt . ":</label>";

	$salida .= "<select name='Formato' onchange='this.form.submit()' class='form-select form-select-sm'>";

	foreach ($formatos_disponiveis as $fmt) {
		$selected = ($fmt['name'] == $formato_ativo) ? "selected" : "";
		$salida .= "<option value='" . $fmt['name'] . "' $selected>" . $fmt['label'] . "</option>";
	}

	$salida .= "</select>";
	$salida .= "</form>";
	$salida .= "</div>";

	// Retorna o HTML e o formato ativo para o script principal usar
	return array($salida, $formato_ativo);
}