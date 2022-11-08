<?php
/*
20210914 fho4abcd Send option "explorar" to the folder exploration, improve html
20220214 fho4abcd message if no permission. Improved feedback message
20220224 fho4abcd ensure traling / if root specified in dr_path
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
include ("../config.php");
$lang=$_SESSION["lang"];
include("../lang/dbadmin.php");
include("../lang/admin.php");
include("../common/header.php");

$db=$arrHttp["base"];
if (!isset($_SESSION["permiso"]["CENTRAL_EDREC"]) and !isset($_SESSION["permiso"][$db."_CENTRAL_EDREC"])
and !isset($_SESSION["permiso"]["CENTRAL_CREC"])  and !isset($_SESSION["permiso"][$db."_CENTRAL_CREC"])
and !isset($_SESSION["permiso"]["CENTRAL_ALL"])
and !isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
	echo "<h4>".$msgstr["invalidright"]."</h4>";
	die;
}
?>
<body>
<script>
function Explorar(){
    msgwin_ex=window.open("","dirsexplorer","width=800,height=600,resizable,scrollbars")
    msgwin_ex.focus();
    document.dirs_explore.submit()
}
</script>
<?php
include "../common/inc_div-helper.php";
$img_path="";
if (file_exists($db_path.$arrHttp["base"]."/dr_path.def")){
	$def = parse_ini_file($db_path.$arrHttp["base"]."/dr_path.def");
    if (isset($def["ROOT"]) && trim($def["ROOT"]!="")){
        $img_path=trim($def["ROOT"]);
        $name_path=$msgstr["root_from_dr"];
        if (substr($img_path,-1)!="/" && substr($img_path,-1)!="\\" ) {
            $img_path.="/";
        }
    }
}
if ($img_path==""){
    $name_path="%path_database%".$arrHttp["base"]."/";
}

?>
<div class="middle form">
<div class="formContent">
<div align=center><h3><?php echo $msgstr["uploadfile"] ?></h3>
<form name=upload action=upload_img.php method=POST enctype='multipart/form-data'>
<table align=center>
<tr><td></td><td style='color:blue';>
    <?php echo $msgstr["empty_is_def"]." (".$name_path.").<br>".$msgstr["browse"]." ".$msgstr["to_change"];?>
</td></tr>
<tr>
    <td><?php echo $msgstr["storein"]?></td>
    <td><input type=text name=storein size=40 value="<?php if (isset($arrHttp["storein"])) echo $arrHttp["storein"]?>" onfocus=blur()>
    &nbsp;<a href=javascript:Explorar()><?php echo $msgstr["browse"]?></a></td>

<tr><td class=menusec1><?php echo $msgstr["selfile"]?></td>
    <td ><input name=userfile[] type=file size=80></td>
<tr><td colspan=2 align=center><input type=submit value='<?php echo $msgstr["uploadfile"]?>'></td>
</table>
<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
<input type=hidden name=Tag value=<?php echo $arrHttp["Tag"]?>>

</form>
<form name=dirs_explore action=dirs_explorer.php method=post target=dirsexplorer>
<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
<input type=hidden name=tag value=<?php echo $arrHttp["Tag"]?>>
<input type=hidden name=Opcion value="explorar">
</form>
</div>
</body>

</html>