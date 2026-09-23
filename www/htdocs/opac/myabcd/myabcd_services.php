<?php
/*
 * MYABCD_SERVICES.PHP
 * v3.0 - CENTRALISED LOGIC (No duplication)
 * - Fatal error regarding inclusion paths fixed (chdir removed).
 * - The booking logic is split into opac_CheckBooking() and opac_SaveBooking().
 */

// Go up two levels (myabcd -> opac -> htdocs)
include_once(dirname(__FILE__) . "/../../central/config_opac.php");
include_once(dirname(__FILE__) . "/../../central/config.php");

// Go up one level (myabcd -> opac)
include_once(dirname(__FILE__) . "/../functions.php");
include_once(dirname(__FILE__) . "/../functions/send_mails.php");

// Same level (myabcd)
include_once(dirname(__FILE__) . "/my-functions.php");
// --- END OF CORRECTION ---

/**
 * Global User Validation Function
 * (This function is correct)
 */
function opac_VerificarStatusUsuario($user_id)
{
    global $db_path, $cisis_path, $msgstr, $converter_path; // Set $converter_path

    // Ensure that $converter_path is defined (this function can be called before my-functions)
    if (!isset($converter_path)) $converter_path = $cisis_path . "mx";

    $today = date("Ymd");

    // 1. Check Active Suspensions
    $mxs = $converter_path . " " . $db_path . "suspml/data/suspml \"pft=if v20='" . $user_id . "' then if v1='S' and v10='0' then if '" . $today . "'>=v30 and '" . $today . "'<=v60 then 'SUSPENSO' fi, fi fi\" now";
    exec($mxs, $out_s);
    if (!empty($out_s) && isset($out_s[0]) && trim($out_s[0]) == 'SUSPENSO') {
        return ['status' => 'error', 'message' => ($msgstr["user_suspended"] ?? "User suspended.")];
    }

    // 2. Check for Outstanding Fines
    $mxm = $converter_path . " " . $db_path . "suspml/data/suspml \"pft=if v20='" . $user_id . "' then if v1='M' and v10='0' then 'MULTA' fi, fi\" now";
    exec($mxm, $out_m);
    if (!empty($out_m) && isset($out_m[0]) && trim($out_m[0]) == 'MULTA') {
        return ['status' => 'error', 'message' => ($msgstr["user_fined"] ?? "User with outstanding fines.")];
    }

    // 3. Check Overdue Loans
    $mxl = $converter_path . " " . $db_path . "trans/data/trans \"pft=if v20='" . $user_id . "' then if v1='P' then v40, fi fi\" now";
    exec($mxl, $out_l);
    foreach ($out_l as $data_devolucao) {
        $data_devolucao = trim(substr($data_devolucao, 0, 8)); // Gets YYYYMMDD
        if ($data_devolucao != "" && $today > $data_devolucao) {
            return ['status' => 'error', 'message' => ($msgstr["loanoverduer"] ?? "The user has overdue loans.")];
        }
    }

    return ['status' => 'success', 'message' => 'OK'];
}


/**
 * NEW VALIDATION FUNCTION (STEP 1)
 * Performs all validations (User, Policy, Limit, Item Details, Duplicates, Availability).
 * Does not save anything. Simply returns the data if everything is OK.
 */
function opac_VerificarReserva($user_id, $user_type, $item_mfn, $item_base)
{
    global $db_path, $cisis_path, $lang, $msgstr, $xWxis, $actparfolder, $converter_path;

    // (This $debug_log variable is defined in "prepare_reservation.php")
    global $debug_log;
    if (!isset($debug_log)) $debug_log = [];

    $debug_log[] = "--- Start of opac_VerificarReserva ---";

    // [Validation 1] User status
    $status_usuario = opac_VerificarStatusUsuario($user_id);
    if ($status_usuario['status'] == 'error') {
        throw new Exception($status_usuario['message']);
    }
    $debug_log[] = "[Val 1] User Status: OK";

    // [Validation 2] Booking Policy
    $regras_usuario = [];
    $tab_path = $db_path . "circulation/def/" . $lang . "/typeofitems.tab";
    $debug_log[] = "Path to .tab: $tab_path";

    if (file_exists($tab_path)) {
        $fp = file($tab_path);
        foreach ($fp as $line) {
            $parts = explode('|', trim($line));
            if (isset($parts[1]) && trim($parts[1]) == trim($user_type)) {
                $regras_usuario['can_reserve'] = $parts[11] ?? 'N';
                $regras_usuario['reserve_limit'] = 10;
                $debug_log[] = "Rule found: Can reserve? " . $regras_usuario['can_reserve'] . ", Limit: " . $regras_usuario['reserve_limit'];
                break;
            }
        }
    }
    if (empty($regras_usuario) || $regras_usuario['can_reserve'] != 'Y') {
        throw new Exception($msgstr["err_reserve_not_allowed"] ?? "Your user type is not authorised to make a booking.");
    }

    $debug_log[] = "[Val 2] Reservation Policy: OK";

    $dataarr = getUserStatus();
    $total_reservas_atuais = count($dataarr["waits"] ?? []);

    $debug_log[] = "Total Current Reservations: $total_reservas_atuais";

    if ($total_reservas_atuais >= $regras_usuario['reserve_limit']) {
        throw new Exception($msgstr["err_reserve_limit_exceeded"] ?? "Your reservation limit has been exceeded.");
    }
    $debug_log[] = "[Val 3] Reservation Limit: OK";

    // [Validation 4] Get Item Data (CN and Title)
    $control_number = "";
    $item_title = "";
    $mx_cn_cmd = $converter_path . " " . $db_path . $item_base . "/data/" . $item_base . " from=" . $item_mfn . " count=1 \"pft=v1\" now";
    exec($mx_cn_cmd, $out_cn);
    if (!empty($out_cn)) $control_number = trim(implode("", $out_cn));

    if (empty($control_number)) {
        throw new Exception($msgstr["err_item_not_found"] ?? "Item not found or without Control Number (v1).");
    }

    $pft_loans = $db_path . $item_base . "/loans/" . $lang . "/loans_display.pft";
    if (!file_exists($pft_loans)) $pft_loans = $db_path . $item_base . "/loans/" . ($_SESSION['lang'] ?? 'en') . "/loans_display.pft";

    if (file_exists($pft_loans)) {
        $mx_tit_cmd = $converter_path . " " . $db_path . $item_base . "/data/" . $item_base . " from=" . $item_mfn . " count=1 \"pft=@" . $pft_loans . "\" now";
        exec($mx_tit_cmd, $out_tit);
        foreach ($out_tit as $line) $item_title .= trim($line) . " ";
        $item_title = trim($item_title);
    } else {
        $item_title = "(Title not available)";
    }
    $debug_log[] = "[Val 4] Item Data: OK (CN: $control_number)";

    if (!mb_check_encoding($item_title, 'UTF-8')) {
        $item_title = mb_convert_encoding($item_title, 'UTF-8', 'ISO-8859-1');
        $debug_log[] = "[Val 4] Title converted to UTF-8.";
    }

    // [Validation 5a] Duplication
    $mx_dup_cmd = $converter_path . " " . $db_path . "reserve/data/reserve \"pft=if v10='" . $user_id . "' and v20='" . $control_number . "' and v1='0' then 'DUPLICADO' fi\" now";
    exec($mx_dup_cmd, $out_dup);
    foreach ($out_dup as $line_dup) {
        if (trim($line_dup) == 'DUPLICADO') {
            throw new Exception($msgstr["err_reserve_duplicated"] ?? "You already have an active reservation for this item.");
        }
    }
    $debug_log[] = "[Val 5a] Duplication: OK";

    // [Validation 5b] Is the item available? (Check.xis)
    $cipar_biblio = $db_path . $actparfolder . "reserve.par";
    $IsisScript_Check = $xWxis . "opac/reserve_update.xis";
    $query_check = "&base=reserve&cipar=$cipar_biblio&ControlNumber=" . urlencode($control_number) . "&UserCode=" . urlencode($user_id);

    $debug_log[] = "WXIS Call (Check.xis): $query_check";
    $result_check = wxisLlamar($item_base, $query_check, $IsisScript_Check);
    $check_response = implode("", $result_check);
    $debug_log[] = "WXIS Result (Check.xis): $check_response";

    // --- START OF CORRECTION ---
    $check_response_trim = trim($check_response);
    if (substr($check_response_trim, 0, 7) == "[ERROR]") {

        $error_message_raw = trim(substr($check_response_trim, 7));

        // Intercepts the specific "already reserved" error message
        if (
            strpos($error_message_raw, "reserva para este") !== false || // "Já existe uma reserva para este título" (pt)
            strpos($error_message_raw, "reserva para este") !== false || // "Ya existe una reserva para este título" (es)
            strpos($error_message_raw, "already reserved") !== false    // "This title is already reserved" (en)
        ) {
            // Throws our own user-friendly exception
            throw new Exception($msgstr["err_item_already_reserved"] ?? "This item has already been reserved by another user and is currently unavailable.");
        } else {
            // If it is another .xis error, throws the raw error
            throw new Exception($error_message_raw);
        }
    }
    // --- END OF CORRECTION ---
    $debug_log[] = "[Validation 5b] Availability: OK";

    // If all checks pass, returns the data for the modal
    return [
        'title' => $item_title,
        'control_number' => $control_number
    ];
}


/**
 * NEW SAVING FUNCTION (STEP 2)
 * Only executes the final MX command. Trusts that validation has already been done.
 */
function opac_GravarReserva($user_id, $user_type, $user_name, $item_base, $control_number, $item_title, $dias_espera)
{
    global $db_path, $cisis_path, $msgstr, $converter_path;

    $today = date("Ymd");
    $time = date("h:i:s");

    // Adds v41 (User limit date)
    $proc_v41 = "";
    if (!empty($dias_espera) && is_numeric($dias_espera)) {
        $proc_v41 = "<41>" . $dias_espera . "</41>";
    }

    // 1. Saves to Master (MST/XRF)
    $mxa_cmd = $converter_path . " null \"proc='<1>0</1><10>" . $user_id . "</10><12>" . $user_type . "</12><15>" . $item_base . "</15><20>" . $control_number . "</20><30>" . $today . "</30><31>" . $time . "</31>" . $proc_v41 . "<50>" . $item_title . "</50><51>" . $user_name . "</51>'\" append=" . $db_path . "reserve/data/reserve count=1 now";
    exec($mxa_cmd, $out_update, $banderamx);

    if ($banderamx != 0) {
        throw new Exception($msgstr["err_reserve_failed"] ?? "Failure to save the booking (MX return code: $banderamx).");
    }

    // 2. TRIGGER: Updates the Inverted Index (IFP) for the 'reserve' database
    $base_res  = $db_path . "reserve/data/reserve";
    $fst_res   = $db_path . "reserve/data/reserve.fst";
    $actab_res = $db_path . "isisac.tab";
    $uctab_res = $db_path . "isisuc.tab";

    // Resolves the reserve cipar by copying the secure technique
    $par_original = $db_path . "par/reserve.par";
    $cipar_seguro = $par_original;
    if (file_exists($par_original)) {
        $par_content = file_get_contents($par_original);
        if (strpos($par_content, '%path_database%') !== false) {
            $par_content = str_replace("%path_database%", $db_path, $par_content);
            $cipar_seguro = $db_path . "wrk/temp_reserve_async.par";
            if (!is_dir($db_path . "wrk")) @mkdir($db_path . "wrk", 0777, true);
            file_put_contents($cipar_seguro, $par_content);
        }
    }

    // Executes fullinv asynchronously to avoid delaying the user response
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd_inv = "start /B \"\" \"$converter_path\" cipar=\"$cipar_seguro\" db=\"$base_res\" fst=@\"$fst_res\" actab=\"$actab_res\" uctab=\"$uctab_res\" fullinv=\"$base_res\" -all now 2>&1";
        pclose(popen($cmd_inv, "r"));
    } else {
        $cmd_inv = "\"$converter_path\" cipar=\"$cipar_seguro\" db=\"$base_res\" fst=@\"$fst_res\" actab=\"$actab_res\" uctab=\"$uctab_res\" fullinv=\"$base_res\" -all now > /dev/null 2>&1 &";
        exec($cmd_inv);
    }

    opac_AtualizarIndiceReserva_Async();

    return ['status' => 'success', 'message' => $msgstr["reserve_success"] ?? "Booking confirmed!"];
}


// -------------------------------------------------------------------
// OLD FUNCTIONS (RENEW, CANCEL) - THESE WERE CORRECT AND REMAIN UNCHANGED
// -------------------------------------------------------------------

function opac_RenovarEmprestimo($loan_id_mfn, $user_id, $user_type, $copy_type)
{
    global $db_path, $cisis_path, $lang, $msgstr, $converter_path;
    $today = date("Ymd");
    $time = date("h:i:s");

    try {
        // --- Validation 1: User Status (OK) ---
        $status_usuario = opac_VerificarStatusUsuario($user_id);
        if ($status_usuario['status'] == 'error') {
            throw new Exception($status_usuario['message']);
        }


        // [Validation 2] Loan Policy (Reading like in Reservations)
        $LoanPolicy = "";
        $fp = file($db_path . "circulation/def/" . $lang . "/typeofitems.tab");

        // Logic for reading policy EQUAL to opac_VerificarReserva
        foreach ($fp as $value) {
            $val = explode('|', $value);

            // Compares Only Column 2 (user_type)
            if (isset($val[1]) && trim($val[1]) == trim($user_type)) {
                $LoanPolicy = $value;
                break; // User policy found
            }
        }

        if ($LoanPolicy == "") {
            throw new Exception($msgstr["err_policy_not_found"] ?? "Loan policy not found for user type: $user_type");
        }

        $splitpolicies = explode("|", $LoanPolicy);
        $allowrenewals = $splitpolicies[6] ?? 0; // Column 7 = Renewal limit
        $loanterm = $splitpolicies[5] ?? 'D';  // Column 6 = Term (D/H)
        $loanlong = $splitpolicies[3] ?? 7;   // Column 4 = Duration

        // [Validation 3] Renewal Limit
        $mx_count_ren = $converter_path . " " . $db_path . "trans/data/trans \"pft=v200\" from=" . $loan_id_mfn . " count=1 now";
        exec($mx_count_ren, $out_ren);
        $cantrenewals = count($out_ren);

        if ($cantrenewals >= $allowrenewals) {
            throw new Exception($msgstr["renewallimitreached"] ?? "Renewal limit reached.");
        }

        // [Validation 4] Item is reserved?
        $mx_cn = $converter_path . " " . $db_path . "trans/data/trans \"pft=v98,'+-+',v95\" from=" . $loan_id_mfn . " count=1 now";
        exec($mx_cn, $out_cn);
        $text_cn = implode("", $out_cn);
        $splittxt = explode("+-+", $text_cn);
        $db_item = $splittxt[0] ?? '';
        $cn_item = $splittxt[1] ?? '';

        if ($cn_item != "") {
            $mxr = $converter_path . " " . $db_path . "reserve/data/reserve \"pft=if v1='0' and v20='" . $cn_item . "' and v15='" . $db_item . "' then 'RESERVADO' fi\" now";
            exec($mxr, $out_r);
            if (!empty($out_r) && isset($out_r[0]) && trim($out_r[0]) == 'RESERVED') {
                throw new Exception($msgstr["documentreserved"] ?? "This item has been reserved by another user and cannot be renewed.");
            }
        }

        // --- SUCCESS: Execute the Renewal ---
        $timeto = "";
        if ($loanterm == "H") {
            $dateto_obj = new DateTime("+$loanlong hours");
            $dateto = $dateto_obj->format("Ymd");
            $timeto = $dateto_obj->format("h:i:s");
        } else {
            $dateto_obj = new DateTime("+$loanlong days");
            $dateto = $dateto_obj->format("Ymd");
        }

        $mxa = $converter_path . " " . $db_path . "trans/data/trans \"proc='<200>^a" . $today . "^b" . $time . "^c" . $dateto . "^d" . $timeto . "^e" . $user_id . "<" . "/200>'\" from=" . $loan_id_mfn . " count=1 copy=" . $db_path . "trans/data/trans now";
        exec($mxa, $out_update, $banderamx);

        if ($banderamx != 0) {
            throw new Exception($msgstr["err_renewal_failed"] ?? "Failed to save the renewal to the database.");
        }

        return ['status' => 'success', 'message' => $msgstr["success_operation"] ?? "Renewal confirmed!"];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function opac_CancelarReserva($reservation_mfn, $user_id)
{
    global $db_path, $cisis_path, $msgstr, $converter_path;
    $date = date("Ymd");
    $time = date("h:i:s");

    try {
        $mx = $converter_path . " " . $db_path . "reserve/data/reserve \"proc=if mfn=" . $reservation_mfn . " then 'd1d130d131d132','<1>1</1>','<132>" . $user_id . "</132>',,'<130>" . $date . "</130>','<131>" . $time . "</131>' fi \" copy=" . $db_path . "reserve/data/reserve now";
        exec($mx, $outmx, $banderamx);

        if ($banderamx != 0) {
            throw new Exception($msgstr["err_cancel_failed"] ?? "Error updating the database.");
        }

        opac_AtualizarIndiceReserva_Async();

        return ['status' => 'success', 'message' => $msgstr["reserve_cancel_success"] ?? "Reservation successfully cancelled."];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Updates the Inverted Index (IFP) of the 'reserve' database asynchronously.
 * Resolves %path_database% paths dynamically to avoid failures in CISIS.
 */
function opac_AtualizarIndiceReserva_Async()
{
    global $db_path, $converter_path;

    $base_res  = $db_path . "reserve/data/reserve";
    $fst_res   = $db_path . "reserve/data/reserve.fst";
    $actab_res = $db_path . "isisac.tab";
    $uctab_res = $db_path . "isisuc.tab";

    $par_original = $db_path . "par/reserve.par";
    $cipar_seguro = $par_original;

    if (file_exists($par_original)) {
        $par_content = file_get_contents($par_original);
        if (strpos($par_content, '%path_database%') !== false) {
            $par_content = str_replace("%path_database%", $db_path, $par_content);
            $cipar_seguro = $db_path . "wrk/temp_reserve_async.par";
            if (!is_dir($db_path . "wrk")) @mkdir($db_path . "wrk", 0777, true);
            file_put_contents($cipar_seguro, $par_content);
        }
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd_inv = "start /B \"\" \"$converter_path\" cipar=\"$cipar_seguro\" db=\"$base_res\" fst=@\"$fst_res\" actab=\"$actab_res\" uctab=\"$uctab_res\" fullinv=\"$base_res\" -all now 2>&1";
        pclose(popen($cmd_inv, "r"));
    } else {
        $cmd_inv = "\"$converter_path\" cipar=\"$cipar_seguro\" db=\"$base_res\" fst=@\"$fst_res\" actab=\"$actab_res\" uctab=\"$uctab_res\" fullinv=\"$base_res\" -all now > /dev/null 2>&1 &";
        exec($cmd_inv);
    }
}
