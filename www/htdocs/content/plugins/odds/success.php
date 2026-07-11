<?php

/**
 * Name: success.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Success page for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */

if (!class_exists('PluginBridge')) {
    die("Direct access forbidden.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_SESSION['lang'] ?? 'en';

$bridge = PluginBridge::getInstance();
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$pluginPath = realpath(__DIR__);

global $msgstr;
if (!is_array($msgstr)) {
    $msgstr = [];
}
@include_once($abcdPath . "/lang/admin.php");
include($pluginPath . "/lang/odds.php");

$mfn = $_SESSION['odds_success_mfn'] ?? null;
$base = $_SESSION['odds_base'] ?? 'odds';
$emailStatus = $_SESSION['odds_email_status'] ?? '';

if (!$mfn) {
    header("Location: ?action=form");
    exit;
}

unset($_SESSION['odds_success_mfn']);
?>

<link href="/content/plugins/odds/assets/css/odds.css" rel="stylesheet" type="text/css">
<link href="/content/plugins/odds/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">

<div class="container my-5 py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">

                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="h3 fw-bold text-dark mb-3">
                        <?php echo $msgstr['notice_success'] ?? 'Request Successfully Sent!'; ?>
                    </h2>

                    <p class="text-secondary mb-2 fs-5">
                        <?php echo $msgstr['odds_cre_rec1'] ?? 'Created record'; ?>
                        <strong class="text-primary fs-4"><?php echo htmlspecialchars($mfn); ?></strong>
                        <?php echo $msgstr['odds_cre_rec2'] ?? 'in database'; ?>
                        <strong class="text-dark"><?php echo htmlspecialchars($base); ?></strong>.
                    </p>

                    <?php if (!empty($emailStatus)): ?>
                        <div class="alert alert-light border mt-4 text-start shadow-sm">
                            <?php echo $emailStatus; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5">
                        <a href="?action=form&base=<?php echo urlencode($base); ?>" class="btn btn-outline-primary px-4 py-2 fw-semibold rounded-3">
                            <i class="fas fa-arrow-left me-2"></i> <?php echo $msgstr['notice_back'] ?? 'Go Back to Form'; ?>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>