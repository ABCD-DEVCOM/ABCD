<?php
/* modifications
2021-02-25 fho4abcd moved profile and favicon to standard location. Favicon to png (see index.php).Non functional mods for readability
2022-06-13 fho4abcd simplified DOCTYPE
2025-12-23 fho4abcd Update for HTML5: html tag with language+ update meta tags. Remove timestamp from css link
		Note that everything was and is cached
		Note that a timestamp on css files does not prevent caching of included files
2026-03-20 fho4abcd. Add client IP check. Fires normally only in case of a hack
*/
if (isset($charset))
	$content_charset = $charset;
else
	$content_charset = $meta_encoding;

if (!isset($css_name))
	$css_name = "";
else
	$css_name .= "/";

$htmllang = "";
if (isset($lang)) $htmllang = $lang;

echo abcd_run_hook('abcd_header_end', '');
?>
<!DOCTYPE html>
<html lang=<?php echo $lang; ?>>

<head>
	<title>ABCD <?php if (isset($institution_name))  echo "| " . $institution_name ?></title>
	<meta name="keywords" content="">
	<meta name="description" content="">
	<meta name="robots" content="noindex"><!-- robots.txt file is more effective -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="<?php echo $content_charset; ?>">

	<!-- Favicons -->
	<link rel="mask-icon" href="/assets/images/favicons/favicon.svg" color="#fff"><!-- for apple -->
	<link rel="icon" type="image/svg+xml" href="/assets/images/favicons/favicon.svg">

	<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicons/favicon-16x16.png">

	<link rel="apple-touch-icon" sizes="60x60" href="/assets/images/favicons/favicon-60x60.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/assets/images/favicons/favicon-76x76.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/assets/images/favicons/favicon-120x120.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/assets/images/favicons/favicon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicons/favicon-180x180.png">

	<!-- Stylesheets -->
	<link rel="stylesheet" rev="stylesheet" href="/assets/css/template.css" type="text/css" media="screen">

	<!--FontAwesome-->
	<link href="/assets/css/all.min.css" rel="stylesheet">

	<?php
	include("css_settings.php");
	?>
</head>
<?php
/*
Check Client IP if a database is set for all scripts except the home page
*/
$including_file = pathinfo(debug_backtrace()[0]['file'])['basename'];
if ($including_file != "homepage.php") {
	if (isset($arrHttp['base'])) {
		include("inc_ip_check.php");
		$clientIP = getClientIP();
		if (checkClientIP($clientIP, $arrHttp['base']) === false) { ?>
			<div id="ip_not_allowed" style="width: 100%; background-color: #ffc107; text-align: center;">
				<?php
				echo "Client IP (" . $clientIP . ") is invalid for database " . $arrHttp['base'] . "<br>Attempt to hack ??<br>";
				?>
			</div><?php
					unset($arrHttp['base']);
					exit;
				}
			}
		}
