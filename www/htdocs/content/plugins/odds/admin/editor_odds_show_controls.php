<?php

/**
 * Name: editor_odds_show_controls.php
 * Description: Standalone visual editor for the odds_show_controls.tab file.
 * Enforces FDT schema integrity and implements the Core vs Content architecture.
 *
 * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 */

if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

$bridge = PluginBridge::getInstance();
$abcdPath = rtrim($bridge->get('abcd_path', realpath(__DIR__ . '/../../../central')), '/\\');
//$contentPath = realpath($abcdPath . '/../content');
$dbPath = rtrim($bridge->get('db_path'), '/\\');
$lang = $_SESSION['lang'] ?? 'en';
$pluginSlug = 'odds';

// -------------------------------------------------------------------------
// 1. FDT PARSER (Schema Enforcement & UTF-8 Sanitization)
// -------------------------------------------------------------------------
$fdtFields = [];
$fdtPath = "{$dbPath}/odds/def/{$lang}/odds.fdt";
if (!file_exists($fdtPath)) {
    $fdtPath = "{$dbPath}/odds/def/en/odds.fdt"; // Fallback
}

if (file_exists($fdtPath)) {
    $fdtLines = file($fdtPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($fdtLines as $line) {
        $parts = explode('|', $line);
        // Typical ABCD FDT format: F|tag|Name|...
        if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2])) {
            $tagFormat = 'tag' . str_pad(trim($parts[1]), 3, '0', STR_PAD_LEFT);
            $label = trim($parts[2]);

            // Legacy ABCD fix: json_encode requires strict UTF-8. 
            // If the FDT is in ISO-8859-1 (common in CISIS), we must convert it.
            if (!mb_check_encoding($label, 'UTF-8')) {
                $label = mb_convert_encoding($label, 'UTF-8', 'ISO-8859-1');
            }

            $fdtFields[$tagFormat] = $label;
        }
    }
}


// -------------------------------------------------------------------------
// 2. SAVE HANDLER (Writes only to CONTENT / Cofre)
// -------------------------------------------------------------------------
$alertEditorMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_odds_controls') {

    $cofreDir = "{$dbPath}/odds/def/{$lang}/";
    if (!is_dir($cofreDir)) {
        @mkdir($cofreDir, 0777, true);
    }

    $fileShowControls = "{$cofreDir}/odds_show_controls.tab";
    $payload = $_POST['odds_controls_payload'] ?? '';

    // Normalize line endings
    $payload = preg_replace("/(^[\r\n]*|[\r\n]+)[\\s\t]*[\r\n]+/", "\n", trim($payload));

    if (@file_put_contents($fileShowControls, $payload) !== false) {
        $alertEditorMsg = "<div style='color:green; font-weight:bold; padding:10px; border:1px solid green; margin-bottom:15px; background:#e8f5e9;'><b>Success!</b> Custom form layout saved to Content directory." . $fileShowControls . "</div>";
    } else {
        $alertEditorMsg = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'><b>Error:</b> Permission denied writing to <code>{$cofreDir}</code>.</div>";
    }
}

// 3. LOAD HANDLER (Cascade: Cofre -> Motor -> Fallback)
$fileToLoad = null;
// Nota: Usei $dbPath que você já definiu acima, mantendo a consistência
$cofreFile = "{$dbPath}/odds/def/{$lang}/odds_show_controls.tab";

if (file_exists($cofreFile)) {
    $fileToLoad = $cofreFile;
    $loadedSource = "Database Directory (odds)";
}

$parsedData = [];

if ($fileToLoad) {
    // 1. Lê o conteúdo todo como string
    $rawContent = file_get_contents($fileToLoad);

    // 2. Remove o BOM se existir (caractere invisível de codificação)
    $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

    // 3. Converte para UTF-8 se não estiver (blinda contra ISO-8859-1)
    if (!mb_check_encoding($rawContent, 'UTF-8')) {
        $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
    }

    // 4. Divide em linhas e processa
    $lines = explode("\n", $rawContent);
    $currentLevel = null;

    foreach ($lines as $line) {
        $line = trim($line); // Remove espaços em branco antes e depois
        if (empty($line) || str_starts_with($line, '#')) continue;

        // Match Level Header: [as] | ... (Regex mais robusta para espaços)
        if (preg_match('/^\[(.*?)\]\s*\|\s*(.*)$/', $line, $matches)) {
            $currentLevel = trim($matches[1]);
            $lvlDesc = trim($matches[2]);

            $parsedData[$currentLevel] = [
                'desc' => $lvlDesc,
                'fields' => []
            ];
        }
        // Match Field: tagXXX | ...
        elseif ($currentLevel && str_starts_with($line, 'tag')) {
            $parts = array_map('trim', explode('|', $line));

            // Só adiciona se o formato estiver correto (pelo menos 4 partes: tag, label, type, len)
            if (count($parts) >= 4) {
                $parsedData[$currentLevel]['fields'][] = [
                    'tag'        => $parts[0] ?? '',
                    'label'      => $parts[1] ?? '',
                    'type'       => $parts[2] ?? 'text',
                    'length'     => $parts[3] ?? '60',
                    'validation' => $parts[4] ?? ''
                ];
            }
        }
    }
}

?>

<style>
    .odds-editor-wrap {
        font-family: sans-serif;
    }

    .level-block {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .level-header {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        align-items: center;
        border-bottom: 2px solid #ccc;
        padding-bottom: 10px;
    }

    .field-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
        background: #fff;
        padding: 8px;
        border: 1px solid #eee;
    }

    .field-row select,
    .field-row input {
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 3px;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
        cursor: pointer;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
        border: none;
    }

    .btn-success {
        background: #28a745;
        color: white;
        border: none;
    }

    .btn-primary {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 15px;
        font-weight: bold;
        cursor: pointer;
    }

    .drag-handle {
        cursor: grab;
        color: #999;
        padding: 0 10px;
    }
</style>

<div class="odds-editor-wrap">
    <?php echo $alertEditorMsg; ?>

    <div style="margin-bottom: 15px; font-size: 13px; color: #555;">
        <i class="fas fa-info-circle"></i> Currently loaded from: <b><?php echo $loadedSource ?? 'New File'; ?></b>.
        Changes will be saved exclusively to your Content directory.
    </div>

    <form id="oddsControlsForm" method="POST" action="">
        <input type="hidden" name="action" value="save_odds_controls">
        <textarea name="odds_controls_payload" id="odds_controls_payload" style="display:none;"></textarea>

        <div id="levelsContainer"></div>

        <button type="button" class="btn-success" style="padding: 8px 15px; font-weight: bold; cursor: pointer;" onclick="OddsEditor.addLevel()">
            <i class="fas fa-plus"></i> Add Bibliographic Level
        </button>
        <hr>
        <button type="button" class="btn-primary" onclick="OddsEditor.save()">
            <i class="fas fa-save"></i> Save Form Structure
        </button>
    </form>
</div>

<script>
    // JSON_FORCE_OBJECT ensures empty arrays become {} in JS, preventing iteration errors
    const fdtDict = <?php echo json_encode($fdtFields, JSON_FORCE_OBJECT | JSON_HEX_QUOT | JSON_HEX_TAG) ?: '{}'; ?>;
    const initialData = <?php echo json_encode($parsedData, JSON_FORCE_OBJECT | JSON_HEX_QUOT | JSON_HEX_TAG) ?: '{}'; ?>;

    const OddsEditor = {
        container: document.getElementById('levelsContainer'),

        init: function() {
            if (Object.keys(initialData).length === 0) {
                this.addLevel(); // Empty state
            } else {
                for (const [code, data] of Object.entries(initialData)) {
                    this.addLevel(code, data.desc, data.fields);
                }
            }
        },

        createSelect: function(options, selectedVal, className) {
            let sel = document.createElement('select');
            sel.className = className;

            let optPlaceholder = document.createElement('option');
            optPlaceholder.value = "";
            optPlaceholder.text = "Select a field...";
            sel.appendChild(optPlaceholder);

            let found = false;
            for (const [val, lbl] of Object.entries(options)) {
                let opt = document.createElement('option');
                opt.value = val;
                opt.text = `${val} - ${lbl}`;
                if (val === selectedVal) {
                    opt.selected = true;
                    found = true;
                }
                sel.appendChild(opt);
            }

            if (selectedVal && !found) {
                let opt = document.createElement('option');
                opt.value = selectedVal;
                opt.text = `${selectedVal} - (não encontrado no FDT)`;
                opt.selected = true;
                sel.appendChild(opt);
            }

            return sel;
        },

        addField: function(fieldsContainer, fieldData = null) {
            const row = document.createElement('div');
            row.className = 'field-row';

            const fTag = fieldData ? fieldData.tag : '';
            const fLbl = fieldData ? fieldData.label : '';
            const fType = fieldData ? fieldData.type : 'text';
            const fLen = fieldData ? fieldData.length : '60';
            const fVal = fieldData ? fieldData.validation : '';

            // Tag Selector (From FDT)
            const selTag = this.createSelect(fdtDict, fTag, 'f-tag');

            row.innerHTML = `
            <span class="drag-handle"><i class="fas fa-bars"></i></span>
            <input type="text" class="f-lbl" value="${fLbl}" placeholder="Field Label" style="flex: 2;">
            <select class="f-type">
                <option value="text" ${fType === 'text' ? 'selected' : ''}>Text</option>
                <option value="textarea" ${fType === 'textarea' ? 'selected' : ''}>Textarea</option>
            </select>
            <input type="number" class="f-len" value="${fLen}" placeholder="Length" style="width: 70px;">
            <input type="text" class="f-val" value="${fVal}" placeholder="Validation (e.g. email, required)" style="flex: 1;">
            <button type="button" class="btn-sm btn-danger" onclick="this.parentElement.remove()" title="Remove Field">
                <i class="fas fa-times"></i>
            </button>
        `;

            row.insertBefore(selTag, row.children[1]);

            // Auto-fill label when selecting a tag
            selTag.addEventListener('change', function() {
                const labelInput = row.querySelector('.f-lbl');
                if (labelInput.value === '') {
                    // Extract just the label part (removing the 'tag010 - ' prefix)
                    const fullText = this.options[this.selectedIndex].text;
                    const labelText = fullText.split(' - ').slice(1).join(' - ');
                    labelInput.value = labelText;
                }
            });

            fieldsContainer.appendChild(row);
        },

        addLevel: function(code = '', desc = '', fields = []) {
            const block = document.createElement('div');
            block.className = 'level-block';

            const header = document.createElement('div');
            header.className = 'level-header';
            header.innerHTML = `
            <b>Level Code:</b> <input type="text" class="lvl-code" value="${code}" placeholder="e.g., as" style="width: 60px;">
            <b>Description:</b> <input type="text" class="lvl-desc" value="${desc}" placeholder="Journal article" style="flex: 1;">
            <button type="button" class="btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-trash"></i> Remove Level
            </button>
        `;

            const fieldsContainer = document.createElement('div');
            fieldsContainer.className = 'fields-container';

            const btnAddField = document.createElement('button');
            btnAddField.type = 'button';
            btnAddField.className = 'btn-sm btn-success';
            btnAddField.style.marginTop = '10px';
            btnAddField.innerHTML = '<i class="fas fa-plus"></i> Add Field';
            btnAddField.onclick = () => this.addField(fieldsContainer);

            block.appendChild(header);
            block.appendChild(fieldsContainer);
            block.appendChild(btnAddField);

            this.container.appendChild(block);

            if (fields.length > 0) {
                fields.forEach(f => this.addField(fieldsContainer, f));
            } else {
                this.addField(fieldsContainer); // default empty field
            }
            const fieldsArray = Array.isArray(fields) ? fields : Object.values(fields || {});

            if (fieldsArray.length > 0) {
                fieldsArray.forEach(f => this.addField(fieldsContainer, f));
            } else {
                this.addField(fieldsContainer); // default empty field
            }
        },

        save: function() {
            let output = "";
            const levels = this.container.querySelectorAll('.level-block');

            levels.forEach(lvl => {
                const code = lvl.querySelector('.lvl-code').value.trim();
                const desc = lvl.querySelector('.lvl-desc').value.trim();
                if (!code) return;

                output += `[${code}] | ${desc}\n`;

                const fields = lvl.querySelectorAll('.field-row');
                fields.forEach(f => {
                    const tag = f.querySelector('.f-tag').value.trim();
                    const lbl = f.querySelector('.f-lbl').value.trim();
                    const type = f.querySelector('.f-type').value.trim();
                    const len = f.querySelector('.f-len').value.trim();
                    const val = f.querySelector('.f-val').value.trim();

                    if (tag) {
                        let line = `${tag} | ${lbl} | ${type} | ${len}`;
                        if (val) line += ` | ${val}`;
                        output += line + "\n";
                    }
                });
                output += "\n";
            });

            document.getElementById('odds_controls_payload').value = output;
            document.getElementById('oddsControlsForm').submit();
        }
    };

    document.addEventListener('DOMContentLoaded', () => OddsEditor.init());
</script>