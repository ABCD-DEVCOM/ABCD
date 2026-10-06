<?php
/*
20220120 fho4abcd Created from inc_back
20220121 fho4abcd typo

Function  : Show the cancelbutton in the breadcrumb in div class actions
Usage     : <?php include "../common/inc_cancel.php" ?>
** Variables
**  $arrHttp["base"]: The current database. If not set the Home page is shown
**  $backtocancelscript   : The return script.  If not set the Home page is shown
**  $arrHttp["encabezado"]: Indicator to show the header. If not set no action
*/

$inc_backtourl = "";
$inc_optionset = false;
if (isset($backtocancelscript) and $backtocancelscript != "") {
    $inc_backtourl = $backtocancelscript;
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

if (strpos($inc_backtourl, "encabezado=") === false) {
    if (isset($arrHttp["encabezado"]) and $arrHttp["encabezado"] != "") {
        if (!$inc_optionset) $inc_backtourl .= "?";
        if ($inc_optionset) $inc_backtourl .= "&";
        $inc_optionset = true;
        $inc_backtourl .= "encabezado=" . $arrHttp["encabezado"];
    }
}

// Interceptor de enrutamiento
if (function_exists('abcd_run_hook')) {
    $inc_backtourl = abcd_run_hook('abcd_nav_back_url', $inc_backtourl);
}

?>

<a href="<?php echo $inc_backtourl ?>" class="button_browse" title='<?php echo $msgstr["cancel"] ?>'>
    <i class="far fa-window-close bt-red"></i>&nbsp;<?php echo $msgstr["cancel"] ?></a>
<?php
unset($inc_backtourl);
unset($inc_optionset);
