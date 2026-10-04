<?php

/**
 * Name: api_mapper.php
 * Author: Roger C. Guilherme
 * Description: Visual FDT to API/JSON Mapper
 * 
 * Created on: 2026-08-15
 */

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    exit;
}

require_once("../config.php");
require_once("../common/get_post.php");

$lang = $_SESSION["lang"] ?? "en";
@include("../lang/admin.php");

$base = $_REQUEST['base'] ?? '';
if (empty($base)) {
    die($msgstr['api_db_not_specified'] ?? "Database not specified.");
}

$apiRootPath = realpath(dirname(__DIR__, 2) . '/abcd-api');
$mappingsDir = $apiRootPath . '/resources/mappings/';
$i2xFile = $mappingsDir . $base . '.i2x';
$pftFile = $mappingsDir . $base . '_dc.pft';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_mapping') {
    $mappingData = $_POST['map'] ?? [];

    $i2xContent = "root=oai_dc\n";
    $pftContent = "'          <oai_dc:dc\n";
    $pftContent .= "                  xmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n";
    $pftContent .= "                  xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
    $pftContent .= "                  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
    $pftContent .= "                  xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n";
    $pftContent .= "                  http://www.openarchives.org/OAI/2.0/oai_dc.xsd\">'/\n\n";

    foreach ($mappingData as $tag => $node) {
        $node = trim($node);
        if (!empty($node) && $node !== 'custom') {
            $i2xContent .= (int)$tag . " " . $node . "\n";
            $pftContent .= "if p(v{$tag}) then ( '<{$node}><![CDATA[',v{$tag},']]></{$node}>'/ ) fi,\n";
        }
    }
    $pftContent .= "\n'          </oai_dc:dc>'/\n";

    if (!is_dir($mappingsDir)) @mkdir($mappingsDir, 0755, true);

    $i2xSaved = file_put_contents($i2xFile, $i2xContent);
    $pftSaved = file_put_contents($pftFile, $pftContent);

    if ($i2xSaved !== false && $pftSaved !== false) {
        $message = str_replace('{{base}}', $base, $msgstr['api_mapping_saved'] ?? "API mapping format for '{{base}}' saved successfully!");
        $messageType = "success";
    } else {
        $message = $msgstr['api_mapping_error'] ?? "Error writing mapping files. Check folder permissions.";
        $messageType = "error";
    }
}

$fdtPath = $db_path . $base . "/def/" . $_SESSION["lang"] . "/" . $base . ".fdt";
if (!file_exists($fdtPath)) {
    $fdtPath = $db_path . $base . "/def/" . $lang_db . "/" . $base . ".fdt";
}

$fields = [];
if (file_exists($fdtPath)) {
    $fdtLines = file($fdtPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($fdtLines as $line) {
        $t = explode('|', $line);
        if (!in_array($t[0], ['H', 'LDR', 'TAB', 'L'])) {
            $tag = trim($t[1]);
            $name = trim($t[2]);
            if (is_numeric($tag)) $fields[(int)$tag] = $name;
        }
    }
}

$existingMap = [];
if (file_exists($i2xFile)) {
    $i2xLines = file($i2xFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($i2xLines as $line) {
        $line = trim($line);
        if (strpos($line, 'root=') === 0) continue;

        $parts = explode(' ', $line, 2);
        if (count($parts) === 2 && is_numeric($parts[0])) {
            $existingMap[(int)$parts[0]] = trim($parts[1]);
        }
    }
}

$dcElements = [
    'dc:identifier' => $msgstr['api_dc_identifier'] ?? 'Identifier (ID, ISBN, ISSN)',
    'dc:title' => $msgstr['api_dc_title'] ?? 'Title',
    'dc:creator' => $msgstr['api_dc_creator'] ?? 'Creator (Author)',
    'dc:subject' => $msgstr['api_dc_subject'] ?? 'Subject (Keywords)',
    'dc:description' => $msgstr['api_dc_description'] ?? 'Description (Abstract, Notes)',
    'dc:publisher' => $msgstr['api_dc_publisher'] ?? 'Publisher',
    'dc:contributor' => $msgstr['api_dc_contributor'] ?? 'Contributor',
    'dc:date' => $msgstr['api_dc_date'] ?? 'Date',
    'dc:type' => $msgstr['api_dc_type'] ?? 'Type (Genre)',
    'dc:format' => $msgstr['api_dc_format'] ?? 'Format (Physical/Digital)',
    'dc:source' => $msgstr['api_dc_source'] ?? 'Source',
    'dc:language' => $msgstr['api_dc_language'] ?? 'Language',
    'dc:relation' => $msgstr['api_dc_relation'] ?? 'Relation (Links, Attachments)',
    'dc:coverage' => $msgstr['api_dc_coverage'] ?? 'Coverage (Spatial/Temporal)',
    'dc:rights' => $msgstr['api_dc_rights'] ?? 'Rights (Access conditions)'
];

include("../common/header.php");
?>

<body>
    <style>
        .map-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .map-table th {
            background: #e2e8f0;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #cbd5e1;
        }

        .map-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .map-table tr:hover {
            background: #f8fafc;
        }

        .tag-badge {
            background: #3b82f6;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
    <?php include("../common/institutional_info.php"); ?>
    <div class="sectionInfo">
        <div class="breadcrumb"><?php echo $msgstr["configure"] ?? 'Configure ABCD'; ?> / <?php echo $msgstr['api_visual_mapper_breadcrumb'] ?? 'API REST Manager / Visual Data Mapper'; ?></div>
        <div class="actions"><a href="api_manager.php" class="defaultButton backButton"><i class="fas fa-arrow-left"></i> <?php echo $msgstr['plugin_back'] ?? 'Back'; ?></a></div>
        <div class="spacer">&#160;</div>
    </div>
    <div class="middle form">
        <div class="formContent">
            <h3><i class="fas fa-project-diagram"></i> <?php echo $msgstr['api_data_mapper'] ?? 'API Data Mapper:'; ?> <strong><?php echo strtoupper($base); ?></strong></h3>
            <p style="color: #666; margin-bottom: 20px;"><?php echo $msgstr['api_mapper_instructions'] ?? 'Map your database fields to standard JSON keys. Fields left as "Do not export" will be hidden from the API output.'; ?></p>
            <?php if (!empty($message)): ?>
                <div style="background-color: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <strong><?php echo ucfirst($messageType); ?>:</strong> <?php echo htmlspecialchars($message, ENT_QUOTES, $charset); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="save_mapping">
                <table class="map-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php echo $msgstr['api_tag'] ?? 'Tag'; ?></th>
                            <th><?php echo $msgstr['api_field_name_fdt'] ?? 'Field Name (from FDT)'; ?></th>
                            <th style="width: 400px;"><?php echo $msgstr['api_output_key'] ?? 'API Output Key (JSON Node)'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $tag => $name): ?>
                            <?php $currentMap = $existingMap[$tag] ?? ''; ?>
                            <tr>
                                <td><span class="tag-badge"><?php echo htmlspecialchars($tag, ENT_QUOTES, $charset); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($name, ENT_QUOTES, $charset); ?></strong></td>
                                <td>
                                    <div style="display: flex; gap: 10px;">
                                        <select name="map[<?php echo $tag; ?>]" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;" onchange="checkCustom(this, 'custom_<?php echo $tag; ?>')">
                                            <option value=""><?php echo $msgstr['api_no_export'] ?? '-- Do not export this field --'; ?></option>
                                            <optgroup label="<?php echo $msgstr['api_std_dc'] ?? 'Standard Dublin Core'; ?>">
                                                <?php foreach ($dcElements as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($currentMap === $key) ? 'selected' : ''; ?>><?php echo $label; ?> (<?php echo $key; ?>)</option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="<?php echo $msgstr['api_custom'] ?? 'Custom'; ?>">
                                                <option value="custom" <?php echo (!empty($currentMap) && !isset($dcElements[$currentMap])) ? 'selected' : ''; ?>><?php echo $msgstr['api_custom_key'] ?? 'Custom Key...'; ?></option>
                                            </optgroup>
                                        </select>
                                        <input type="text" id="custom_<?php echo $tag; ?>" name="custom_map[<?php echo $tag; ?>]" value="<?php echo (!empty($currentMap) && !isset($dcElements[$currentMap])) ? htmlspecialchars($currentMap, ENT_QUOTES, $charset) : ''; ?>" style="display: <?php echo (!empty($currentMap) && !isset($dcElements[$currentMap])) ? 'block' : 'none'; ?>; width: 150px; padding: 6px;" placeholder="<?php echo $msgstr['api_custom_placeholder'] ?? 'e.g. my_field'; ?>" onkeyup="updateCustomValue(this, 'map[<?php echo $tag; ?>]')">
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; background: #f8f9fa; padding: 15px; border-top: 1px solid #ddd; text-align: right;">
                    <button type="submit" class="bt bt-green" style="font-size: 16px; padding: 10px 20px;"><i class="fas fa-save"></i> <?php echo $msgstr['api_save_mappings'] ?? 'Save API Mappings'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function checkCustom(selectObj, customInputId) {
            var customInput = document.getElementById(customInputId);
            if (selectObj.value === 'custom') {
                customInput.style.display = 'block';
                customInput.focus();
            } else {
                customInput.style.display = 'none';
                customInput.value = '';
            }
        }

        function updateCustomValue(inputObj, selectName) {
            var selectObj = document.querySelector('select[name="' + selectName + '"]');
            var customOption = selectObj.querySelector('option[value="custom"]');
            if (customOption) customOption.value = inputObj.value;
        }
        window.onload = function() {
            var selects = document.querySelectorAll('select[name^="map["]');
            selects.forEach(function(sel) {
                if (sel.value !== "" && !sel.querySelector('option[value="' + sel.value + '"]')) {
                    var customOpt = sel.querySelector('option[value="custom"]');
                    if (customOpt) customOpt.value = sel.value;
                }
            });
        };
    </script>
    <?php include("../common/footer.php"); ?>