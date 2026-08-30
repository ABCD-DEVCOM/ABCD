<?php
/*
______________________________________________________________________________________________________________
SCRIPT: edit_relevance.php
VERSION: 4.1 (Fixes clobbering bug and alert position)
*
* CHANGE LOG:
* 2025-11-09 rogercgui - Correção do bug de sobrescrita (clobbering).
* - Script agora lê o relevance.def existente antes de salvar.
* - Preserva seções não relacionadas (ex: [restriction]).
* - Mensagem de sucesso movida para dentro do layout ($update_message).
* - parseFdtFile agora usa a função central file_get_contents_utf8().
______________________________________________________________________________________________________________
*/

include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/relevance";
include "../../common/inc_div-helper.php";

$update_message = ""; // Variável para feedback

// Functions findFdtPath, parseFdtFile, parseRelevanceFile remain the same as version 3.0
function findFdtPath($db_path, $base, $lang, $default_lang)
{
    $paths_to_check = [
        $db_path . $base . "/def/" . $lang . "/" . $base . ".fdt",
        $db_path . $base . "/def/" . $default_lang . "/" . $base . ".fdt",
        $db_path . $base . "/def/" . $base . ".fdt"
    ];

    foreach ($paths_to_check as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

/**
 * Analisa o arquivo .fdt e retorna um array de campos (tag => nome).
 * (Modificada para usar file_get_contents_utf8)
 */
function parseFdtFile($fdt_path)
{
    $fields = [];
    // --- Usa file_get_contents_utf8() ---
    $lines = file_get_contents_utf8($fdt_path);

    if ($lines) {
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            $fieldType = trim($parts[0] ?? '');
            if (isset($parts[1], $parts[2]) && in_array($fieldType, ['T', 'AI', 'M', 'F', 'LDR'])) {
                $tag = trim($parts[1]);
                $name = trim($parts[2]);
                if ($tag !== '' && is_numeric($tag) && $name !== '') {
                    $fields[$tag] = $name;
                }
            }
        }
        ksort($fields, SORT_NUMERIC);
    }
    return $fields;
}


function parseRelevanceFile($relevance_path)
{
    $config = [];
    if (file_exists($relevance_path)) {
        $ini_array = parse_ini_file($relevance_path, true);
        if (is_array($ini_array)) {
            foreach ($ini_array as $section => $values) {
                // Apenas processa as seções de relevância
                if (in_array($section, ['title', 'author', 'subject', 'general']) && isset($values['fields'])) {
                    $field_tags = preg_split('/,\s*/', $values['fields']);
                    foreach ($field_tags as $tag) {
                        $config[trim($tag)] = $section;
                    }
                }
            }
        }
    }
    return $config;
}

// =================================================================
// LÓGICA DE SALVAMENTO CORRIGIDA
// =================================================================
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "save") {
    $base = $_REQUEST['base'];
    $relevance_path = $db_path . $base . "/opac/relevance.def";

    // 1. Carrega todos os dados existentes (incluindo [restriction], etc.)
    $ini_data = [];
    if (file_exists($relevance_path)) {
        $ini_data = parse_ini_file($relevance_path, true);
        if ($ini_data === false) $ini_data = [];
    }

    // 2. Processa os dados de relevância do formulário
    $relevance_data = [
        'title' => [],
        'author' => [],
        'subject' => [],
        'general' => []
    ];

    if (isset($_REQUEST['fields'])) {
        foreach ($_REQUEST['fields'] as $tag => $category) {
            if (!empty($category) && isset($relevance_data[$category])) {
                $relevance_data[$category][] = $tag;
            }
        }
    }

    // 3. Sobrescreve *apenas* as seções de relevância no array $ini_data
    // Se a seção ficar vazia, ela é removida (unset) para não ser escrita
    if (!empty($relevance_data['title'])) {
        $ini_data['title'] = ['fields' => implode(", ", $relevance_data['title'])];
    } else {
        unset($ini_data['title']);
    }

    if (!empty($relevance_data['author'])) {
        $ini_data['author'] = ['fields' => implode(", ", $relevance_data['author'])];
    } else {
        unset($ini_data['author']);
    }

    if (!empty($relevance_data['subject'])) {
        $ini_data['subject'] = ['fields' => implode(", ", $relevance_data['subject'])];
    } else {
        unset($ini_data['subject']);
    }

    // Lógica do 'general': só adiciona 'ALL' se todo o resto estiver vazio
    if (empty($relevance_data['title']) && empty($relevance_data['author']) && empty($relevance_data['subject']) && empty($relevance_data['general'])) {
        $ini_data['general'] = ['fields' => 'ALL'];
    } elseif (!empty($relevance_data['general'])) {
        $ini_data['general'] = ['fields' => implode(", ", $relevance_data['general'])];
    } else {
        unset($ini_data['general']);
    }


    // 4. Constrói o conteúdo final do INI
    $ini_content = "";
    foreach ($ini_data as $section => $values) {
        if (empty($values) || !is_array($values)) {
            continue; // Pula seções vazias ou malformadas
        }

        $ini_content .= "[$section]\n";
        foreach ($values as $key => $value) {
            // Adiciona aspas para garantir que os valores sejam strings
            $ini_content .= "$key = \"" . addslashes($value) . "\"\n";
        }
        $ini_content .= "\n";
    }

    // 5. Salva o arquivo e define a mensagem de update
    if (file_put_contents($relevance_path, $ini_content) === false) {
        $update_message = "<div class='alert alert-danger'>Error saving the file: $relevance_path</div>";
    } else {
        $update_message = "<div class='alert alert-success'>File saved successfully: $relevance_path</div>";
    }
}
// =================================================================
// FIM DA LÓGICA DE SALVAMENTO
// =================================================================


// Data loading remains the same
$base = isset($_REQUEST["base"]) ? $_REQUEST["base"] : "";
$lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : "en";
$fdt_fields = [];
$fdt_path = "";
$current_config = [];

if ($base != "") {
    $fdt_path = findFdtPath($db_path, $base, $lang, $lang_db);
    $fdt_fields = parseFdtFile($fdt_path);
    $relevance_path = $db_path . $base . "/opac/relevance.def";
    $current_config = parseRelevanceFile($relevance_path);
}

?>
<script>
    var idPage = "db_configuration";
</script>

<div class="middle form row m-0">
    <div class="formContent col-2 m-2 p-0">
        <?php include("conf_opac_menu.php"); ?>
    </div>
    <div class="formContent col-9 m-2">
        <?php include("menu_dbbar.php");  ?>
        <h3><?php echo $msgstr["relevance_conf"]; ?>: <?php echo strtoupper($base); ?></h3>

        <?php
        // Exibe a mensagem de sucesso/erro AQUI, dentro do layout
        if (!empty($update_message)) echo $update_message;
        ?>

        <?php if ($base == ""): ?>
            <div class="alert alert-info">Please select a database from the sidebar to begin.</div>
        <?php elseif (empty($fdt_fields)): ?>
            <div class="alert alert-danger">
                FDT file not found or is empty. Searched for: <strong><?php echo $base . ".fdt"; ?></strong>
            </div>
        <?php else: ?>
            <form name="edit_relevance" method="post">
                <input type="hidden" name="base" value="<?php echo $base; ?>">
                <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                <input type="hidden" name="action" value="save">

                <div classRead-Only" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="table-dark" style="position: sticky; top: 0; background: #fff;">
                            <tr>
                                <th>Tag</th>
                                <th><?php echo $msgstr["campo"]; ?></th>
                                <th><?php echo $msgstr["relevance_category"]; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($fdt_fields as $tag => $name) :
                                $current_category = isset($current_config[$tag]) ? $current_config[$tag] : '';
                            ?>
                                <tr>
                                    <td><strong>V<?php echo $tag; ?></strong></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td>
                                        <select name="fields[<?php echo $tag; ?>]" class="form-select form-select-sm">
                                            <option value="" <?php echo ($current_category == '') ? 'selected' : ''; ?>> -- Not used -- </option>
                                            <option value="title" <?php echo ($current_category == 'title') ? 'selected' : ''; ?>>Title</option>
                                            <option value="author" <?php echo ($current_category == 'author') ? 'selected' : ''; ?>>Author</option>
                                            <option value="subject" <?php echo ($current_category == 'subject') ? 'selected' : ''; ?>>Subject</option>
                                            <option value="general" <?php echo ($current_category == 'general') ? 'selected' : ''; ?>>General</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <br>
                <button type="submit" class="btn btn-primary"><?php echo $msgstr["save"]; ?></button>
                <a href="procesos_base.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo $msgstr["cancel"]; ?></a>
            </form>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include("../../common/footer.php"); ?>