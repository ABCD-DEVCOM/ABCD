<?php

/**
 * Name: form.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Form page for the ODDS plugin in the ABCD application.
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

// 1. Inicia a sessão para garantir que o idioma seja lembrado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Captura o idioma da URL (se existir) e salva na sessão para as próximas telas
if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang'])) {
    $_SESSION['lang'] = trim($_REQUEST['lang']);
}
$lang = $_SESSION['lang'] ?? 'en';

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$pluginPath = realpath(__DIR__);

// 3. Força o carregamento do dicionário correto do Plugin
global $msgstr;
if (!is_array($msgstr)) {
    $msgstr = [];
}
@include_once($abcdPath . "/lang/admin.php");
include($pluginPath . "/lang/odds.php");

$oddsDefFile = rtrim($dbPath, '/\\') . '/odds/def/odds.def';
$oddsAccess = 'public';

if (file_exists($oddsDefFile)) {
    $defData = parse_ini_file($oddsDefFile);
    if (isset($defData['ODDS_ACCESS'])) {
        $oddsAccess = $defData['ODDS_ACCESS'];
    }
}

$isOpacLogged = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($oddsAccess === 'auth_only' && !$isOpacLogged) {
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    $authRoute = $defData['AUTH_ROUTE'] ?? '/opac/login.php';
    header("Location: {$authRoute}?RedirectUrl={$returnUrl}&lang={$lang}");
    exit;
}

$requestData = [
    'id'       => $isOpacLogged ? $_SESSION['user_id'] : ($_REQUEST['tag630'] ?? $_REQUEST['id'] ?? ''),
    'name'     => $isOpacLogged ? $_SESSION['user_name'] : ($_REQUEST['tag510'] ?? $_REQUEST['name'] ?? ''),
    'email'    => $_REQUEST['tag528'] ?? $_REQUEST['email'] ?? '',
    'phone'    => $_REQUEST['tag512'] ?? $_REQUEST['phone'] ?? '',
    'category' => $_REQUEST['tag520'] ?? $_REQUEST['category'] ?? '',
    'level'    => $_REQUEST['tag006'] ?? $_REQUEST['level'] ?? '',
    'mfn'      => $_REQUEST['tag999'] ?? $_REQUEST['mfn'] ?? '',
    'comments' => $_REQUEST['tag068'] ?? '',
    'referer'  => $_REQUEST['referer'] ?? ''
];

require_once __DIR__ . '/inc_odds_combos.php';
$combos = load_combos($lang);

require_once __DIR__ . '/inc_odds_info.php';
$helpInfo = load_info($lang);

$welcomeMsg = str_replace(
    ["[year]", "[day]", "[month]"],
    [date("Y"), date("j"), date("F")],
    $msgstr["welcome"] ?? "Welcome"
);
?>

<link href="/content/plugins/odds/assets/css/odds.css" rel="stylesheet" type="text/css">
<link href="/content/plugins/odds/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">

<div class="container my-5">
    <div class="row mb-4 align-items-center border-bottom pb-3">
        <div class="col-md-8">
            <h2 class="h3 mb-1 text-primary fw-bold"><?php echo $msgstr['title'] ?? 'Document Delivery'; ?></h2>
            <p class="text-muted mb-0 fs-6"><?php echo $msgstr['subtitle'] ?? 'Fill out the form below to request a document.'; ?></p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="alert alert-primary d-flex align-items-center rounded-3 mb-4" role="alert">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div class="fw-medium"><?php echo $welcomeMsg; ?></div>
                    </div>

                    <!-- Uses relative action to support any routing (/service/ or /plugin/) -->
                    <form method="post" id="forma1" action="?action=process" novalidate>
                        <input type="hidden" name="tag999" id="tag999" value="<?php echo htmlspecialchars($requestData['mfn']); ?>">

                        <!-- User Data -->
                        <h4 class="h5 mb-4 text-secondary border-bottom pb-2 fw-semibold"><?php echo $msgstr['subtitle_user'] ?? 'User Data'; ?></h4>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id" class="form-label fw-bold"><?php echo $msgstr['odds_id'] ?? 'ID'; ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo $isOpacLogged ? 'bg-light' : ''; ?>" id="id" name="tag630" value="<?php echo htmlspecialchars($requestData['id']); ?>" maxlength="10" data-jv="required uint" <?php echo $isOpacLogged ? 'readonly' : ''; ?>>
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold"><?php echo $msgstr['name'] ?? 'Name'; ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo $isOpacLogged ? 'bg-light' : ''; ?>" id="name" name="tag510" value="<?php echo htmlspecialchars($requestData['name']); ?>" maxlength="35" data-jv="required" <?php echo $isOpacLogged ? 'readonly' : ''; ?>>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold"><?php echo $msgstr['email'] ?? 'Email'; ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="tag528" value="<?php echo htmlspecialchars($requestData['email']); ?>" maxlength="35" data-jv="email required">
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-bold"><?php echo $msgstr['phone'] ?? 'Phone'; ?></label>
                                <input type="text" class="form-control" id="phone" name="tag512" value="<?php echo htmlspecialchars($requestData['phone']); ?>" maxlength="15">
                            </div>

                            <div class="col-md-12">
                                <label for="category" class="form-label fw-bold"><?php echo $msgstr['category'] ?? 'Category'; ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="tag520" id="category">
                                    <option value="" <?php echo empty($requestData['category']) ? 'selected' : ''; ?>><?php echo $msgstr['odds_selectlevel'] ?? '-- Select --'; ?></option>
                                    <?php foreach ($combos["categoria"] as $key => $value): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($requestData['category'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Request Data -->
                        <div class="d-flex align-items-center justify-content-between mt-5 mb-4 border-bottom pb-2">
                            <h4 class="h5 text-secondary mb-0 fw-semibold"><?php echo $msgstr['subtitle_request'] ?? 'Request Data'; ?></h4>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="level" class="form-label fw-bold"><?php echo $msgstr['level'] ?? 'Level'; ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="tag006" id="level" onchange="fetchDynamicFields(this.value)">
                                    <option value="" <?php echo empty($requestData['level']) ? 'selected' : ''; ?>><?php echo $msgstr['odds_selectlevel'] ?? '-- Select --'; ?></option>
                                    <?php foreach ($combos["nivelbiblio"] as $key => $value): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($requestData['level'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dynamic fields container -->
                            <div id="dynamic_fields_container" class="col-md-12 row g-3 m-0 p-0"></div>

                            <div class="col-md-12 mt-4">
                                <label for="comments" class="form-label fw-bold"><?php echo $msgstr['comments'] ?? 'Comments'; ?>:</label>
                                <textarea class="form-control bg-light" id="comments" name="tag068" rows="4" style="resize:none;"><?php echo htmlspecialchars($requestData['comments']); ?></textarea>
                            </div>
                        </div>

                        <div id="form_errors" class="mt-4" style="display:none;"></div>

                        <div class="mt-5 text-end">
                            <button class="btn btn-primary px-5 py-2 fw-bold rounded-3" type="button" id="button_submit" onclick="validateAndSubmit();">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo $msgstr["send_button"] ?? 'Send Request'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light rounded-3 sticky-top" style="top: 20px; z-index: 1;">
                <div class="card-body p-4">
                    <h5 class="card-title text-primary mb-3 fw-bold border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i> Service Conditions
                    </h5>
                    <div class="card-text small text-secondary">
                        <ul class="list-unstyled mb-0 odds-help-list">
                            <?php
                            foreach ($helpInfo as $infoLine) {
                                echo '<li class="mb-3 d-flex align-items-start"><i class="fas fa-angle-right text-primary mt-1 me-2"></i><span>' . $infoLine . '</span></li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const initialLevel = document.getElementById('level').value;
        if (initialLevel) fetchDynamicFields(initialLevel);
    });

    async function fetchDynamicFields(level) {
        const container = document.getElementById('dynamic_fields_container');
        if (!level) {
            container.innerHTML = '';
            return;
        }
        try {
            // Uses relative routing to preserve custom URL endpoints
            const response = await fetch(`?action=ajax&level=${encodeURIComponent(level)}`);
            if (!response.ok) throw new Error('Failed to load fields');
            container.innerHTML = await response.text();
        } catch (error) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading requested fields. Please try again.</div></div>';
        }
    }

    function toggleSourceInput(expectedValue) {
        const selectElement = document.getElementById("select_source");
        const otherInput = document.getElementById('tag900_other');
        if (!selectElement || !otherInput) return;

        if (selectElement.value === expectedValue) {
            otherInput.classList.remove('d-none');
        } else {
            otherInput.classList.add('d-none');
        }
    }

    function getLabelTextFor(inputId) {
        const labels = document.getElementsByTagName('label');
        for (let label of labels) {
            if (label.htmlFor === inputId) {
                return label.textContent.replace('*', '').trim();
            }
        }
        return inputId;
    }

    function validateField(inputElement) {
        const value = inputElement.value.trim();
        const label = getLabelTextFor(inputElement.id);
        const validations = inputElement.dataset.jv ? inputElement.dataset.jv.split(/\s+/) : [];

        inputElement.classList.remove('is-invalid');

        for (let rule of validations) {
            let errorMsg = null;
            switch (rule) {
                case 'required':
                    if (!value) errorMsg = `Field requires a value: <b>${label}</b>`;
                    break;
                case 'email':
                    const emailRegex = /^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i;
                    if (value && !emailRegex.test(value)) errorMsg = `Invalid email format in: <b>${label}</b>`;
                    break;
                case 'uint':
                    const intRegex = /^[0-9]+$/;
                    if (value && !intRegex.test(value)) errorMsg = `Value must be an integer: <b>${label}</b>`;
                    break;
            }
            if (errorMsg) {
                inputElement.classList.add('is-invalid');
                return `<li>${errorMsg}</li>`;
            }
        }
        return "";
    }

    function validateAndSubmit() {
        const form = document.getElementById('forma1');
        const errorBox = document.getElementById('form_errors');
        let errors = "";

        errorBox.style.display = 'none';
        errorBox.innerHTML = '';

        const categoryEl = document.getElementById("category");
        categoryEl.classList.remove('is-invalid');
        if (!categoryEl.value) {
            categoryEl.classList.add('is-invalid');
            errors += `<li>Field requires a value: <b>${getLabelTextFor('category')}</b></li>`;
        }

        const levelEl = document.getElementById("level");
        levelEl.classList.remove('is-invalid');
        if (!levelEl.value) {
            levelEl.classList.add('is-invalid');
            errors += `<li>Field requires a value: <b>${getLabelTextFor('level')}</b></li>`;
        }

        const inputs = form.querySelectorAll('input[data-jv]');
        inputs.forEach(input => {
            errors += validateField(input);
        });

        if (errors !== "") {
            errorBox.innerHTML = `<div class="alert alert-danger shadow-sm border-0"><ul class="mb-0 ps-3">${errors}</ul></div>`;
            errorBox.style.display = 'block';
            window.scrollTo({
                top: errorBox.offsetTop - 50,
                behavior: 'smooth'
            });
        } else {
            form.submit();
        }
    }
</script>