<?php
    /*
* @file        db_convert_crunchmf.php
* @author      Roger Craveiro Guilherme
* @date        2026-03-07
* @description Utility to convert database files between Windows and Linux formats using the crunchmf utility.
*
* changes:
* 2026-07-29: Prevents Warning: ZipArchive::extractTo(DB.xrf): Operation failed: Operation not permitted in /utilities/db_convert_crunchmf.php on line 95
*/

    session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    exit;
}

include("../common/get_post.php");
include("../config.php");
include("../lang/dbadmin.php");
include("../lang/admin.php");

// --- Initial Definitions ---
$base = $arrHttp["base"];
$bd_path = $db_path . $base;
$data_path = $bd_path . "/data";
$full_base_path = $data_path . $base;

// Detect Current OS
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$current_os = $is_windows ? "Windows" : "Linux";
$target_suffix = $is_windows ? "_lin" : "_win";
$target_os_label = $is_windows ? "Linux" : "Windows";

// Path to the executable file
$crunchmf = $cisis_path . "crunchmf" . $exe_ext;

// DOWNLOAD (EXPORT)
if (isset($arrHttp["accion"]) && $arrHttp["accion"] == "convert_export") {

    $target_base_name = $base . $target_suffix;
    $target_full_path = $data_path . $target_base_name;

    // Command: crunchmf base base_suffix
    $cmd = "$crunchmf $full_base_path $target_full_path 2>&1";

    exec($cmd, $output, $status);

    if ($status == 0) {
        $zip_filename = $target_base_name . ".zip";
        $zip_path = $data_path . $zip_filename;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Adds generated MST and XRF
            if (file_exists($target_full_path . ".mst")) $zip->addFile($target_full_path . ".mst", $target_base_name . ".mst");
            if (file_exists($target_full_path . ".xrf")) $zip->addFile($target_full_path . ".xrf", $target_base_name . ".xrf");
            $zip->close();

            // Cleans up generated temporary files (.mst and .xrf files)
            @unlink($target_full_path . ".mst");
            @unlink($target_full_path . ".xrf");

            if (file_exists($zip_path)) {
                // Clears any rubbish that PHP has put in the buffer (spaces, errors, html)
                if (ob_get_level()) ob_end_clean();

                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($zip_path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($zip_path));

                readfile($zip_path);

                // Remove o zip do servidor após enviar (opcional, mas recomendável para não encher disco)
                // unlink($zip_path); 

                exit;
            }
        } else {
            $msg_err = $msgstr["conv_so_error"];
        }
    } else {
        $msg_err = $msgstr["conv_so_error"] . ": " . implode("<br>", $output);
    }
}

// UPLOAD (IMPORT) - Processing logic
if (isset($arrHttp["accion"]) && $arrHttp["accion"] == "convert_import") {
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
        $zip_tmp = $_FILES['upload_file']['tmp_name'];
        $zip = new ZipArchive;

        if ($zip->open($zip_tmp) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $target_file = rtrim($data_path, '/\\') . DIRECTORY_SEPARATOR . $filename;

                if (file_exists($target_file)) {
                    @chmod($target_file, 0666); // Attempts to obtain permission to modify
                    @unlink($target_file);      // Remove o arquivo conflitante
                }
            }

            // Performs the extraction and handles any errors that may still occur
            $extract_success = @$zip->extractTo($data_path);
            $zip->close();

            if ($extract_success) {
                $found_files = glob($data_path . "/" . $base . "_*.mst"); 

                if (count($found_files) > 0) {
                    $imported_mst = $found_files[0];
                    $imported_base_root = str_replace(".mst", "", $imported_mst);
                    $imported_xrf = $imported_base_root . ".xrf";

                if (file_exists($imported_xrf)) {
                    $timestamp = date("YmdHis");
                    $bkp_mst = $full_base_path . "_bkp_" . $timestamp . ".mst";
                    $bkp_xrf = $full_base_path . "_bkp_" . $timestamp . ".xrf";

                    if (file_exists($full_base_path . ".mst")) rename($full_base_path . ".mst", $bkp_mst);
                    if (file_exists($full_base_path . ".xrf")) rename($full_base_path . ".xrf", $bkp_xrf);

                    rename($imported_mst, $full_base_path . ".mst");
                    rename($imported_xrf, $full_base_path . ".xrf");

                    $msg_ok = $msgstr["conv_msg_ok"] . ": " . basename($bkp_mst);
                }
                } else {
                    $msg_err = $msgstr["conv_so_xrf_error"];
                }
            } else {
                $msg_err = $msgstr["conv_so_error_sufx"];
            }
        } else {
            $msg_err = $msgstr["conv_so_fail"];
        }
    } else {
        $msg_err = $msgstr["conv_so_upload_error"];
    }
}

include("../common/header.php");
?>

<body>
    <?php include("../common/institutional_info.php"); ?>

    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["conv_so_breadcrumb"]; ?>
        </div>
        <div class="actions">

            <?php
            $backtoscript = "../dbadmin/menu_mantenimiento.php?reinicio=s";
            include "../common/inc_back.php"; ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>

    <div class="middle homepage">
        <div class="mainBox">
            <div class="boxContent">

                <div>
                    <h1><?php echo $msgstr["conv_so_title"] . ": " . $base; ?></h1>
                </div>

                <?php if (isset($msg_err)) echo "<div style='color:red; border:1px solid red; padding:10px; margin:10px 0;'>$msg_err</div>"; ?>
                <?php if (isset($msg_ok)) echo "<div style='color:green; border:1px solid green; padding:10px; margin:10px 0;'>$msg_ok</div>"; ?>

                <div style="padding: 20px; background: #f8f9fa; border: 1px solid #ddd; margin-bottom: 20px;">
                    <h3><?php echo $msgstr["conv_so_stats"]; ?></h3>
                    <ul>
                        <li><strong><?php echo $msgstr["conv_so_server"]; ?>:</strong> <?php echo $current_os; ?></li>
                        <li><strong><?php echo $msgstr["conv_so_path"]; ?>:</strong> <?php echo $full_base_path; ?></li>
                    </ul>
                </div>

                <div style="display:flex; gap:20px; flex-wrap:wrap;">

                    <div style="flex:1; border: 1px solid #ccc; padding:15px; border-radius:5px;">
                        <h3><i class="fas fa-exchange-alt"></i> 1. <?php echo $msgstr["conv_so_export_to"]; ?> <?php echo $target_os_label; ?></h3>
                        <p><?php echo $msgstr["conv_so_msg1"]; ?> <strong><?php echo $target_os_label; ?></strong>.</p>
                        <p><?php echo $msgstr["conv_so_msg2"]; ?> <code><?php echo $target_suffix; ?></code>.</p>

                        <form action="db_convert_crunchmf.php" method="post">
                            <input type="hidden" name="base" value="<?php echo $base; ?>">
                            <input type="hidden" name="accion" value="convert_export">

                            <button type="submit" class="bt-green">
                                <i class="fas fa-cogs"></i> <?php echo $msgstr["conv_so_subzip"]; ?>
                            </button>
                        </form>
                    </div>

                    <div style="flex:1; border: 1px solid #ccc; padding:15px; border-radius:5px;">
                        <h3><i class="fas fa-file-import"></i> 2. <?php echo $msgstr["conv_so_import"]; ?></h3>
                        <p><?php echo $msgstr["conv_so_exp_info"]; ?></p>
                        <p><?php echo $msgstr["conv_so_exp_steps"]; ?>:</p>
                        <ol>
                            <li><?php echo $msgstr["conv_so_exp_steps1"]; ?></li>
                            <li><?php echo $msgstr["conv_so_exp_steps2"]; ?></li>
                            <li><?php echo $msgstr["conv_so_exp_steps3"]; ?></li>
                        </ol>

                        <form action="db_convert_crunchmf.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="base" value="<?php echo $base; ?>">
                            <input type="hidden" name="accion" value="convert_import">

                            <label><?php echo $msgstr["conv_so_exp_select"]; ?> (<code><?php echo $base; ?>_xxx.zip</code>):</label><br>
                            <input type="file" name="upload_file" accept=".zip" required style="margin-bottom:10px;">
                            <br>
                            <button type="submit" class="bt-blue" onclick="return confirm('<?php echo $msgstr["conv_so_alert"]; ?>')">
                                <i class="fas fa-upload"></i> <?php echo $msgstr["conv_so_import_ok"]; ?>
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php include("../common/footer.php"); ?>
</body>

</html>