<?php
/*
______________________________________________________________________________________________________________
SCRIPT: edit_restriction.php
DESCRIPTION: Configura o acesso restrito a registros no OPAC.
*
* CHANGE LOG:
* 2025-11-09 rogercgui - Refatoração completa:
* - Lógica de salvamento movida para o topo (usa $update_message).
* - Funções de leitura (parseFdtFile, getUserTypes) usam file_get_contents_utf8().
* - Rótulos hard-coded substituídos por variáveis $msgstr.
* - Layout padronizado com flex e accordions para helpers (FDT e Tipos de Usuário).
______________________________________________________________________________________________________________
*/

include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/restricted-access";
include "../../common/inc_div-helper.php";

// --- Funções Auxiliares ---

/**
 * Encontra o caminho do arquivo .fdt para a base de dados.
 * (Copiada de edit_relevance.php - sem alteração de lógica)
 */
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

/**
 * Lê as configurações da seção [restriction] do relevance.def.
 * (sem alteração de lógica)
 */
function parseRestrictionSettings($relevance_path)
{
    $settings = [
        'restriction_field' => '',
        'restriction_value' => '',
        'restriction_type' => 'hidden', // Valor padrão
        'not_restricted_users' => ''
    ];

    if (file_exists($relevance_path)) {
        $ini = parse_ini_file($relevance_path, true);
        if (isset($ini['restriction'])) {
            $settings['restriction_field'] = $ini['restriction']['restriction_field'] ?? '';
            $settings['restriction_value'] = $ini['restriction']['restriction_value'] ?? '';
            $settings['restriction_type'] = $ini['restriction']['restriction_type'] ?? 'hidden';
            $settings['not_restricted_users'] = $ini['restriction']['not_restricted_users'] ?? '';
        }
    }
    return $settings;
}

/**
 * Lê os tipos de usuários do arquivo typeofusers.tab.
 * (Modificada para usar file_get_contents_utf8)
 */
function getUserTypes($db_path, $lang, $default_lang)
{
    $types = [];
    $paths_to_check = [
        $db_path . "circulation/def/" . $lang . "/typeofusers.tab",
        $db_path . "circulation/def/" . $default_lang . "/typeofusers.tab",
        $db_path . "circulation/def/typeofusers.tab" // Fallback
    ];

    $file_path = null;
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            $file_path = $path;
            break;
        }
    }

    if ($file_path) {
        // --- Usa file_get_contents_utf8() ---
        $lines = file_get_contents_utf8($file_path);
        if ($lines) {
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 2) {
                    $types[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
    }
    return $types;
}


// --- Lógica de Salvamento ---
$update_message = ""; // Variável para feedback
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "save") {
    $base = $_REQUEST['base'];
    $relevance_path = $db_path . $base . "/opac/relevance.def";

    // Lê todo o arquivo .def existente
    $ini_data = [];
    if (file_exists($relevance_path)) {
        $ini_data = parse_ini_file($relevance_path, true);
        if ($ini_data === false) $ini_data = [];
    }

    // Atualiza/Cria apenas a seção [restriction]
    $ini_data['restriction'] = [
        'restriction_field' => $_REQUEST['restriction_field'] ?? '',
        'restriction_value' => $_REQUEST['restriction_value'] ?? '',
        'restriction_type' => $_REQUEST['restriction_type'] ?? 'hidden',
        'not_restricted_users' => $_REQUEST['not_restricted_users'] ?? ''
    ];

    // Reconstroi o conteúdo do INI
    $ini_content = "";
    foreach ($ini_data as $section => $values) {
        $ini_content .= "[$section]\n";
        foreach ($values as $key => $value) {
            // Adiciona aspas para garantir que os valores sejam strings
            $ini_content .= "$key = \"" . addslashes($value) . "\"\n";
        }
        $ini_content .= "\n";
    }

    // Salva o arquivo
    if (file_put_contents($relevance_path, $ini_content) === false) {
        $update_message = "<div class='alert alert-danger'>Error saving the file: $relevance_path</div>";
    } else {
        $update_message = "<div class='alert alert-success'>File saved successfully: $relevance_path</div>";
    }
}

// --- Carregamento de Dados ---
$base = isset($_REQUEST["base"]) ? $_REQUEST["base"] : "";
$lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : "en";
$fdt_fields = [];
$fdt_path = "";
$current_config = [];
$user_types = [];

if ($base != "") {
    // Carrega campos do FDT
    $fdt_path = findFdtPath($db_path, $base, $lang, $lang_db);
    $fdt_fields = parseFdtFile($fdt_path);

    // Carrega configurações de restrição atuais
    $relevance_path = $db_path . $base . "/opac/relevance.def";
    $current_config = parseRestrictionSettings($relevance_path);

    // Carrega tipos de usuários (para referência)
    $user_types = getUserTypes($db_path, $lang, $lang_db);
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
        <h3><?php echo $msgstr["opac_conf_restricted_records"]; ?>: <?php echo strtoupper($base); ?></h3>

        <?php
        // Exibe a mensagem de sucesso/erro AQUI, dentro do layout
        if (!empty($update_message)) echo $update_message;
        ?>

        <?php if ($base == ""): ?>
            <div class="alert alert-info"><?php echo $msgstr["cfg_db_select_db_to_start"]; ?></div>
        <?php elseif (empty($fdt_fields)): ?>
            <div class="alert alert-danger">
                <?php echo $msgstr["fdt_not_found_or_empty"]; ?> <strong><?php echo $base . ".fdt"; ?></strong>
            </div>
        <?php else: ?>

            <div style="display: flex;">

                <div style="flex: 0 0 60%;">
                    <form name="edit_restriction" method="post" class="card p-3">
                        <input type="hidden" name="base" value="<?php echo $base; ?>">
                        <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                        <input type="hidden" name="action" value="save">

                        <div class="mb-3">
                            <label for="restriction_field" class="form-label"><?php echo $msgstr["restriction_field"]; ?></label>
                            <select id="restriction_field" name="restriction_field" class="form-select">
                                <option value="">-- <?php echo $msgstr["cfg_select_field"]; ?> --</option>
                                <?php
                                foreach ($fdt_fields as $tag => $name) :
                                    $selected = ($current_config['restriction_field'] == $tag) ? 'selected' : '';
                                    echo "<option value=\"$tag\" $selected>V$tag - " . htmlspecialchars($name) . "</option>\n";
                                endforeach;
                                ?>
                            </select>
                            <div class="form-text"><?php echo $msgstr["restriction_field_desc"]; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="restriction_value" class="form-label"><?php echo $msgstr["restriction_value"]; ?></label>
                            <input type="text" id="restriction_value" name="restriction_value" class="form-control" value="<?php echo htmlspecialchars($current_config['restriction_value']); ?>">
                            <div class="form-text"><?php echo $msgstr["restriction_value_desc"]; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="restriction_type" class="form-label"><?php echo $msgstr["restriction_type"]; ?></label>
                            <select id="restriction_type" name="restriction_type" class="form-select">
                                <option value="hidden" <?php echo ($current_config['restriction_type'] == 'hidden') ? 'selected' : ''; ?>>
                                    <?php echo $msgstr["restriction_type_hidden"]; ?>
                                </option>
                                <option value="authentication" <?php echo ($current_config['restriction_type'] == 'authentication') ? 'selected' : ''; ?>>
                                    <?php echo $msgstr["restriction_type_auth"]; ?>
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="not_restricted_users" class="form-label"><?php echo $msgstr["not_restricted_users"]; ?></label>
                            <input type="text" id="not_restricted_users" name="not_restricted_users" class="form-control" value="<?php echo htmlspecialchars($current_config['not_restricted_users']); ?>">
                            <div class="form-text"><?php echo $msgstr["not_restricted_users_desc"]; ?></div>
                        </div>

                        <br>
                        <button type="submit" class="bt bt-green"><?php echo $msgstr["save"]; ?></button>
                        <a href="procesos_base.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>" class="bt bt-light"><?php echo $msgstr["cancel"]; ?></a>
                    </form>
                </div>
                <div style="flex: 1; padding-left: 20px;">

                    <button type="button" class="accordion">
                        <i class="fas fa-question-circle"></i> <?php echo $msgstr["reference_fdt_fields"]; ?>
                    </button>
                    <div class="panel p-0">
                        <div class="reference-box" style="max-height: 450px;">
                            <strong><?php echo htmlspecialchars($fdt_path); ?></strong>
                            <table class="table striped">
                                <thead>
                                    <tr>
                                        <th>Tag</th>
                                        <th>Nome</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fdt_fields as $tag => $name): ?>
                                        <tr>
                                            <td>V<?php echo $tag; ?></td>
                                            <td><?php echo htmlspecialchars($name); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button type="button" class="accordion" style="margin-top: 10px;">
                        <i class="fas fa-question-circle"></i> <?php echo $msgstr["reference_user_types"]; ?>
                    </button>
                    <div class="panel p-0">
                        <div class="reference-box" style="max-height: 250px;">
                            <?php if (!empty($user_types)): ?>
                                <div class="alert alert-secondary p-2">
                                    <strong><?php echo $msgstr["available_categories"]; ?>:</strong><br>
                                    <small>
                                        <?php
                                        $type_list = [];
                                        foreach ($user_types as $code => $name) {
                                            $type_list[] = "<strong>" . htmlspecialchars($code) . "</strong>: " . htmlspecialchars($name);
                                        }
                                        echo implode('<br>', $type_list);
                                        ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning p-2">
                                    <small><?php echo $msgstr["users_types_not_found"]; // Você precisará adicionar esta msgstr 
                                            ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include("../../common/footer.php"); ?>