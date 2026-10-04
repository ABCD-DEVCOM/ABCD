<?php

/**
 * @program:   ABCD - Central Utilities
 * @file:      sync_copies_to_loanobjects.php
 * @desc:      Smart batch synchronizer that creates or updates loanobjects records
 *             from the copies database, ensuring no duplicates are generated.
 *             Implements a Memory-Map Delta Sync for high performance.
 *             Dynamically reads items.tab to enforce Loan Object Types (^o).
 * @author:    Roger Craveiro Guilherme 
 * @since:     2026-09-01
 */

session_start();
if (!isset($_SESSION["permiso"])) {
    header("Location: ../common/error_page.php");
    exit;
}

set_time_limit(0);

include("../common/get_post.php");
include("../config.php");
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../common/header.php");

$lang = $_SESSION["lang"] ?? 'en';
$lang_db = $def["DEFAULT_DBLANG"] ?? $lang;
$base_ant = $arrHttp["base"] ?? '';
$backtoscript = "../dbadmin/menu_mantenimiento.php";

// ----------------------------------------------------------------------
// READ items.tab FOR LOAN OBJECT TYPES
// ----------------------------------------------------------------------
$items_tab_path = $db_path . "circulation/def/" . $lang . "/items.tab";
if (!file_exists($items_tab_path)) {
    $items_tab_path = $db_path . "circulation/def/" . $lang_db . "/items.tab";
}
if (!file_exists($items_tab_path)) {
    $items_tab_path = $db_path . "circulation/def/en/items.tab"; // Ultimate fallback
}

$item_types = [];
if (file_exists($items_tab_path)) {
    $lines = file($items_tab_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 2) {
            $item_types[trim($parts[0])] = trim($parts[1]);
        }
    }
}

echo "<body onunload='if(typeof win !== \"undefined\") win.close()'>\n";
if (isset($arrHttp["encabezado"])) {
    include("../common/institutional_info.php");
}
?>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo ($msgstr["sync_copies_to_lo_title"] ?? "Sync Copies to Loan Objects") . ": " . htmlspecialchars($base_ant); ?>
    </div>
    <div class="actions">
        <?php include "../common/inc_back.php"; ?>
    </div>
    <div class="spacer">&#160;</div>
</div>

<?php include "../common/inc_div-helper.php"; ?>

<div class="middle form">
    <div class="formContent">
        <div align="center">
            <h3><?php echo $msgstr["sync_copies_to_lo_heading"] ?? "Smart Batch Synchronization: Copies &rarr; Loan Objects"; ?></h3>
        </div>

        <?php
        if (!isset($arrHttp["execute"])) {
            // --- UI: Confirmation & Configuration Form ---
        ?>
            <form action="" method="post" name="form1" id="form1">
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($base_ant); ?>" />
                <input type="hidden" name="execute" value="1" />
                <?php if (isset($arrHttp["encabezado"])) echo "<input type='hidden' name='encabezado' value='s' />"; ?>

                <div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">
                    <p style="text-align:justify; margin-bottom: 20px;">
                        <?php echo $msgstr["sync_copies_to_lo_desc"] ?? "This script will scan the <strong>copies</strong> database and synchronize it with <strong>loanobjects</strong>. It safely maps existing inventory numbers and injects only missing items (Delta Sync) via the <code>959</code> tag, preventing duplicates."; ?>
                    </p>

                    <div style="margin-bottom: 15px;">
                        <label for="default_item_type" style="font-weight: bold; display: block; margin-bottom: 5px;">
                            <i class="fas fa-tags"></i> <?php echo $msgstr["sync_copies_to_lo_default_type"] ?? "Default Object Type (Subfield ^o):"; ?>
                        </label>
                        <select name="default_item_type" id="default_item_type" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value=""><?php echo $msgstr["sync_copies_to_lo_keep_v50"] ?? "-- Keep from Copies DB (v50) or Leave Empty --"; ?></option>
                            <?php foreach ($item_types as $code => $desc): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>">
                                    <?php echo htmlspecialchars($desc); ?> (<?php echo htmlspecialchars($code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <?php echo $msgstr["sync_copies_to_lo_default_help"] ?? "If a copy record lacks the 'Type of object' field (v50), this default will be applied to the <code>^o</code> subfield to ensure Loan Policies work correctly."; ?>
                        </small>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="force_default_type" value="1">
                            <span style="font-weight: bold; color: #d32f2f;"><?php echo $msgstr["sync_copies_to_lo_force_type"] ?? "Force default type on ALL synced items"; ?></span>
                        </label>
                        <small style="color: #666; margin-left: 25px; display: block;">
                            <?php echo $msgstr["sync_copies_to_lo_force_help"] ?? "Check this to overwrite the existing type (v50) in Copies and force the selected type above."; ?>
                        </small>
                    </div>
                </div>

                <div style="text-align:center; margin-top: 20px;">
                    <button type="submit" class="bt bt-green" style="font-size: 1.1em; padding: 10px 20px;">
                        <i class="fas fa-sync"></i> <?php echo $msgstr["sync_copies_to_lo_start"] ?? "Start Synchronization"; ?>
                    </button>
                </div>
            </form>
        <?php
        } else {
            // --- EXECUTION LOGIC ---
            echo "<h4 style='text-align:center;'><i class='fas fa-cog fa-spin'></i> " . ($msgstr["sync_copies_to_lo_processing"] ?? "Processing Synchronization...") . "</h4>";
            echo "<div style='background:#f4f4f4; padding:15px; border:1px solid #ccc; height:350px; overflow-y:auto; font-family:monospace; margin: 0 20px;'>";

            ob_flush();
            flush();

            $default_item_type = $_POST['default_item_type'] ?? '';
            $force_default_type = isset($_POST['force_default_type']) && $_POST['force_default_type'] == '1';

            // 1. Memory Map: Existing Loan Objects
            $loan_mfn_map = [];
            $loan_inv_map = [];

            if (file_exists($db_path . "loanobjects/data/loanobjects.mst")) {
                echo ($msgstr["sync_copies_to_lo_reading_lo"] ?? "Reading existing loanobjects database...") . "<br>";
                ob_flush();
                flush();

                $loan_temp_lst = $db_path . "wrk/lo_sync_extract.txt";
                $pft_lo = "mfn,'|',v10,'_',v1,'|',(v959^i,';')/";
                $mx_lo_cmd = escapeshellcmd($mx_path) . " " . escapeshellarg($db_path . "loanobjects/data/loanobjects") . " \"pft=$pft_lo\" now -all > " . escapeshellarg($loan_temp_lst);

                exec($mx_lo_cmd, $out_lo, $status_lo);

                if (file_exists($loan_temp_lst)) {
                    $lines_lo = file($loan_temp_lst, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines_lo as $line) {
                        $parts = explode('|', $line);
                        if (count($parts) >= 3) {
                            $mfn = trim($parts[0]);
                            $key = trim($parts[1]);
                            $invs = explode(';', trim($parts[2]));

                            $loan_mfn_map[$key] = $mfn;
                            foreach ($invs as $inv) {
                                if ($inv !== '') {
                                    $loan_inv_map[$key][$inv] = true;
                                }
                            }
                        }
                    }
                    unlink($loan_temp_lst);
                }
            }

            // 2. Memory Map: Extract Copies
            echo ($msgstr["sync_copies_to_lo_reading_cp"] ?? "Reading copies database...") . "<br>";
            ob_flush();
            flush();

            $copies_temp_lst = $db_path . "wrk/copies_sync_extract.txt";
            $pft_cp = "mfn,'|',v10,'_',v1,'|',v30,'|',v35,'|',v40,'|',v50,'|',v10,'|',v1/";
            $mx_cp_cmd = escapeshellcmd($mx_path) . " " . escapeshellarg($db_path . "copies/data/copies") . " \"pft=$pft_cp\" now -all > " . escapeshellarg($copies_temp_lst);

            exec($mx_cp_cmd, $out_cp, $status_cp);

            if ($status_cp !== 0 || !file_exists($copies_temp_lst)) {
                echo "<span style='color:red;'>" . ($msgstr["sync_copies_to_lo_error_mx"] ?? "Error executing MX on copies database. Operation aborted.") . "</span></div>";
                exit;
            }

            $grouped_copies = [];
            $lines_cp = file($copies_temp_lst, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines_cp as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 8) {
                    $key  = trim($parts[1]);
                    $inv  = trim($parts[2]);
                    $ml   = trim($parts[3]);
                    $bl   = trim($parts[4]);
                    $type = trim($parts[5]);
                    $db   = trim($parts[6]);
                    $cn   = trim($parts[7]);

                    if (empty($key) || empty($inv) || empty($cn) || empty($db)) continue;

                    if (!isset($grouped_copies[$key])) {
                        $grouped_copies[$key] = [
                            'db' => $db,
                            'cn' => $cn,
                            'items' => []
                        ];
                    }

                    if ($force_default_type && $default_item_type !== '') {
                        $type = $default_item_type;
                    } elseif (empty($type) && $default_item_type !== '') {
                        $type = $default_item_type;
                    }

                    $tag959 = "^i" . $inv;
                    if (!empty($type)) $tag959 .= "^o" . $type;
                    if (!empty($ml))   $tag959 .= "^l" . $ml;
                    if (!empty($bl))   $tag959 .= "^b" . $bl;

                    $grouped_copies[$key]['items'][$inv] = $tag959;
                }
            }
            unlink($copies_temp_lst);

            // 3. Process the Delta (Differences) and Update via WXIS
            echo ($msgstr["sync_copies_to_lo_calculating"] ?? "Calculating deltas and injecting to loanobjects...") . "<br><br>";
            ob_flush();
            flush();

            $created_count = 0;
            $updated_count = 0;
            $skipped_count = 0;

            foreach ($grouped_copies as $key => $data) {
                $valortag = "";
                $items_added = 0;
                $cn_db = $data['db'];
                $cn_val = $data['cn'];

                if (isset($loan_mfn_map[$key])) {
                    $mfn = $loan_mfn_map[$key];
                    $existing_invs = $loan_inv_map[$key] ?? [];

                    foreach ($data['items'] as $inv => $tag959) {
                        if (!isset($existing_invs[$inv])) {
                            $valortag .= "<959 0>" . $tag959 . "</959>";
                            $items_added++;
                        }
                    }

                    if ($items_added > 0) {
                        $opcion_update = "actualizar";
                        $target_mfn = $mfn;
                        $update_msg = sprintf($msgstr["sync_copies_to_lo_updating"] ?? "Updating MFN %s (Appending %s copies) for Title: %s", $target_mfn, $items_added, $key);
                        echo "<span style='color:blue;'>{$update_msg}</span><br>";
                        $updated_count++;
                    } else {
                        $skipped_count++;
                    }
                } else {
                    $valortag .= "<1 0>" . $cn_val . "</1>";
                    $valortag .= "<10 0>" . $cn_db . "</10>";
                    foreach ($data['items'] as $inv => $tag959) {
                        $valortag .= "<959 0>" . $tag959 . "</959>";
                        $items_added++;
                    }

                    if ($items_added > 0) {
                        $opcion_update = "crear";
                        $target_mfn = "New";
                        $create_msg = sprintf($msgstr["sync_copies_to_lo_creating"] ?? "Created new record for Title: %s (%s copies)", $key, $items_added);
                        echo "<span style='color:green;'>{$create_msg}</span><br>";
                        $created_count++;
                    }
                }

                if ($items_added > 0) {
                    $contenido = [];
                    $err_wxis = "";
                    $IsisScript = $xWxis . "actualizar.xis";
                    $query = "&base=loanobjects&cipar=" . $db_path . $actparfolder . "loanobjects.par&login=" . urlencode($_SESSION["login"]) . "&Mfn=" . $target_mfn . "&Opcion=" . $opcion_update . "&ValorCapturado=" . urlencode($valortag);

                    include("../common/wxis_llamar.php");

                    if ($err_wxis !== "") {
                        $err_msg = sprintf($msgstr["sync_copies_to_lo_error_wxis"] ?? "WXIS failed on record %s: %s", $key, $err_wxis);
                        echo "<span style='color:red;'>{$err_msg}</span><br>";
                    }
                    ob_flush();
                    flush();
                }
            }

            echo "</div><br>";

            // --- FINAL REPORT ---
            echo "<div style='text-align:center; padding: 15px; background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 5px; margin: 0 20px;'>";
            echo "<h3 style='margin-top: 0; color: #2e7d32;'><i class='fas fa-check-circle'></i> " . ($msgstr["sync_copies_to_lo_complete"] ?? "Synchronization Complete") . "</h3>";
            echo ($msgstr["sync_copies_to_lo_untouched"] ?? "Untouched titles (already in sync):") . " <strong>$skipped_count</strong><br>";
            echo ($msgstr["sync_copies_to_lo_new_created"] ?? "New Loan Objects created:") . " <strong style='color:green;'>$created_count</strong><br>";
            echo ($msgstr["sync_copies_to_lo_updated_merge"] ?? "Existing Loan Objects updated (Merge):") . " <strong style='color:blue;'>$updated_count</strong><br>";
            echo "</div>";
        }
        ?>
    </div>
</div>
<?php include("../common/footer.php"); ?>