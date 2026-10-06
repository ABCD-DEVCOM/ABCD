<?php
/*
20211214 fho4abcd Created
20211216 fho4abcd Add functionality for return script of the caller
20220112 fho4abcd Do not add base= if there is already a base added
20200120 fho4abcd Check if the url has already options
20220209 fho4abcd Replace double quotes by single quotes

Function  : Show the backbutton in the breadcrumb in div class actions
Usage     : <?php include "../common/inc_back.php" ?>
** Variables
**  $arrHttp["base"]: The current database. If not set the Home page is shown
**  $backtoscript   : The return script.  If not set the Home page is shown
**  $arrHttp["backtoscript_org"]: The return script of the caller. If not set no action
**  $arrHttp["encabezado"]: Indicator to show the header. If not set no action
*/

$inc_backtourl = "";
$inc_optionset = false;
if (isset($backtoscript) and $backtoscript != "") {
    $inc_backtourl = $backtoscript;
} else {
    $inc_backtourl = "/central/common/inicio.php";
}
// Determine if we have already url parameters in the "back" script
$inc_optionset = true;
if (strpos($inc_backtourl, "?") === false) $inc_optionset = false;;

if (strpos($inc_backtourl, "base=") === false) {
    if (isset($arrHttp["base"]) and $arrHttp["base"] != "") {
        if (!$inc_optionset) $inc_backtourl .= "?";
        if ($inc_optionset) $inc_backtourl .= "&";
        $inc_backtourl .= "base=" . $arrHttp["base"];
        $inc_optionset = true;
    }
}
if (isset($arrHttp["backtoscript_org"])) {
    if (!$inc_optionset) $inc_backtourl .= "?";
    if ($inc_optionset) $inc_backtourl .= "&";
    $inc_optionset = true;
    $inc_backtourl .= "backtoscript=" . $arrHttp["backtoscript_org"];
}

if (strpos($inc_backtourl, "encabezado=") === false) {
    if (isset($arrHttp["encabezado"]) and $arrHttp["encabezado"] != "") {
        if (!$inc_optionset) $inc_backtourl .= "?";
        if ($inc_optionset) $inc_backtourl .= "&";
        $inc_optionset = true;
        $inc_backtourl .= "encabezado=" . $arrHttp["encabezado"];
    }
}
// Replace double quotes by single quotes for the href below
$inc_backtourl = str_replace('"', "'", $inc_backtourl);

// Interceptor de enrutamiento
if (function_exists('abcd_run_hook')) {
    $inc_backtourl = abcd_run_hook('abcd_nav_back_url', $inc_backtourl);
}

?>

<a href="<?php echo $inc_backtourl ?>" class="button_browse" title='<?php echo $msgstr["regresar"] ?>'>
    <i class="fas fa-arrow-circle-left"></i>&nbsp;<?php echo $msgstr["regresar"] ?></a>
<?php
unset($inc_backtourl);
unset($inc_optionset);
