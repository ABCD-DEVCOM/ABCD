<?php
/*
20220313 fho4abcd Rewritten. Can run in normal window now, update of the file in this script
20220316 fho4abcd Only one update
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include ("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");;
include("../lang/dbadmin.php");;
$archivo=str_replace("\\","/",$arrHttp["archivo"]);
$base="";
$confirmcount=0;
$backtoscript="databases_list.php";
if (isset($arrHttp["base"])) $base=$arrHttp["base"];
if (isset($arrHttp["confirmcount"])) $confirmcount=$arrHttp["confirmcount"];
if (isset($arrHttp["backtoscript"])) $backtoscript=$arrHttp["backtoscript"];
include("../common/header.php");
?>
<body>
<script>
function Enviar(){
	document.update.submit()
}
</script>
<?php
include("../common/institutional_info.php");
?>
<div class="sectionInfo">
    <div class="breadcrumb">
    <?php echo $msgstr["dblist"] ?>
    </div>
    <div class="actions">
		<?php
        include "../common/inc_back.php";
		include "../common/inc_home.php";
        $savescript="javascript:Enviar()";
        if ($confirmcount==0) include "../common/inc_save.php";
        ?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<div class="middle form">
<div class="formContent">
<?php
if ( $confirmcount==0 ) {
    $confirmcount++;
    // Show and edit the filecontent
    if (file_exists($db_path.$archivo))
        $fp=file($db_path.$archivo);
    else
        $fp=array();
    ?>
    <form name=update method=post >
    <input type=hidden name=confirmcount value=<?php echo $confirmcount?>>
    <input type=hidden name=archivo value='<?php echo $arrHttp["archivo"];?>'>
    <input type=hidden name=base value="<?php echo $base;?>">
    <input type=hidden name=backtoscript value="<?php echo $backtoscript;?>">
<textarea name=txt rows=20 cols=100 style="font-family:courier">
<?php
//left adjusted code to ensure leftadjusted text content
foreach ($fp as $value) echo $value;
?>
</textarea>
    <br>
    <a class="bt bt-green" href="javascript:Enviar()" ><i class="far fa-save"></i> <?php echo $msgstr["actualizar"]?></a>
    <a class="bt bt-gray" href="<?php echo $backtoscript;?>"><i class="far fa-window-close"></i> &nbsp;<?php echo $msgstr["cancel"]?></a>

    </form>
    <?php
} else if ($confirmcount>0 ) {
    // Write the file if this was a "next" run
    $arrHttp["txt"]=stripslashes($arrHttp["txt"]);
    $arrHttp["txt"]=str_replace("\"",'"',$arrHttp["txt"]);
    $arrHttp["txt"]=str_replace(PHP_EOL.PHP_EOL,PHP_EOL,$arrHttp["txt"]);
    $archivo=$arrHttp["archivo"];
    $fp=fopen($db_path.$archivo,"w");
    fputs($fp,$arrHttp["txt"]);
    fclose($fp);
    echo "<h4 style='text-align:center'>";
    echo $archivo." ".$msgstr["updated"];
    echo "</h4>";
}
?>
</div></div>
<?php
include("../common/footer.php");
?>