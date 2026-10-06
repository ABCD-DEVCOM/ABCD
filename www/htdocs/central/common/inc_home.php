<?php
/*
20211220 fho4abcd Copied from inc_back.php
Function  : Show the Home button in the breadcrumb in div class actions
Usage     : <?php include "../common/inc_home.php" ?>
** Variable:none
*/
$inc_home = "/central/common/inicio.php";
if (isset($arrHttp["base"]) AND $arrHttp["base"] != "") {
    $inc_home .= "?base=" . $arrHttp["base"];
}

// Interceptor de enrutamiento
if (function_exists('abcd_run_hook')) {
    $inc_home = abcd_run_hook('abcd_nav_home_url', $inc_home);
}

?>
<a href="<?php echo $inc_home;?>" class="button_browse " title='<?php echo $msgstr["inicio"]?>'>
    <i class="fas fa-home"></i>&nbsp;<?php echo $msgstr["inicio"]?>
</a>
<?php
unset($inc_home);
