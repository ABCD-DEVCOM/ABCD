<?php
/*
** 20220112 fho4abcd make it work again+backbutton+helper+clean html
** 20220129 fho4abcd Detect copy from default in stead of current. More feedback
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../common/header.php");
?>
<body>
<script>
function Enviar(){
	document.wks.submit()
}
function Regresar(){
	document.wks.Opcion.value="saveas";
	document.wks.submit()
}
</script>
<?php
$backtoscript="../dbadmin/fmt_adm.php"; // The default return script
include("../common/institutional_info.php");
?>
<div class="sectionInfo">
	<div class="breadcrumb">
    <?php echo $msgstr["credfmt"]." &rarr; ".$msgstr["saveas"]?>
	</div>
	<div class="actions">
    <?php 
    include "../common/inc_back.php";
    include "../common/inc_home.php";
    ?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php";?>
<div class="middle form">
<div class="formContent">
<div style="text-align:center">
<form name=wks method=post action=fmt_saveas.php onsubmit="javascript:Enviar();return false">
<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
<input type=hidden name=Opcion value="save">
<input type=hidden name=fmt_name value=<?php echo $arrHttp["fmt_name"]?>>
<input type=hidden name=fmt_desc value="<?php echo $arrHttp["fmt_desc"]?>">

<p>
    <?php echo $msgstr["copyfrom"]." ".$msgstr["name"]?>= <b><?php echo $arrHttp["fmt_name"]?></b> &nbsp; &nbsp;
    <?php echo $msgstr["description"]?>= <b><?php echo $arrHttp["fmt_desc"]?></b>
</p>
<?php
if (isset($arrHttp["name"])){
	echo "<input type=hidden name=name value='". $arrHttp["name"]."'>\n";
}
if (isset($arrHttp["desc"])){
	echo "<input type=hidden name=desc value='". $arrHttp["desc"]."'>\n";
}
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"]="saveas";
switch ($arrHttp["Opcion"]){
	case "saveas":
        ?>
		<p><?php echo $msgstr["name"]?>
        <input type=text name=name size=8 maxlength=12 value=''> &nbsp; &nbsp;
		<?php echo $msgstr["description"]?>
        <input type=text size=50 maxlength=50 name=desc value=''>
        &nbsp;
        <button class="button_browse edit" type="submit"  title="<?php echo $msgstr["save"]?>">
            <i class="far fa-save"></i> </button>
        <?php
		break;
	case "save":
		GuardarWks();
}
?>
</form>
</div>
</div>
<?php
include("../common/footer.php");
//======================================================
function  GuardarWks(){
global $arrHttp,$msgstr,$db_path,$lang_db,$backtoscript;
    $newnamebase=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["name"].".fmt";
    $newname=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["name"].".fmt";
	$err="N";
	if (!isset($arrHttp["name"]) or trim($arrHttp["name"])==""){
		echo $msgstr["missing"].": ".$msgstr["name"];
		$err="S";
	}
	if (!isset($arrHttp["desc"]) or trim($arrHttp["desc"])==""){
		echo "<br>".$msgstr["missing"].": ".$msgstr["description"];
		$err="S";
	}
	if($err=="N" and !preg_match('/^[a-z0-9-_]+$/',$arrHttp["name"])) {
		echo "<h3 style='color:red'>".$arrHttp["name"]." &rarr; ".$msgstr["invalidfilename"]."</h3>";
   		$err="S";
	}
	if ($err=="N"){
		if (file_exists($newname)){
			echo "<h3 style='color:red'>".$arrHttp["name"]." &rarr; ".$msgstr["fileexists"]."</h3>";
			$err="S";
		}
	}
    $fmt_currentbase=$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["fmt_name"].".fmt";
    $fmt_current=$db_path.$fmt_currentbase;
    $fmt_default=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["fmt_name"].".fmt";
    // It is unknown if the copy is initiated from lang_db. That cannot work
    if (!file_exists($fmt_current)) {
        if (file_exists($fmt_default)) {
            $err="B";
            echo "<h3 style='color:red'>".$fmt_currentbase."&rarr;".$msgstr["ne"]."<br>";
            echo $msgstr["fmtcopybyedit"]."</h3>";
        } else {
            $err="S";
            echo "<h3 style='color:red'>".$fmt_currentbase."&rarr;".$msgstr["ne"]."</h3>";
        }
    }
	if ($err=="S"){
		echo "<p><a href=javascript:Regresar()>".$msgstr["back"]."</a>";
    } else if ($err=="B") {
        include "../common/inc_back.php";
	} else {
        copy($fmt_current,$newname);
        echo "<p>".$msgstr["created"].": ".$newnamebase."</p>";
        // Update formatos.wks
        $wksname="formatos.wks";
        $wkspath=$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$wksname;
        $wksfullpath=$db_path.$wkspath;
        $new_wks="\n".$arrHttp["name"]."|".$arrHttp["desc"];
        $res=file_put_contents ($wksfullpath , $new_wks ,FILE_APPEND);
        echo "<p>".$msgstr["updated"].": ".$wkspath;
    }
}
?>


