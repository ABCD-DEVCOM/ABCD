<?php
/*
* view_dic.php
*
* @author Roger C. Guilherme
* @date 2025-10-02
* @description Script to manage the static list (.dic) used in the OPAC for suggestions of similar terms when the user enters a term that does not exist in the database.
* It allows generating the list using the IFKEYS.EXE utility from CISIS, uploading a new list, downloading the existing list, and clearing the list.
* The list is generated based on the prefixes defined in the .ix file and the characters defined in the .lang file.
* The generation process is done in batches via AJAX to avoid timeouts and provide feedback to the user.
*
* CHANGE LOG:
* 2025-10-05 rogercgui Added AJAX batch processing for list generation to avoid timeouts and improve user feedback.
*/
// Starts the output buffer to control what is sent to the browser.
ob_start();

include("conf_opac_top.php");

$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/databases/did-you-mean";

include "../../common/inc_div-helper.php";

// Defines the main variables that will be used in all routes.
$base = isset($_REQUEST["base"]) ? $_REQUEST["base"] : null;
$lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : "pt";
$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : (isset($_REQUEST["diccionario_accion"]) ? $_REQUEST["diccionario_accion"] : "view");

if ($base) {
    $db_path = $_SESSION["db_path"];
    $dic_file_path = $db_path . $base . "/opac/" . $base . ".dic";
    $actparfolder = "par/";
}

// =========================================================================
// START OF API ROUTES (ACTIONS THAT DO NOT GENERATE HTML)
// =========================================================================

// ROUTE 1: PROCESS A BATCH OF TERMS (CALL VIA JAVASCRIPT/AJAX)
if ($action === 'process_batch') {
    if (!$base) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erro fatal: Base de dados não especificada na chamada da API.']);
        exit;
    }

    ob_clean();
    header('Content-Type: application/json');

    $prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : '';
    $letter = isset($_REQUEST['letter']) ? $_REQUEST['letter'] : '';

    $IsisScript = $xWxis . "opac/alfabetico.xis";
    $cipar = $db_path . $actparfolder . $base . ".par";

    $query = "&base=" . $base . "&cipar=" . $cipar . "&Opcion=autoridades&prefijo=" . urlencode($prefix) . "&letra=" . urlencode($letter) . "&posting=ALL&count=999999";

    $batch_terms = array();
    if (file_exists("../../common/wxis_llamar.php")) {
        @include("../../common/wxis_llamar.php");
        if (isset($contenido) && is_array($contenido)) {
            foreach ($contenido as $linha) {
                if (trim($linha) != "" && strpos($linha, '$$$') !== false) {
                    $parts = explode('$$$', $linha);
                    if (isset($parts[1])) {
                        $term_bruto = ($p = strpos($parts[1], '>')) !== false ? trim(substr($parts[1], $p + 1)) : trim($parts[1]);
                        // A filtragem pelo prefixo+letra garante que termos de outras letras não entrem na lista.
                        if (strpos($term_bruto, $prefix . $letter) === 0) {
                            $batch_terms[] = $term_bruto;
                        }
                    }
                }
            }
        }
    }

    echo json_encode(['terms' => $batch_terms]);
    exit;
}


// ROUTE 2: FINISH GENERATION AND SAVE THE FILE (AJAX)
if ($action === 'finalize') {
    ob_clean();

    // Term sanitization function
    function sanitize_term($raw_term)
    {
        $term = trim($raw_term);
        if (preg_match('/^[A-Z]{1,3}_\|?$/', $term)) return '';
        $term = preg_replace('/^([A-Z]{1,3})_\|/', '$1_', $term);
        $term = str_replace(array('(', ')', ';', '|', ':', '/', '.'), '', $term);
        $term_without_prefix = preg_replace('/^[A-Z]{1,3}_/', '', $term);
        if (ctype_digit(str_replace(' ', '', $term_without_prefix))) return '';
        return trim($term);
    }

    $all_terms_json = isset($_POST['terms']) ? $_POST['terms'] : '[]';
    $all_terms = json_decode($all_terms_json, true);
    $sanitized_terms = [];

    if (is_array($all_terms)) {
        foreach ($all_terms as $term) {
            $clean = sanitize_term($term);
            if (!empty($clean)) {
                $sanitized_terms[] = mb_strtoupper($clean, 'UTF-8');
            }
        }
    }

    $final_terms_list = array_unique($sanitized_terms);
    sort($final_terms_list);
    $final_content = implode("\n", $final_terms_list);

    file_put_contents($dic_file_path, $final_content);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'file' => $dic_file_path, 'count' => count($final_terms_list)]);
    exit;
}


// ROUTE 3: FILE DOWNLOAD
if ($action === "baixar") {
    if (file_exists($dic_file_path)) {
        ob_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($dic_file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($dic_file_path));
        readfile($dic_file_path);
        exit;
    } else {
        $_SESSION['dict_message'] = "<div class='alert error'>Arquivo não encontrado para download.</div>";
        header("Location: view_dic.php?base=$base&lang=$lang");
        exit;
    }
}

// =========================================================================
// END OF API ROUTES. FROM HERE ON, THE SCRIPT RENDERS THE HTML PAGE.
// =========================================================================

if (!$base) {
    die("Erro fatal: The database was not specified for viewing the page..");
}


$mensagem = "";
if (isset($_SESSION['dict_message'])) {
    $mensagem = $_SESSION['dict_message'];
    unset($_SESSION['dict_message']);
}

// UPLOAD LOGIC
if (isset($_FILES['dic_file']) && $_FILES['dic_file']['error'] == 0) {
    $allowed_extensions = ['dic'];
    $file_extension = pathinfo($_FILES['dic_file']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($file_extension), $allowed_extensions)) {
        if (move_uploaded_file($_FILES['dic_file']['tmp_name'], $dic_file_path)) {
            $mensagem = "<div class='alert success'>" . $msgstr["dict_uploaded"] . ": " . htmlspecialchars($dic_file_path) . "</div>";
        } else {
            $mensagem = "<div class='alert error'>" . $msgstr["dict_upload_error"] . "</div>";
        }
    } else {
        $mensagem = "<div class='alert error'>" . $msgstr["dict_invalid_file"] . "</div>";
    }
}

// LOGIC FOR CLEANING ACTION
if ($action === "limpar") {
    if (file_exists($dic_file_path)) {
        unlink($dic_file_path);
        $mensagem = "<div class='alert success'>" . $msgstr["dict_cleared"] . "</div>";
    }
}

// READ THE CONTENTS OF THE DICTIONARY FOR DISPLAY IN THE TEXT AREA
$dictionary_content = file_exists($dic_file_path) ? file_get_contents($dic_file_path) : "";

// Busca os caracteres dos alfabetos configurados
$dynamic_alphabet = get_dictionary_characters($db_path, $base, $lang, $charset);

// Search for file prefixes .ix
$ix_file_path = $db_path . $base . "/opac/$lang/" . $base . ".ix";
$prefixes = [];
if (file_exists($ix_file_path)) {
    foreach (file($ix_file_path) as $line) {
        $parts = explode('|', trim($line));
        if (isset($parts[1]) && !empty(trim($parts[1]))) $prefixes[] = trim($parts[1]);
    }
    $prefixes = array_unique($prefixes);
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
        <h3><?php echo (isset($msgstr["cfg_list_management"]) ? $msgstr["cfg_list_management"] : "Static list management") . ": " . htmlspecialchars($base); ?></h3>
        <p><?php echo $msgstr['cfg_list_explanation']; ?></p>
        <?php if ($mensagem) echo $mensagem; ?>

        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ccc;">
            <h4><?php echo $msgstr['cfg_actions']; ?></h4>
            <button id="start_generation" class="bt bt-green"><?php echo $msgstr['dict_generate_slow']; ?></button>
            <a href="processar_ifkeys.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>" class="bt bt-green"><?php echo $msgstr["dict_generate_fast"]; ?></a>
            <a href="view_dic.php?base=<?php echo htmlspecialchars($base); ?>&lang=<?php echo htmlspecialchars($lang); ?>&diccionario_accion=limpar" class="bt bt-red"><?php echo $msgstr["cfg_clear_list"]; ?></a>
            <a href="view_dic.php?base=<?php echo htmlspecialchars($base); ?>&lang=<?php echo htmlspecialchars($lang); ?>&diccionario_accion=baixar" class="bt bt-gray"><?php echo $msgstr["cfg_download_list"]; ?></a>
        </div>

        <div id="progress_section" style="display: none; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ccc;">
            <h4><?php echo $msgstr['cfg_generate_list']; ?></h4>
            <div id="status_message" style="font-family: monospace; margin-bottom: 10px;"></div>
            <div style="border: 1px solid #ccc; width: 100%;">
                <div id="progress_bar" style="width: 0%; height: 24px; background-color: #4CAF50; text-align: center; color: white;">0%</div>
            </div>
            <pre id="log_output" style="height: 200px; overflow-y: scroll; background-color: #333; color: #fff; padding: 10px; margin-top: 10px;"></pre>
        </div>

        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ccc;">
            <h4><?php echo $msgstr['cfg_upload_list']; ?></h4>
            <form method="POST" enctype="multipart/form-data" action="view_dic.php">
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($base); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
                <input type="file" name="dic_file" accept=".dic">
                <button type="submit" class="bt bt-blue"><?php echo $msgstr['send']; ?></button>
            </form>
        </div>

        <div>
            <h4><?php echo $msgstr['cfg_list_contents']; ?> (<?php echo htmlspecialchars($dic_file_path); ?>)</h4>
            <textarea readonly style="width: 80%; height: 400px; font-family: monospace; font-size: 12px; background-color: #f4f4f4; border: 1px solid #ccc;"><?php echo htmlspecialchars($dictionary_content); ?></textarea>
        </div>
    </div>
</div>

<script>
    document.getElementById('start_generation').addEventListener('click', function() {
        this.disabled = true;
        this.innerText = "Processando...";
        document.getElementById('progress_section').style.display = 'block';

        const prefixes = <?php echo json_encode($prefixes); ?>;
        const alphabet = <?php echo json_encode($dynamic_alphabet); ?>;
        const total_tasks = prefixes.length * alphabet.length;
        let tasks_completed = 0;
        let all_terms = [];
        const log_output = document.getElementById('log_output');

        function update_progress() {
            tasks_completed++;
            let percentage = (total_tasks > 0) ? Math.round((tasks_completed / total_tasks) * 100) : 0;
            document.getElementById('progress_bar').style.width = percentage + '%';
            document.getElementById('progress_bar').innerText = percentage + '%';
        }

        function log(message) {
            log_output.innerHTML += message + '\n';
            log_output.scrollTop = log_output.scrollHeight;
        }

        async function process_task(prefix, letter) {
            document.getElementById('status_message').innerText = `Processando prefixo ${prefix} / ${letter}...`;
            try {
                const response = await fetch(`view_dic.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>&action=process_batch&prefix=${encodeURIComponent(prefix)}&letter=${encodeURIComponent(letter)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.terms && data.terms.length > 0) {
                    all_terms = all_terms.concat(data.terms);
                    log(`[${prefix}${letter}] - Terms found: ${data.terms.length}`);
                } else {
                    log(`[${prefix}${letter}] - No terms found.`);
                }
            } catch (error) {
                log(`ERRO [${prefix}${letter}]: ${error.message}`);
            }
            update_progress();
        }

        async function run_all_tasks() {
            log("Iniciando geração...");
            if (total_tasks === 0) {
                log("No prefix or alphabet configured. Check the .ix and .lang files.");
                finalize_generation();
                return;
            }
            for (const prefix of prefixes) {
                for (const letter of alphabet) {
                    await process_task(prefix, letter);
                }
            }
            finalize_generation();
        }

        async function finalize_generation() {
            document.getElementById('status_message').innerText = "Finalizando e salvando arquivo...";
            log("Sanitizing, removing duplicates, and saving...");

            const formData = new FormData();
            formData.append('terms', JSON.stringify(all_terms));

            const final_response = await fetch(`view_dic.php?base=<?php echo $base; ?>&lang=<?php echo $lang; ?>&action=finalize`, {
                method: 'POST',
                body: formData
            });
            const final_data = await final_response.json();

            if (final_data.status === 'success') {
                log(`\nPROCESS COMPLETED!`);
                log(`File saved in: ${final_data.file}`);
                log(`Total unique terms: ${final_data.count}`);
                document.getElementById('status_message').innerText = "Completed!";
                document.getElementById('start_generation').style.display = 'none';
                setTimeout(() => window.location.reload(), 2000);
            } else {
                log("Error saving the final file: " + (final_data.error || 'Unknown error'));
            }
        }

        run_all_tasks();
    });
</script>

<?php
ob_end_flush();
include("../../common/footer.php");
?>