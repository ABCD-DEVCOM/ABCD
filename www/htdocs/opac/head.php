<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   www/htdocs/opac/head.php
 *  Purpose:  Custom <head> section for OPAC interface
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 * 2025-09-14 rogercgui Added cache improvements to Dark Mode.
 * 2025-11-28 rogercgui Added Open Graph, Twitter and LinkedIn meta tags for better social media sharing.
 * 2025-06-10 rogercgui Added Cloudflare Turnstile script inclusion if CAPTCHA is enabled in OPAC.DEF.
 * 2025-05-20 rogercgui Added nonce generation for Content Security Policy
 * 2025-02-15 rogercgui Added favicon support.
 * 2025-09-05 rogercgui Improved meta tags for SEO.
 * 2025-09-22 rogercgui Added cache control headers to prevent caching issues.
 * 2025-10-01 rogercgui Added Dark Mode class to body based on cookie.
 * -------------------------------------------------------------------------
 */

include_once(dirname(__FILE__) . "/../central/config_opac.php");
include $Web_Dir . 'functions.php';
validate_inputs($_REQUEST);

// Definition of safety headers
$nonce = base64_encode(random_bytes(16));
header("X-XSS-Protection: 1; mode=block");
header("Content-Type: text/html; charset=$meta_encoding");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");
header("Cache-Control: no-cache, no-store, must-revalidate"); // ou header("Cache-Control: no-store");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Data no passado para invalidar o cache
header("Pragma: no-cache"); // Para HTTP/1.0

//include ("get_ip_address.php");
//session_cache_limiter("private_no_expire");

// FIX: Provide a fallback for HTTP_HOST to prevent undefined key warnings
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$http_host}/";
$ActualDir = getcwd();

//foreach ($_REQUEST as $var => $value) echo "$var=>$value<br>";
?>
<!doctype html>
<html lang="<?php echo $lang; ?>" class="<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'dark-mode' : ''; ?>">

<head>
    <title><?php echo htmlspecialchars($TituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($Site_Description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($Site_Keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta charset="<?php echo $meta_encoding; ?>">
    <meta name="author" content="<?php echo htmlspecialchars($TituloEncabezado, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="language" content="<?php echo $lang; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">

    <?php foreach (["og", "twitter", "linkedin"] as $prefix) : ?>
        <meta property="<?php echo $prefix; ?>:title" content="<?php echo htmlspecialchars($TituloPagina, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="<?php echo $prefix; ?>:description" content="<?php echo htmlspecialchars($Site_Description, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="<?php echo $prefix; ?>:image" content="<?php echo htmlspecialchars($link_logo, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>

    <?php if (!empty($shortIcon)) : ?>
        <link rel="icon" href="<?php echo htmlspecialchars($shortIcon, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>



    <link rel="stylesheet" href="<?php echo $OpacHttp; ?>assets/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $OpacHttp; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $OpacHttp; ?>assets/css/styles.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $OpacHttp; ?>assets/css/jquery-ui.css?<?php echo time(); ?>">


    <?php foreach (["highlight.js", "lr_trim.js", "selectbox.js", "jquery-3.6.4.min.js", "get_cookies.js", "canvas.js", "autocompletar.js", "script_b.js"] as $script) : ?>
        <script src="<?php echo $OpacHttp; ?>assets/js/<?php echo $script; ?>?<?php echo time(); ?>"></script>
    <?php endforeach; ?>

    <script>
        var OpacHttpPath = "<?php echo $link_logo; ?>/";
        const OpacLang = '<?php echo isset($lang) ? $lang : "pt"; ?>';
        const Msgstr = '<?php echo json_encode($msgstr); ?>';
        const OpacContext = '<?php echo isset($actual_context) ? $actual_context : ""; ?>';
    </script>

    <?php
    // Adds the cloudflare Turnstile script if captcha is enabled in OPAC.DEF
    if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y' && isset($opac_gdef['CAPTCHA_SITE_KEY'])) {
        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>' . PHP_EOL;
    }
    ?>


    <?php echo $googleAnalyticsCode; ?>
    <?php echo $CustomStyle; ?>

</head>

<body class="<?php echo getDarkModeClass(); ?>">
    <?php include get_template_file("views/topbar.php"); ?>

    <div class="container<?php echo $container; ?>">

        <main>
            <?php
            // A barra lateral agora é exclusiva para FACETAS/FILTROS.
            // Portanto, detectamos se estamos na página de pesquisa:
            $is_search_page = (strpos($_SERVER['PHP_SELF'], 'buscar_integrada.php') !== false);
            $show_sidebar = ($sidebar === 'Y' || $sidebar === 'SL') && $is_search_page;
            ?>

            <div class="container-fluid"> <?php
                                            // =========================================================================
                                            // LAYOUT 1: FULL-WIDTH SEARCH (Layout 'SL' or 'N' / Home)
                                            // Occupies 100% of the width, staying above the sidebar
                                            // =========================================================================
                                            if ($sidebar === 'SL' || !$show_sidebar): ?>
                    <div class="row">
                        <div class="col-12 px-4 mt-4">
                            <?php if ($is_search_page): ?><div class="collapse" id="collapseSearchForm"><?php endif; ?>

                                <div id="searchBox" class="card bg-white p-4 mb-4 rounded-0 shadow-sm">
                                    <?php
                                                switch ($search_form) {
                                                    case 'free':
                                                        include("components/search_free.php");
                                                        break;
                                                    case 'detailed':
                                                    case 'detalle':
                                                    case 'avanzada':
                                                        include("components/search_detailed.php");
                                                        break;
                                                    default:
                                                        include("components/search_free.php");
                                                        break;
                                                }
                                    ?>
                                </div>

                                <?php if ($is_search_page): ?>
                                </div><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">

                    <?php if ($show_sidebar) : ?>
                        <div class="col-12 d-md-none mb-3">
                            <button class="btn btn-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-expanded="false" aria-controls="sidebar">
                                <i class="fas fa-filter"></i> <?php echo $msgstr['sidebar_toggle'] ?? 'Filtros / Menu'; ?>
                            </button>
                        </div>

                        <?php include get_template_file("views/sidebar.php"); ?>

                        <div id="page" class="col-12 col-md-9 p-0">
                        <?php else : ?>
                            <div id="page" class="col-12">
                            <?php endif; ?>

                            <div class="pt-0" id="content">

                                <?php
                                // =========================================================================
                                // LAYOUT 2: CONTAINED SEARCH (Layout 'Y')
                                // Occupies only the space of the main column, following the loop
                                // =========================================================================
                                if ($sidebar === 'Y' && $show_sidebar): ?>
                                    <?php if ($is_search_page): ?><div class="collapse" id="collapseSearchForm"><?php endif; ?>

                                        <div id="searchBox" class="card bg-white p-4 mb-4 rounded-0 shadow-sm">
                                            <?php
                                            switch ($search_form) {
                                                case 'free':
                                                    include("components/search_free.php");
                                                    break;
                                                case 'detailed':
                                                case 'detalle':
                                                case 'avanzada':
                                                    include("components/search_detailed.php");
                                                    break;
                                                default:
                                                    include("components/search_free.php");
                                                    break;
                                            }
                                            ?>
                                        </div>

                                        <?php if ($is_search_page): ?>
                                        </div><?php endif; ?>
                                <?php endif; ?>

                                <?php $_REQUEST["base"] = $actualbase ?? ''; ?>