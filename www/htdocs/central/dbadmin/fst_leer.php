<?php
/* Modifications
2026-11-11 fho4abcd. Rewritten: Better css, better top row, better buttons, separate pop-up windows
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])){
    header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var => $value)     echo "$var = $value<br>";
include ("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");

include("../common/header.php");
?>
<body>
<style>
    td{
        font-family:arial;
        font-size:12px;
        background-color:white;
        vertical-align:top;
    }
</style>
<script>
function Showfdt(){    msgwin=window.open("","Fdt","width=400,height=400,resizable,scrollbars=yes")
    msgwin.focus()}
function Showsearchform(){
    msgwin=window.open("","Srch","width=400,height=400,resizable,scrollbars=yes,top=100")
    msgwin.focus()
}
</script>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["fst"] ?>
        </div>
        <div class="actions">
            <a href="fdt_leer.php?base=<?php echo $arrHttp["base"]?>" class="bt bt-blue" target=Fdt onclick=Showfdt()
                title="<?php echo $msgstr["displayfdt"]?>">FDT</a>
             <a href="adform_leer.php?base=<?php echo $arrHttp["base"]?>" class="bt bt-blue" target=Srch  onclick=Showsearchform()
                title="<?php echo $msgstr["advsearch"].":".$msgstr["catalogacion"]?>"><?php echo $msgstr["advsearch"]?></a>
            <!-- No other actions. The only valid would be close and this will not work if this file is embedded -->
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php
    include "../common/inc_div-helper.php";
    ?>
<br>
<table bgcolor=#EEEEEE>
    <tr>
        <th>ID</th>
        <th style="text-align:center"><?php echo $msgstr["itech"]?></th>
        <th><?php echo $msgstr["extrpft"]?></th>
    </tr>
<?php
$fst=$db_path.$arrHttp["base"]."/data/".$arrHttp["base"].".fst";
if (file_exists($fst)){
    $fp=file($fst);
    foreach ($fp as $value){
        if (trim($value)!=""){
            $ix=strpos($value," ");
            $id=trim(substr($value,0,$ix));
            $value=trim(substr($value,$ix));
            $ix=strpos($value," ");
            $ti=trim(substr($value,0,$ix));
            $format=stripslashes(trim(substr($value,$ix+1)));
            echo "<tr><td>$id</td>";
            echo "<td style=\"text-align:center\">$ti</td>";
            echo "<td style=\"font-family:courier new\">$format</td>";
        }
    }
}else{
    echo "<tr><td colspan=4 style='color:red'>Error:".$fst." does not exist</td></tr>";    echo "<tr><td>&nbsp</td><td>&nbsp</td><td>&nbsp;</td>";}
?>
</table>
<br>

</body>
</html>