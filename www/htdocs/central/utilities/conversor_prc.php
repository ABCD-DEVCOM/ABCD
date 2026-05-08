<?php
/*
* @file        conversor_prc.php
* @author      Roger Craveiro Guilherme
* @date        2026-03-20
* @description   PROC Conversion Interface (Post-TXT Migration). This tool automates the process of applying conversion rules using the MX utility and WXIS, allowing users to preview the results before implementing the changes in the main database.
*
* CHANGE LOG:
* 2026-03-21 rogercgui Sets the display of records to the raw format.
* 2026-03-20 rogercgui Initial development of the PRC Conversion Interface.
* 2026-05-08 rogercgui Added a safety lock to limit the preview to a maximum of 50 records at a time to prevent overloading WXIS/the server. Implemented range selection for MFNs in the preview.
* 2026-05-08 rogercgui Improved the user interface for range selection and added clear instructions for users. Updated the commit process to ensure that the temporary database is properly checked before attempting to replace the original.
*/

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    die;
}

include("../common/get_post.php");
include("../config.php");

$lang = $_SESSION["lang"];
include("../lang/admin.php");
include("../lang/soporte.php");

// Configuration of files and directories specific to this database
$base = $arrHttp["base"];
$data_dir = $db_path . $base . "/data/";
$pfts_dir = $db_path . $base . "/data/"; // Caminho do PRC

$db_original = $data_dir . $base;
$db_temp     = $data_dir . $base . "_conv";
$db_backup   = $data_dir . $base . "_ori";
$prc_file    = $pfts_dir . "fix.prc";

$acao = isset($_POST['acao']) ? $_POST['acao'] : '';
$prc_content = isset($_POST['prc_content']) ? $_POST['prc_content'] : '';

// --- Capturing the MFN range ---
$mfn_start = isset($_POST['mfn_start']) ? max(1, intval($_POST['mfn_start'])) : 1;
$mfn_end   = isset($_POST['mfn_end'])   ? max($mfn_start, intval($_POST['mfn_end'])) : 5;

// Safety lock: limits the preview to a maximum of 50 records at a time
// to avoid overloading WXIS/the server
if (($mfn_end - $mfn_start) > 50) {
    $mfn_end = $mfn_start + 50;
}
// ------------------------------------------

if ($acao == '' && file_exists($prc_file)) {
    $prc_content = file_get_contents($prc_file);
}

$output_msg = "";
$preview_before = "";
$preview_after = "";

// ---------------------------------------------------------
// EXECUTION LOGIC
// ---------------------------------------------------------

// SECTION 1: PREVIEW AND TEMP GENERATION ONLY
if ($acao == 'preview') {

    // 1. Save the edited PRC content to the file
    file_put_contents($prc_file, $prc_content);

    // 2. Run MX only to CREATE the temporary database (_conv) using the ABCD global $mx_path
    $cmd_create = escapeshellcmd($mx_path) . " " . escapeshellarg($db_original) . " \"proc=@" . $prc_file . "\" create=" . escapeshellarg($db_temp) . " now -all 2>&1";
    shell_exec($cmd_create);


    $cmd_reorder = escapeshellcmd($mx_path) . " " . escapeshellarg($db_temp) . " \"proc='S'\" copy=" . escapeshellarg($db_temp) . " now -all 2>&1";
    shell_exec($cmd_reorder);

    // 3. Creation of a temporary .par file so that WXIS can read the _conv
    $par_temp_path = $db_path . $actparfolder . $base . "_conv.par";
    $par_content = $base . "_conv.*=" . $data_dir . $base . "_conv.*\n";
    file_put_contents($par_temp_path, $par_content);

    // 4. GENERATING A PREVIEW USING WXIS
    $IsisScript = $xWxis . "buscar.xis";

    // ---- READ ORIGINAL (Before) ----
    $preview_before = "";
    for ($mfn = $mfn_start; $mfn <= $mfn_end; $mfn++) {
        $query = "&base=" . $base . "&cipar=" . $db_path . $actparfolder . $base . ".par&Mfn=" . $mfn . "&Opcion=ver&Formato=ALL";
        $contenido = array();
        include("../common/wxis_llamar.php");

        if (count($contenido) > 0) {
            $preview_before .= "mfn=" . $mfn . "\n";
            foreach ($contenido as $linea) {
                $linea = str_ireplace('<BR>', "", $linea);

                if (trim($linea) == '$$DELETED') {
                    $preview_before .= " [Registro Eliminado]\n";
                } else {
                    $linea_segura = str_replace(['<', '>'], ['&lt;', '&gt;'], $linea);
                    $preview_before .= " " . $linea_segura . "\n";
                }
            }
            $preview_before .= "\n";
        }
    }

    // ---- READ TEMP (After / _conv) ----
    $preview_after = "";
    if (file_exists($db_temp . ".mst")) {
        for ($mfn = $mfn_start; $mfn <= $mfn_end; $mfn++) {
            $query = "&base=" . $base . "_conv&cipar=" . $db_path . $actparfolder . $base . "_conv.par&Mfn=" . $mfn . "&Opcion=ver&Formato=ALL";
            $contenido = array();
            include("../common/wxis_llamar.php");

            if (count($contenido) > 0) {
                $preview_after .= "mfn=" . $mfn . "\n";
                foreach ($contenido as $linea) {
                    $linea = str_ireplace('<BR>', "", $linea);

                    if (trim($linea) == '$$DELETED') {
                        $preview_after .= " [Registro Eliminado]\n";
                    } else {
                        $linea_segura = str_replace(['<', '>'], ['&lt;', '&gt;'], $linea);
                        $preview_after .= " " . $linea_segura . "\n";
                    }
                }
                $preview_after .= "\n";
            }
        }
    } else {
        $preview_after = $msgstr["prc_no_temp"];
    }
}

// SECTION 2: COMMIT ONLY (COMPLETION)
if ($acao == 'commit') {
    // 5. Commit: Backing up and renaming using the MX utility
    if (file_exists($db_temp . ".mst")) {

        // 5.1 Back up the original using MX (mx base copy=base_ori)
        $cmd_backup = escapeshellcmd($mx_path) . " " . escapeshellarg($db_original) . " copy=" . escapeshellarg($db_backup) . " now -all 2>&1";
        shell_exec($cmd_backup);

        // 5.2 Replaces the original with v1 using MX (mx base_conv copy=base)
        $cmd_replace = escapeshellcmd($mx_path) . " " . escapeshellarg($db_temp) . " copy=" . escapeshellarg($db_original) . " now -all 2>&1";
        shell_exec($cmd_replace);

        // 5.3 Delete the _conv temporary files using PHP
        @unlink($db_temp . ".mst");
        @unlink($db_temp . ".xrf");

        $output_msg = "<div style='color:green; padding:15px; border:1px solid green; background:#e8f5e9; margin-bottom:15px; border-radius:4px;'>
                        <strong><i class='fas fa-check-circle'></i> " . $msgstr['prc_success'] . "</strong> " . $msgstr['prc_backup_created'] . " <code>{$base}_ori.mst</code>
                       </div>";
    } else {
        $output_msg = "<div style='color:red; padding:15px; border:1px solid red; background:#ffebee; margin-bottom:15px; border-radius:4px;'>
                        <strong><i class='fas fa-exclamation-triangle'></i> Erro:</strong> A base temporária ({$base}_conv.mst) não foi encontrada para efetivação. Faça o preview novamente.
                       </div>";
    }
}

include("../common/header.php");
$n_wiki_help = "abcd-administration/field_manipulation_with_proc";
?>

<body>
    <?php include("../common/institutional_info.php"); ?>
    <div class="sectionInfo">
        <div class="breadcrumb"><i class="fas fa-code"></i> <?php echo $msgstr['prc_title']; ?> - DB: <?php echo $base; ?></div>
        <div class="actions">
            <?php
            $backtoscript = "../dbadmin/menu_mantenimiento.php";
            include "../common/inc_back.php";
            ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php include "../common/inc_div-helper.php"; ?>
    <div class="middle formContent">
        <div style="padding: 20px;">
            <?php echo $output_msg; ?>

            <form name="form_prc" id="form_prc" method="post" action="conversor_prc.php">
                <input type="hidden" name="base" value="<?php echo $base; ?>">
                <input type="hidden" name="acao" id="acao" value="preview">

                <div style="margin-bottom: 20px;">

                    <p style="font-size: 0.9em; color:#666; margin-top:2px;"><?php echo $msgstr['prc_description']; ?> <code><?php echo $base . "/data/fix.prc"; ?></code></p>
                    <textarea name="prc_content" style="width: 100%; height: 250px; font-family: Consolas, monospace; font-size: 14px; padding: 15px; border: 1px solid #ccc; background: #282c34; color: #abb2bf; border-radius:4px;" spellcheck="false"><?php echo str_replace(['<', '>'], ['&lt;', '&gt;'], $prc_content); ?></textarea>
                </div>

                <div style="text-align: center; margin-bottom: 30px;">
                    <button type="button" class="bt bt-blue" onclick="document.getElementById('acao').value='preview'; document.getElementById('form_prc').submit();">
                        <i class="fas fa-play"></i> <?php echo $msgstr['prc_run_preview']; ?>
                    </button>
                </div>

                <?php if ($acao == 'preview' || $acao == 'commit'): ?>
                    <h5 style="border-bottom: 2px solid #ccc; padding-bottom: 5px;"><i class="fas fa-columns"></i> <?php echo $msgstr['prc_original_db']; ?></h5>

                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">

                        <div style="flex: 1;">
                            <strong style="color:#d32f2f;"><i class="fas fa-database"></i> <?php echo $msgstr['prc_before_conversion']; ?></strong>
                            <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; height: 450px; overflow-y: auto; font-family: Consolas, monospace; white-space: pre-wrap; font-size: 0.9em; border-radius:4px;">
                                <?php echo $preview_before; ?>
                            </div>
                        </div>

                        <div style="flex: 1;">
                            <strong style="color:#388e3c;"><i class="fas fa-database"></i> <?php echo $msgstr['prc_after_conversion'] . " " . $base . "_conv"; ?></strong>
                            <div style="background: #e8f5e9; border: 1px solid #a5d6a7; padding: 15px; height: 450px; overflow-y: auto; font-family: Consolas, monospace; white-space: pre-wrap; font-size: 0.9em; border-radius:4px;">
                                <?php echo $preview_after; ?>
                            </div>
                        </div>

                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <span style="font-weight: bold; color: #444;">
                            <i class="fas fa-search-plus"></i> <?php echo $msgstr['prc_adjust_preview_range']; ?>:
                        </span>

                        <div>
                            <label for="mfn_start" style="margin-right: 5px; color:#555;"><?php echo $msgstr['cg_from']; ?></label>
                            <input type="number" name="mfn_start" id="mfn_start" value="<?php echo $mfn_start; ?>" min="1" style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>

                        <div>
                            <label for="mfn_end" style="margin-right: 5px; color:#555;"><?php echo $msgstr['cg_to']; ?></label>
                            <input type="number" name="mfn_end" id="mfn_end" value="<?php echo $mfn_end; ?>" min="1" style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>

                        <button type="button" class="bt bt-blue" style="padding: 6px 15px;" onclick="document.getElementById('acao').value='preview'; document.getElementById('form_prc').submit();">
                            <i class="fas fa-sync-alt"></i> <?php echo $msgstr['prc_refresh_preview']; ?>
                        </button>
                    </div>
                    <div style="text-align: center; background: #fff3e0; padding: 20px; border: 1px solid #ffcc80; border-radius: 5px;">

                        <div style="text-align: center; background: #fff3e0; padding: 20px; border: 1px solid #ffcc80; border-radius: 5px;">
                            <p style="color: #e65100; margin-top: 0; font-size:1.05em;"><?php echo $msgstr['prc_commit_warning']; ?></p>
                            <button type="button" class="bt bt-green" style="font-size:1.1em; padding:10px 20px;" onclick="if(confirm('<?php echo $msgstr['prc_commit_confirm']; ?>')){ document.getElementById('acao').value='commit'; document.getElementById('form_prc').submit(); }">
                                <i class="fas fa-check-double"></i> <?php echo $msgstr['prc_commit_changes']; ?>
                            </button>
                        </div>
                    <?php endif; ?>

            </form>
        </div>
    </div>

    <?php include("../common/footer.php"); ?>