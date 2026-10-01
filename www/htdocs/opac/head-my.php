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
 * 2026-09-29 rogercgui Added a hook for additional head content if the function exists.
 * -------------------------------------------------------------------------
 */

include_once(realpath(__DIR__ . '/../central/config_opac.php'));
include_once(realpath(__DIR__ . '/functions.php'));

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

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
$ActualDir = getcwd();


// Adds the cloudflare Turnstile script if captcha is enabled in OPAC.DEF
if (isset($opac_gdef['CAPTCHA']) && $opac_gdef['CAPTCHA'] === 'Y' && isset($opac_gdef['CAPTCHA_SITE_KEY'])) {
    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>' . PHP_EOL;
}

//foreach ($_REQUEST as $var => $value) echo "$var=>$value<br>";

?>

<!doctype html>
<html lang="<?php echo $lang; ?>" class="<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'dark-mode' : ''; ?>">

<head>
    <title><?php echo htmlspecialchars($TituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($Site_Description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($Site_Keywords, ENT_QUOTES, 'UTF-8'); ?>" />
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


    <?php echo $googleAnalyticsCode; ?>
    <?php echo $CustomStyle; ?>

    <?php
    // Add hook for additional head content if the function exists
    if (function_exists('abcd_run_hook')) {
        echo abcd_run_hook('opac_head_end', '');
    }
    ?>
</head>

<body class="<?php echo getDarkModeClass(); ?>">
    <?php include get_template_file("views/topbar.php"); ?>