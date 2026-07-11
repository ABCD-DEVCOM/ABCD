<?php

/**
 * Name: settings.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Settings page for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

$bridge = PluginBridge::getInstance();
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../../central'));
$dbPath = $bridge->get('db_path');
$pluginPath = realpath(__DIR__ . '/../');

// Assegura o carregamento dos dicionários globais e do plugin

$lang = $_SESSION["lang"] ?? "en";

global $msgstr;
if (!is_array($msgstr)) {
    $msgstr = [];
}
@include_once($abcdPath . "/lang/admin.php");
include($pluginPath . "/lang/odds.php");

$alertMessage = "";

// --- Helper Function to Split Subject and Body ---
function oddsParseTemplate(string $content): array
{
    $lines = explode("\n", $content);
    $subject = '';
    if (isset($lines[0]) && stripos(trim($lines[0]), 'subject =') === 0) {
        $subject = trim(substr(trim($lines[0]), 9)); // Removes 'subject ='
        unset($lines[0]);
    }
    $body = trim(implode("\n", $lines));
    return ['subject' => $subject, 'body' => $body];
}

// -------------------------------------------------------------------------
// POST HANDLERS (The Controller)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['odds_admin_action'])) {

    $action = $_POST['odds_admin_action'];

    // --- HANDLE SMTP SAVE ---
    if ($action === 'save_smtp') {
        $smtpFile = $pluginPath . '/config/smtp.php';
        $newConfig = [
            'host'       => trim($_POST['smtp_host'] ?? ''),
            'port'       => (int) ($_POST['smtp_port'] ?? 587),
            'username'   => trim($_POST['smtp_username'] ?? ''),
            'password'   => trim($_POST['smtp_password'] ?? ''),
            'from_email' => trim($_POST['smtp_from_email'] ?? ''),
            'from_name'  => trim($_POST['smtp_from_name'] ?? '')
        ];

        $fileContent = "<?php\n" .
            "// SECURITY LOCK: Prevents direct URL access\n" .
            "if (!class_exists('PluginBridge')) {\n" .
            "    header(\"HTTP/1.1 403 Forbidden\");\n" .
            "    die(\"Direct access forbidden.\");\n" .
            "}\n\n" .
            "return " . var_export($newConfig, true) . ";\n";

        // The '@' suppresses the ugly PHP error. We handle the failure visually below.
        if (@file_put_contents($smtpFile, $fileContent) !== false) {
            $msg = $msgstr['odds_save_success'] ?? 'Configuration saved successfully!';
            $alertMessage = "<div style='color:green; font-weight:bold; padding:10px; border:1px solid green; margin-bottom:15px;'>{$msg}</div>";
        } else {
            $alertMessage = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'>
                                <strong>Permission Denied:</strong> The web server could not write to the file.<br>
                                <u>Solution:</u> Access your panel/FTP and grant write permissions (<code>chmod 666</code> or <code>777</code>) to this file or directory:<br>
                                <code>" . htmlspecialchars($smtpFile) . "</code>
                             </div>";
        }
    }

    // --- HANDLE ACCESS SAVE ---
    if ($action === 'save_access') {
        $defDir = rtrim($dbPath, '/\\') . '/odds/def';
        if (!is_dir($defDir)) {
            @mkdir($defDir, 0777, true);
        }
        $oddsDefFile = $defDir . '/odds.def';
        $newAccess = ($_POST['odds_access'] === 'auth_only') ? 'auth_only' : 'public';
        $fileContent = "; ODDS Plugin Configuration\nODDS_ACCESS=\"{$newAccess}\"\n";

        if (@file_put_contents($oddsDefFile, $fileContent) !== false) {
            $msg = $msgstr['odds_save_success'] ?? 'Settings saved successfully!';
            $alertMessage = "<div style='color:green; font-weight:bold; padding:10px; border:1px solid green; margin-bottom:15px;'>{$msg}</div>";
        } else {
            $alertMessage = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'>
                                <strong>Permission Denied:</strong> Could not save the settings. Grant write permissions (<code>chmod 777</code>) to the folder: <br><code>{$defDir}</code>
                             </div>";
        }
    }


    // --- HANDLE TEST EMAIL ---
    if ($action === 'test_smtp') {
        $testEmail = trim($_POST['test_email_to'] ?? '');
        $smtpFile = $pluginPath . '/config/smtp.php';

        if (!empty($testEmail) && file_exists($smtpFile)) {
            require_once $pluginPath . '/OddsMailer.php';
            $smtpCfg = require $smtpFile;

            // Enable output buffering to capture PHPMailer's technical log
            ob_start();
            try {
                // Instantiate PHPMailer natively to force DEBUG mode
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->SMTPDebug = 2; // Level 2 displays the Client <-> Server dialogue
                $mail->Debugoutput = 'html';

                $mail->isSMTP();
                $mail->Host       = $smtpCfg['host'];

                // --- NEW: Dynamic Authentication. Only authenticate if a password exists ---
                if (!empty($smtpCfg['password'])) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpCfg['username'];
                    $mail->Password = $smtpCfg['password'];
                } else {
                    $mail->SMTPAuth = false;
                }

                // Adjust encryption automatically based on the port
                if ($smtpCfg['port'] == 465) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->Port       = (int)$smtpCfg['port'];
                $mail->setFrom($smtpCfg['from_email'], $smtpCfg['from_name']);

                // --- NEW: Bypass SSL certificate verification for the tester ---
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->addAddress($testEmail);
                $mail->isHTML(true);
                $mail->Subject = 'ABCD ODDS - SMTP Test';
                $mail->Body    = '<h3>Success!</h3><p>If you are reading this, the SMTP configuration of the ODDS plugin in ABCD is working perfectly.</p>';

                $mail->send();
                $debugLog = ob_get_clean(); // Clean the buffer and save the log

                $alertMessage = "<div style='color:green; padding:15px; border:2px solid green; margin-bottom:15px; background:#e8f5e9;'>
                                    <strong>✅ Test email sent successfully!</strong> Check the inbox (and spam folder) of <b>{$testEmail}</b>.
                                 </div>";
            } catch (Exception $e) {
                $debugLog = ob_get_clean(); // If it fails, capture the error log
                $alertMessage = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'>
                                    <strong>❌ Failed to send email!</strong> The SMTP server refused the connection.<br>
                                    Main reason: <i>{$mail->ErrorInfo}</i>
                                    <hr style='border-top:1px solid #ffcdd2;'>
                                    <details>
                                        <summary style='cursor:pointer; font-weight:bold;'>[+] Click here to view the SMTP Server Technical Log</summary>
                                        <pre style='background:#fff; padding:10px; margin-top:10px; overflow-x:auto; font-size:11px; color:#333; border:1px solid #ccc;'>{$debugLog}</pre>
                                    </details>
                                 </div>";
            }
        }
        $activeTab = 'tab-smtp';
    }

    // --- HANDLE CKEDITOR SAVE ---
    if ($action === 'save_template') {
        $defDir = rtrim($dbPath, '/\\') . '/odds/def/' . $lang;
        if (!is_dir($defDir)) {
            @mkdir($defDir, 0777, true);
        }

        // Reconstruct the files with the technical "subject = " line
        $contentSingle   = "subject = " . trim($_POST['subject_single'] ?? '') . "\n" . trim($_POST['mail_single'] ?? '');
        $contentMultiple = "subject = " . trim($_POST['subject_multiple'] ?? '') . "\n" . trim($_POST['mail_multiple'] ?? '');
        $contentCancel   = "subject = " . trim($_POST['subject_cancel'] ?? '') . "\n" . trim($_POST['mail_cancel'] ?? '');

        $e1 = @file_put_contents($defDir . '/odds_success_mail_single_file.tab', $contentSingle);
        $e2 = @file_put_contents($defDir . '/odds_success_mail_multiple_file.tab', $contentMultiple);
        $e3 = @file_put_contents($defDir . '/odds_cancel_mail.tab', $contentCancel);

        if ($e1 !== false && $e2 !== false && $e3 !== false) {
            $msg = $msgstr['odds_save_success'] ?? 'Templates saved successfully!';
            $alertMessage = "<div style='color:green; font-weight:bold; padding:10px; border:1px solid green; margin-bottom:15px;'>{$msg}</div>";
        } else {
            $alertMessage = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'>
                                <strong>Permission Denied:</strong> Could not save the templates. Grant write permissions (<code>chmod 777</code>) to the folder: <br><code>{$defDir}</code>
                             </div>";
        }
    }

    // --- HANDLE TABLE SAVE ---
    if ($action === 'save_table') {
        $defDir = rtrim($dbPath, '/\\') . '/odds/def/' . $lang;
        if (!is_dir($defDir)) {
            @mkdir($defDir, 0777, true);
        }

        $fileName = $_POST['table_file'] ?? '';
        $content = $_POST['ValorCapturado'] ?? '';

        if (!empty($fileName)) {
            $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\\s\t]*[\r\n]+/", "\n", $content);
            if (@file_put_contents($defDir . '/' . basename($fileName), $content) !== false) {
                $msg = $msgstr['odds_save_success'] ?? 'Saved successfully!';
                $alertMessage = "<div style='color:green; font-weight:bold; padding:10px; border:1px solid green; margin-bottom:15px;'>{$fileName}: {$msg}</div>";
            } else {
                $alertMessage = "<div style='color:red; padding:15px; border:2px solid red; margin-bottom:15px; background:#ffebee;'>
                                    <strong>Permission Denied:</strong> Could not save the table. Check permissions for: <br><code>{$defDir}/" . basename($fileName) . "</code>
                                 </div>";
            }
        }
    }
}

// --- OPAC Access Control Logic ---
$oddsDefFile = rtrim($dbPath, '/\\') . '/odds/def/odds.def';
$oddsAccessSuccess = false;

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_odds_access'])) {
    $newAccess = ($_POST['odds_access'] === 'auth_only') ? 'auth_only' : 'public';

    // Ensure the def directory exists
    $defDir = dirname($oddsDefFile);
    if (!is_dir($defDir)) {
        mkdir($defDir, 0755, true);
    }

    // Build and save the file content
    $fileContent = "; ODDS Plugin Configuration\n";
    $fileContent .= "ODDS_ACCESS=\"{$newAccess}\"\n";

    if (file_put_contents($oddsDefFile, $fileContent) !== false) {
        $oddsAccessSuccess = true;
    }
}

// Read current settings to populate the form
$currentOddsAccess = 'public'; // Default fallback
if (file_exists($oddsDefFile)) {
    $defData = parse_ini_file($oddsDefFile);
    if (isset($defData['ODDS_ACCESS'])) {
        $currentOddsAccess = $defData['ODDS_ACCESS'];
    }
}

// -------------------------------------------------------------------------
// DATA LOADERS
// -------------------------------------------------------------------------

// 1. SMTP Config
$smtpFile = $pluginPath . '/config/smtp.php';
$smtpConfig = file_exists($smtpFile) ? require $smtpFile : [
    'host' => '',
    'port' => '',
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => ''
];

// 2. Templates Config (Parse Subject and Body)
$defDir = rtrim($dbPath, '/\\') . '/odds/def/' . $lang;

$templateSinglePath = $defDir . '/odds_success_mail_single_file.tab';
$templateSingle = file_exists($templateSinglePath) ? file_get_contents($templateSinglePath) : "subject = Document Delivery\nYour document is attached.";
$parsedSingle = oddsParseTemplate($templateSingle);

$templateMultiplePath = $defDir . '/odds_success_mail_multiple_file.tab';
$templateMultiple = file_exists($templateMultiplePath) ? file_get_contents($templateMultiplePath) : "subject = Document Delivery\nYour documents are attached.";
$parsedMultiple = oddsParseTemplate($templateMultiple);

$templateCancelPath = $defDir . '/odds_cancel_mail.tab';
$templateCancel = file_exists($templateCancelPath) ? file_get_contents($templateCancelPath) : "subject = Document Request Cancelled\nYour request could not be fulfilled.";
$parsedCancel = oddsParseTemplate($templateCancel);

// 3. Domain Tables Config (Buscando na base de dados)
$editableTables = [
    'categoria.tab',
    'nivelbiblio.tab',
    'source.tab',
    'status.tab',
    'tipoliteratura.tab',
    'topicarea.tab'
];
$selectedTable = $_GET['table_file'] ?? 'categoria.tab';
$selectedTablePath = $defDir . '/' . $selectedTable;

$tableLines = [];
if (file_exists($selectedTablePath)) {
    $lines = file($selectedTablePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) >= 2) {
            $tableLines[] = ['code' => trim($parts[0]), 'desc' => trim($parts[1])];
        } else {
            $tableLines[] = ['code' => trim($line), 'desc' => ''];
        }
    }
}

$activeTab = 'tab-smtp';
if (isset($_POST['odds_admin_action'])) {
    if ($_POST['odds_admin_action'] === 'save_template') $activeTab = 'tab-templates';
    if ($_POST['odds_admin_action'] === 'save_table') $activeTab = 'tab-tables';
} elseif (isset($_GET['table_file'])) {
    $activeTab = 'tab-tables';
}

// 4. Access Control Config
$oddsDefFile = rtrim($dbPath, '/\\') . '/odds/def/odds.def';
$currentOddsAccess = 'public'; // Default fallback
if (file_exists($oddsDefFile)) {
    $defData = parse_ini_file($oddsDefFile);
    if (isset($defData['ODDS_ACCESS'])) {
        $currentOddsAccess = $defData['ODDS_ACCESS'];
    }
}

// Lógica da Aba Ativa (Atualizada)
$activeTab = 'tab-access'; // Agora a aba padrão será a de Acesso
if (isset($_POST['odds_admin_action'])) {
    if ($_POST['odds_admin_action'] === 'save_smtp') $activeTab = 'tab-smtp';
    if ($_POST['odds_admin_action'] === 'test_smtp') $activeTab = 'tab-smtp';
    if ($_POST['odds_admin_action'] === 'save_template') $activeTab = 'tab-templates';
    if ($_POST['odds_admin_action'] === 'save_table') $activeTab = 'tab-tables';
    if ($_POST['odds_admin_action'] === 'save_access') $activeTab = 'tab-access';
} elseif (isset($_GET['table_file'])) {
    $activeTab = 'tab-tables';
}


?>

<link rel="stylesheet" href="../../../content/plugins/odds/assets/css/style.css">
<script type="text/javascript" src="/central/ckeditor/ckeditor.js"></script>

<div class="odds-admin-container">
    <h2><?php echo $msgstr['odds_admin_panel'] ?? 'ODDS Administration Panel'; ?><a target="_blank" href="/service/odds/" class="bt bt-light" style="margin-left: 10px;">ODDS</a></h2>

    <?php echo $alertMessage; ?>

    <div class="odds-tabs">
        <button class="odds-tab-btn <?php echo $activeTab === 'tab-access' ? 'active' : ''; ?>" onclick="oddsOpenTab(event, 'tab-access')"><?php echo $msgstr['odds_tab_access'] ?? 'Access Control'; ?></button>
        <button class="odds-tab-btn <?php echo $activeTab === 'tab-smtp' ? 'active' : ''; ?>" onclick="oddsOpenTab(event, 'tab-smtp')"><?php echo $msgstr['odds_tab_smtp'] ?? 'E-mail Server (SMTP)'; ?></button>
        <button class="odds-tab-btn <?php echo $activeTab === 'tab-templates' ? 'active' : ''; ?>" onclick="oddsOpenTab(event, 'tab-templates')"><?php echo $msgstr['odds_tab_templates'] ?? 'Message Templates'; ?></button>
        <button class="odds-tab-btn <?php echo $activeTab === 'tab-tables' ? 'active' : ''; ?>" onclick="oddsOpenTab(event, 'tab-tables')"><?php echo $msgstr['odds_tab_tables'] ?? 'Dynamic Tables'; ?></button>
    </div>

    <div id="tab-access" class="odds-tab-content <?php echo $activeTab === 'tab-access' ? 'active' : ''; ?>">
        <h3><?php echo $msgstr['odds_access_title'] ?? 'Access Control (OPAC)'; ?></h3>
        <p><?php echo $msgstr['odds_access_desc'] ?? 'Define who can request documents through the ODDS form:'; ?></p>

        <form method="POST" action="">
            <input type="hidden" name="odds_admin_action" value="save_access">

            <div class="odds-form-group" style="background:#fcfcfc; border:1px solid #ddd; padding:15px; border-radius:4px;">
                <label style="display: block; margin-bottom: 12px; cursor: pointer; font-weight: normal;">
                    <input type="radio" name="odds_access" value="public" <?php echo ($currentOddsAccess === 'public') ? 'checked' : ''; ?>>
                    <strong><?php echo $msgstr['odds_access_public'] ?? 'Public:'; ?></strong>
                    <?php echo $msgstr['odds_access_public_desc'] ?? 'Anyone can fill the form (Anonymous allowed).'; ?>
                </label>

                <label style="display: block; cursor: pointer; font-weight: normal;">
                    <input type="radio" name="odds_access" value="auth_only" <?php echo ($currentOddsAccess === 'auth_only') ? 'checked' : ''; ?>>
                    <strong><?php echo $msgstr['odds_access_auth'] ?? 'Authenticated Only:'; ?></strong>
                    <?php echo $msgstr['odds_access_auth_desc'] ?? 'Requires an active OPAC session. Automatically locks the user ID and Name fields.'; ?>
                </label>
            </div>

            <button type="submit" class="odds-btn-save"><?php echo $msgstr['odds_btn_save'] ?? 'Save Settings'; ?></button>
        </form>
    </div>

    <div id="tab-smtp" class="odds-tab-content <?php echo $activeTab === 'tab-smtp' ? 'active' : ''; ?>">
        <h3><?php echo $msgstr['odds_smtp_title'] ?? 'SMTP Configuration'; ?></h3>
        <p><?php echo $msgstr['odds_smtp_desc'] ?? 'Configure the credentials used to send confirmation and notification emails.'; ?></p>
        <h3><?php echo $msgstr['odds_smtp_title'] ?? 'SMTP Configuration'; ?></h3>
        <p><?php echo $msgstr['odds_smtp_desc'] ?? 'Configure the credentials used to send confirmation and notification emails.'; ?></p>

        <form method="POST" action="">
            <input type="hidden" name="odds_admin_action" value="save_smtp">

            <div class="odds-form-group">
                <label for="smtp_host"><?php echo $msgstr['odds_smtp_host'] ?? 'SMTP Host'; ?></label>
                <input type="text" id="smtp_host" name="smtp_host" value="<?php echo $smtpConfig['host']; ?>" required>
            </div>
            <div class="odds-form-group">
                <label for="smtp_port"><?php echo $msgstr['odds_smtp_port'] ?? 'SMTP Port'; ?></label>
                <input type="number" id="smtp_port" name="smtp_port" value="<?php echo $smtpConfig['port']; ?>" required>
            </div>
            <div class="odds-form-group">
                <label for="smtp_username"><?php echo $msgstr['odds_smtp_user'] ?? 'Username / E-mail'; ?></label>
                <input type="text" id="smtp_username" name="smtp_username" value="<?php echo $smtpConfig['username']; ?>">
            </div>
            <div class="odds-form-group">
                <label for="smtp_password"><?php echo $msgstr['odds_smtp_pass'] ?? 'Password / App Password'; ?></label>
                <input type="password" id="smtp_password" name="smtp_password" value="<?php echo $smtpConfig['password']; ?>">
            </div>
            <hr>
            <div class="odds-form-group">
                <label for="smtp_from_email"><?php echo $msgstr['odds_smtp_from_mail'] ?? 'Sender E-mail (From)'; ?></label>
                <input type="text" id="smtp_from_email" name="smtp_from_email" value="<?php echo $smtpConfig['from_email']; ?>" required>
            </div>
            <div class="odds-form-group">
                <label for="smtp_from_name"><?php echo $msgstr['odds_smtp_from_name'] ?? 'Sender Name (Alias)'; ?></label>
                <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo $smtpConfig['from_name']; ?>" required>
            </div>

            <button type="submit" class="odds-btn-save"><?php echo $msgstr['odds_btn_save'] ?? 'Save Settings'; ?></button>
        </form>

        <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">
        <h4 style="margin-top:0; color:#3669c6;"><i class="fas fa-paper-plane"></i> Test SMTP Connection</h4>
        <form method="POST" action="">
            <input type="hidden" name="odds_admin_action" value="test_smtp">
            <div class="odds-form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="text" name="test_email_to" value="<?php echo $_POST['test_email_to'] ?? ''; ?>" placeholder="Type your e-mail address to test..." style="max-width: 300px;" required>
                <button type="submit" class="odds-btn-action" style="background-color: #2196F3; color: white; padding: 8px 15px; font-weight:bold; border-radius:4px; border:none;">Send Test E-mail</button>
            </div>
        </form>


    </div>

    <div id="tab-templates" class="odds-tab-content <?php echo $activeTab === 'tab-templates' ? 'active' : ''; ?>">
        <h3><?php echo $msgstr['odds_tab_templates'] ?? 'Message Templates'; ?></h3>
        <p><?php echo $msgstr['odds_tpl_desc'] ?? 'Edit the content sent to users when their document requests are fulfilled or cancelled.'; ?></p>

        <div style="background-color: #e8f4f8; border-left: 5px solid #2196F3; padding: 15px; margin-bottom: 20px; font-size: 12px; border-radius: 3px;">
            <strong style="color: #0b5394;"><i class="fas fa-info-circle"></i> <?php echo $msgstr['odds_tpl_help_title'] ?? 'Available Shortcodes'; ?></strong><br>
            <?php echo $msgstr['odds_tpl_help_desc'] ?? 'The system will automatically replace these tags with real data before sending the email:'; ?>
            <ul style="margin-top: 5px; margin-bottom: 0;">
                <li><code>[name]</code> - <?php echo $msgstr['odds_tpl_help_name'] ?? 'Requester Name'; ?></li>
                <li><code>[title]</code> - <?php echo $msgstr['odds_tpl_help_title_doc'] ?? 'Document Reference / Title'; ?></li>
                <li><code>[date]</code> - <?php echo $msgstr['odds_tpl_help_date'] ?? 'Request Date'; ?></li>
                <li><code>[notes]</code> - <?php echo $msgstr['odds_tpl_help_notes'] ?? 'Library Notes (Useful for cancellations)'; ?></li>
                <li><code>[link]</code> - <?php echo $msgstr['odds_tpl_help_link'] ?? 'Download links (Success templates only)'; ?></li>
                <li><code>[url]</code> - <?php echo $msgstr['odds_tpl_help_url'] ?? 'ODDS Root URL'; ?></li>
            </ul>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="odds_admin_action" value="save_template">

            <div class="odds-form-group" style="background:#fcfcfc; border:1px solid #ddd; padding:15px; border-radius:4px;">
                <label style="font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px;">
                    <i class="fas fa-file"></i> <?php echo $msgstr['odds_tpl_single'] ?? 'Single File E-mail Template (odds_success_mail_single_file.tab)'; ?>
                </label>

                <div style="margin-bottom: 10px;">
                    <strong style="font-size:12px;"><?php echo $msgstr['odds_tpl_subject'] ?? 'E-mail Subject:'; ?></strong>
                    <input type="text" name="subject_single" value="<?php echo $parsedSingle['subject']; ?>" style="width: 100%; max-width: none; margin-top: 5px;" required>
                </div>

                <strong style="font-size:12px; display:block; margin-bottom:5px;"><?php echo $msgstr['odds_tpl_body'] ?? 'E-mail Body:'; ?></strong>
                <textarea id="mail_single" name="mail_single" rows="10" cols="80"><?php echo $parsedSingle['body']; ?></textarea>
            </div>

            <div class="odds-form-group" style="background:#fcfcfc; border:1px solid #ddd; padding:15px; border-radius:4px;">
                <label style="font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px;">
                    <i class="fas fa-copy"></i> <?php echo $msgstr['odds_tpl_multiple'] ?? 'Multiple Files E-mail Template (odds_success_mail_multiple_file.tab)'; ?>
                </label>

                <div style="margin-bottom: 10px;">
                    <strong style="font-size:12px;"><?php echo $msgstr['odds_tpl_subject'] ?? 'E-mail Subject:'; ?></strong>
                    <input type="text" name="subject_multiple" value="<?php echo $parsedMultiple['subject']; ?>" style="width: 100%; max-width: none; margin-top: 5px;" required>
                </div>

                <strong style="font-size:12px; display:block; margin-bottom:5px;"><?php echo $msgstr['odds_tpl_body'] ?? 'E-mail Body:'; ?></strong>
                <textarea id="mail_multiple" name="mail_multiple" rows="10" cols="80"><?php echo $parsedMultiple['body']; ?></textarea>
            </div>

            <div class="odds-form-group" style="background:#fff4f4; border:1px solid #ffcdd2; padding:15px; border-radius:4px;">
                <label style="font-size: 14px; border-bottom: 1px solid #ffcdd2; padding-bottom: 5px; margin-bottom: 15px; color:#d32f2f;">
                    <i class="fas fa-ban"></i> <?php echo $msgstr['odds_tpl_cancel'] ?? 'Cancellation E-mail Template (odds_cancel_mail.tab)'; ?>
                </label>

                <div style="margin-bottom: 10px;">
                    <strong style="font-size:12px;"><?php echo $msgstr['odds_tpl_subject'] ?? 'E-mail Subject:'; ?></strong>
                    <input type="text" name="subject_cancel" value="<?php echo $parsedCancel['subject']; ?>" style="width: 100%; max-width: none; margin-top: 5px;" required>
                </div>

                <strong style="font-size:12px; display:block; margin-bottom:5px;"><?php echo $msgstr['odds_tpl_body'] ?? 'E-mail Body:'; ?></strong>
                <textarea id="mail_cancel" name="mail_cancel" rows="10" cols="80"><?php echo $parsedCancel['body']; ?></textarea>
            </div>

            <button type="submit" class="odds-btn-save"><?php echo $msgstr['odds_btn_save'] ?? 'Save Settings'; ?></button>
        </form>

        <script>
            if (typeof CKEDITOR !== 'undefined') {
                CKEDITOR.replace('mail_single');
                CKEDITOR.replace('mail_multiple');
                CKEDITOR.replace('mail_cancel');
            }
        </script>
    </div>

    <div id="tab-tables" class="odds-tab-content <?php echo $activeTab === 'tab-tables' ? 'active' : ''; ?>">
        <h3><?php echo $msgstr['odds_tab_tables'] ?? 'Domain Tables Editor'; ?></h3>

        <div style="margin-bottom: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
            <label style="font-weight:bold;"><?php echo $msgstr['odds_tbl_select'] ?? 'Select table to edit:'; ?></label>
            <select id="tableSelector" onchange="window.location.href='?plugin=odds&table_file='+this.value">
                <?php foreach ($editableTables as $table): ?>
                    <option value="<?php echo $table; ?>" <?php echo $selectedTable === $table ? 'selected' : ''; ?>>
                        <?php echo $table; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <form name="tableForm" method="POST" action="">
            <input type="hidden" name="odds_admin_action" value="save_table">
            <input type="hidden" name="table_file" value="<?php echo $selectedTable; ?>">
            <input type="hidden" name="ValorCapturado" id="ValorCapturado" value="">

            <table class="fst-table" id="domainTable">
                <thead>
                    <tr>
                        <th width="30%"><?php echo $msgstr['odds_tbl_code'] ?? 'Code (Key)'; ?></th>
                        <th width="50%"><?php echo $msgstr['odds_tbl_desc'] ?? 'Description (Label)'; ?></th>
                        <th width="20%"><?php echo $msgstr['odds_tbl_actions'] ?? 'Actions'; ?></th>
                    </tr>
                </thead>
                <tbody id="domainTableBody">
                    <?php if (empty($tableLines)): ?>
                        <tr class="fst-row">
                            <td><input type="text" class="fst-input input-code" value=""></td>
                            <td><input type="text" class="fst-input input-desc" value=""></td>
                            <td>
                                <button type="button" class="odds-btn-action" onclick="addRow(this)">+</button>
                                <button type="button" class="odds-btn-action" onclick="removeRow(this)">-</button>
                                <button type="button" class="odds-btn-action" onclick="moveRow(this, -1)">↑</button>
                                <button type="button" class="odds-btn-action" onclick="moveRow(this, 1)">↓</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tableLines as $line): ?>
                            <tr class="fst-row">
                                <td><input type="text" class="fst-input input-code" value="<?php echo $line['code']; ?>"></td>
                                <td><input type="text" class="fst-input input-desc" value="<?php echo $line['desc']; ?>"></td>
                                <td>
                                    <button type="button" class="odds-btn-action" onclick="addRow(this)">+</button>
                                    <button type="button" class="odds-btn-action" onclick="removeRow(this)">-</button>
                                    <button type="button" class="odds-btn-action" onclick="moveRow(this, -1)">↑</button>
                                    <button type="button" class="odds-btn-action" onclick="moveRow(this, 1)">↓</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <br>
            <button type="button" class="odds-btn-save" onclick="submitTable()"><?php echo $msgstr['odds_btn_save'] ?? 'Save Settings'; ?></button>
        </form>
    </div>
</div>

<script>
    function oddsOpenTab(evt, tabId) {
        const tabContents = document.getElementsByClassName("odds-tab-content");
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].style.display = "none";
            tabContents[i].classList.remove("active");
        }

        const tabBtns = document.getElementsByClassName("odds-tab-btn");
        for (let i = 0; i < tabBtns.length; i++) {
            tabBtns[i].className = tabBtns[i].className.replace(" active", "");
        }

        document.getElementById(tabId).style.display = "block";
        document.getElementById(tabId).classList.add("active");
        evt.currentTarget.className += " active";
    }

    function addRow(btn) {
        const row = btn.closest("tr");
        const newRow = row.cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        if (row.nextSibling) {
            row.parentNode.insertBefore(newRow, row.nextSibling);
        } else {
            row.parentNode.appendChild(newRow);
        }
    }

    function removeRow(btn) {
        const row = btn.closest("tr");
        const tbody = row.parentNode;
        if (tbody.querySelectorAll("tr").length > 1) {
            tbody.removeChild(row);
        } else {
            alert("<?php echo $msgstr['odds_tbl_del_err'] ?? 'Cannot remove the last row.'; ?>");
        }
    }

    function moveRow(btn, direction) {
        const row = btn.closest("tr");
        const tbody = row.parentNode;
        if (direction === -1 && row.previousElementSibling) {
            tbody.insertBefore(row, row.previousElementSibling);
        } else if (direction === 1 && row.nextElementSibling) {
            tbody.insertBefore(row.nextElementSibling, row);
        }
    }

    function collectTableData() {
        const rows = document.querySelectorAll("#domainTableBody .fst-row");
        let data = [];
        rows.forEach(row => {
            const code = row.querySelector(".input-code").value.trim();
            const desc = row.querySelector(".input-desc").value.trim();
            if (code !== "") {
                if (desc !== "") {
                    data.push(code + "|" + desc);
                } else {
                    data.push(code);
                }
            }
        });
        return data.join("\n");
    }

    function submitTable() {
        const content = collectTableData();
        document.getElementById("ValorCapturado").value = content;
        document.forms["tableForm"].submit();
    }
</script>