<?php

/**
 * Name: inc_odds_show_controls.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Core logic for parsing .tab form definitions for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

function get_odds_tab_file(string $filename, string $lang): ?string
{
    global $db_path;

    $bridge = PluginBridge::getInstance();
    $centralPath = rtrim($bridge->get('abcd_path', realpath(__DIR__ . '/../../../central')), '/\\');
    $contentPath = realpath($centralPath . '/../content');
    $pluginSlug = 'odds';

    // 1. Primary path: Content Vault (User Overrides)
    $cofreFile = "{$contentPath}/lang/{$lang}/{$filename}";
    if (file_exists($cofreFile)) return $cofreFile;

    // 2. Secondary path: Core Plugin translations (Factory default)
    $motorFile = "{$contentPath}/plugins/{$pluginSlug}/lang/{$lang}/{$filename}";
    if (file_exists($motorFile)) return $motorFile;

    // 3. Fallback path: Core Plugin English (Factory fallback)
    $motorFallback = "{$contentPath}/plugins/{$pluginSlug}/lang/en/{$filename}";
    if (file_exists($motorFallback)) return $motorFallback;

    if (!empty($db_path)) {
        $legacyFile = rtrim($db_path, '/\\') . "/odds/def/{$lang}/{$filename}";
        if (file_exists($legacyFile)) return $legacyFile;
    }
    return null;
}

function _remove_comments(string $text): string
{
    $text = preg_replace('!/\*.*?\*/!s', '', $text);
    $text = str_replace("\r", '', $text);
    $text = preg_replace('/\n\s*\n/', "\n", $text);
    return $text;
}

function _add_source(string $source_label, string $input_length, string $referer, array $variable_fields): string
{
    global $msgstr, $lang;

    $odds_source_file = get_odds_tab_file("source.tab", $lang);
    if (!$odds_source_file) return "<div class='text-danger'>source.tab missing.</div>";

    $rawContent = file_get_contents($odds_source_file);
    $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);
    if (!mb_check_encoding($rawContent, 'UTF-8')) {
        $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
    }

    $file_contents = _remove_comments(trim($rawContent));
    $lines = explode("\n", $file_contents);

    $options = [];
    foreach ($lines as $line) {
        if (trim($line) !== "") $options[] = explode('|', trim($line));
    }

    $last_option = end($options);
    $last_value = $last_option[0];
    $selected = $variable_fields["tag900"] ?? "";

    // Wrapper para manter o comportamento flex
    $html = "<div class='flex-grow-1' style='min-width: 250px; max-width: 100%;'>";
    $html .= "<label class='form-label fw-bold' for='select_source'>{$source_label}</label>";
    $html .= "<select id='select_source' class='form-select mb-2' name='tag900' onchange=\"toggleSourceInput('{$last_value}')\">";

    $is_last_selected = false;
    foreach ($options as $opt) {
        $val = trim($opt[0]);
        $lbl = trim($opt[1] ?? $val);
        $isSelected = ($val === $selected) ? "selected" : "";
        if ($isSelected && $val === $last_value) $is_last_selected = true;
        $html .= "<option value=\"{$val}\" {$isSelected}>{$lbl}</option>";
    }
    $html .= "</select>";

    $tag900_visibility = $is_last_selected ? "" : "d-none";
    $other_value = $variable_fields["tag900_other"] ?? "";
    $html .= "<input id='tag900_other' name='tag900_other' class='form-control mb-3 {$tag900_visibility}' type='text' value='{$other_value}' placeholder='...'>";
    $html .= "</div>";

    return $html;
}

function _build_input(string $line, array $values, array $variable_fields): string
{
    $input_name     = $values[0];
    $input_label    = str_replace('*', '<span class="text-danger">*</span>', $values[1]);
    $input_type     = $values[2] ?? 'text';
    $input_length   = $values[3] ?? '20';
    $input_value    = $variable_fields[$input_name] ?? "";

    // CSS para respeitar o tamanho do input mas permitir flexibilidade
    $input_style = "style='width: calc({$input_length}ch + 1.5rem); max-width: 100%;'";

    if ($input_name === "tag900") {
        return _add_source($values[1], $input_length, $variable_fields['referer'] ?? "", $variable_fields);
    }

    $input = "<div class='flex-grow-1' style='min-width: 150px;'>";
    $input .= "<label class='form-label fw-bold' for='{$input_name}'>{$input_label}</label>";
    $input .= "<input value='{$input_value}' type='{$input_type}' id='{$input_name}' name='{$input_name}' class='form-control mb-3' {$input_style} maxlength='{$input_length}' />";
    $input .= "</div>";

    return $input;
}

function read_odds_show_controls(string $lang, string $level, array $variable_fields, &$optional_inputs): bool
{
    $optional_inputs = "<div class='d-flex flex-wrap gap-3'>"; // Container pai flexível

    $odds_show_file = get_odds_tab_file("odds_show_controls.tab", $lang);
    if (!$odds_show_file) return false;

    $rawContent = file_get_contents($odds_show_file);
    $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);
    if (!mb_check_encoding($rawContent, 'UTF-8')) {
        $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
    }

    $file_contents = _remove_comments(trim($rawContent));

    $pattern = '/\[' . preg_quote($level, '/') . '\]\s*\|.*?\n(.*?)(?=\n\[|$)/s';

    if (preg_match($pattern, $file_contents, $matches)) {
        $lines = explode("\n", trim($matches[1]));
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $values = array_map('trim', explode("|", $line));
            $optional_inputs .= _build_input($line, $values, $variable_fields);
        }
        $optional_inputs .= "</div>"; // Fecha container
        return true;
    }
    $optional_inputs .= "</div>";
    return false;
}