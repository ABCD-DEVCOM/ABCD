<?php
/*
20210921 fho4abcd Button in form + inc_div-helper + sanitize html + remove unused VerificarTipo + translations
20211216 fho4abcd Backbutton by included file
*/
session_start();
unset($_SESSION["DCIMPORT"]);
unset($_SESSION["CISIS"]);
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}else{
	if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) and !isset($_SESSION["permiso"]["CENTRAL_CRDB"])){
		header("Location: ../common/error_page.php") ;
	}
}
unset($_SESSION["FDT"]);
unset($_SESSION["PFT"]);
unset($_SESSION["FST"]);
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include("../common/get_post.php");
include ("../config.php");
$lang=$_SESSION["lang"];



include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");

//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php")
?>
<body>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script language=javascript>
function Validar(){
	cisisv=""
	ix=document.forma1.CISIS_VERSION.length
	for (i=0;i<ix;i++){
		if (document.forma1.CISIS_VERSION[i].checked) cisisv=document.forma1.CISIS_VERSION[i].value
	}
	if (cisisv==""){
		alert("<?php echo $msgstr["falta"]." CISIS VERSION"?>")
		return
	}
	unicode=""
	ix=document.forma1.UNICODE.length
	for (i=0;i<ix;i++){

		if (document.forma1.UNICODE[i].checked) {
			unicode=document.forma1.UNICODE[i].value
		}
	}
	if (unicode==""){
		alert("<?php echo $msgstr["falta"]." UNICODE"?>")
		return
	}
	dbn=Trim(document.forma1.nombre.value)
	if (dbn==""){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["dbn"]?>")
		return
	}
	var alphaExp = /^[a-zA-Z_0123456789-]+$/;
    if(dbn.match(alphaExp)){

    }else{
        alert("<?php echo $msgstr["invalidfilename"]?>");
        document.forma1.nombre.focus();
        return
    }
    document.forma1.base.value=dbn.toLowerCase()
    document.forma1.nombre.value=dbn.toLowerCase()
	ix=document.forma1.base_sel.options.length
	for (i=1;i<ix;i++){
		if (document.forma1.base_sel.options[i].value==dbn){
			alert("<?php echo $msgstr["dbexists"]?>")
			return
		}
	}
	desc=Trim(document.forma1.desc.value)
	if (desc==""){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["descripcion"]?>")
		return
	}
	ix=document.forma1.base_sel.selectedIndex
	if (ix<1){
		alert("<?php echo $msgstr["falta"]." ".$msgstr["cpdb"]?>")
		return
	}
	switch(ix){
		case 1:

			document.forma1.desc.value=desc
			document.forma1.action="fdt.php"
			document.forma1.Opcion.value="new"
			document.forma1.submit()
			break
		case 2:
			document.forma1.action="winisis.php"
			document.forma1.submit()
			break
		default:
			document.forma1.action="crearbd_ex_copy.php"
			document.forma1.submit()
			break
	}

}
</script>
<?php
if (isset($arrHttp["encabezado"])){
	include("../common/institutional_info.php");
	$encabezado="s";
}else{
	$encabezado="";
}
?>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr["createdb"]?>
    </div>
    <div class="actions">
    <?php include "../common/inc_back.php"; ?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php
include("../common/inc_div-helper.php");
?>
<div class="middle form">
<div class="formContent">
	<form method=post name=forma1 onsubmit="javascript:return false">
		<input type=hidden name=Opcion>
		<input type=hidden name=base>
		<?php if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=s>\n"?>
        <div id="formRow01" class="formRow">
            <label for="field01"><strong><?php echo $msgstr["cisis_version"]?></strong></label>
            <div class="frDataFields">
                <?php
                $CIV=explode(";",$cisis_versions_allowed);
                foreach ($CIV as $v){
                    echo "<input type=radio name=\"CISIS_VERSION\" value=\"$v\">&nbsp;$v &nbsp; &nbsp;";
                }
                ?>
                <p>
            </div>
            <div class="spacer">&#160;</div>
        </div>
        <div id="formRow01" class="formRow">
            <label for="field01"><strong>UNICODE</strong></label>
            <div class="frDataFields">
                <input type="radio" name="UNICODE" value="0">&nbsp;<?php echo $msgstr["uni_no"]?>&nbsp;&nbsp;
                <input type="radio" name="UNICODE" value="1">&nbsp;<?php echo $msgstr["uni_yes"]?>
                <p>
            </div>
            <div class="spacer">&#160;</div>
        </div>
        <div id="formRow01" class="formRow">
            <label for="field01"><strong><?php echo $msgstr["dbn"]?></strong></label>
            <div class="frDataFields">
                <input type="text" name="nombre"  id="field01" value="" class="textEntry singleTextEntry" onfocus="this.className = 'textEntry singleTextEntry textEntryFocus';document.getElementById('formRow01').className = 'formRow formRowFocus';" onblur="this.className = 'textEntry singleTextEntry';document.getElementById('formRow01').className = 'formRow';" />
                <p>
            </div>
            <div class="spacer">&#160;</div>
        </div>
        <div id="formRow02" class="formRow">
            <label for="field02"><strong><?php echo $msgstr["descripcion"]?></strong></label>
            <div class="frDataFields">
                <input type=text name="desc" id="field02" class="textEntry singleTextEntry" onfocus="this.className = 'textEntry singleTextEntry textEntryFocus';document.getElementById('formRow02').className = 'formRow formRowFocus';" onblur="this.className = 'textEntry singleTextEntry';document.getElementById('formRow02').className = 'formRow';">
                <p>
            </div>
            <div class="spacer">&#160;</div>
        </div>

        <div id="formRow3" class="formRow formRowFocus">
            <label for="field3"><strong><?php echo $msgstr["createfrom"]?>:</strong></label>
            <div class="frDataFields">
                <select name="base_sel" id="field3"  class="textEntry singleTextEntry">
                    <option value=""></option>
                    <option value="~~NewDb"><?php echo $msgstr["newdb"]?></option>
                    <option value="~~WinIsis"><?php echo $msgstr["winisisdb"]?></option>
                    <?php
                    $fp = file($db_path."bases.dat");
                    $bdatos=array();
                    foreach ($fp as $linea){
                        if (trim($linea)!="") {
                            $bdatos[]=$linea;
                            $b=explode('|',$linea);
                            $llave=$b[0];
                            if ($llave!="acces") echo "<option value=$b[0]>".$b[1];
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="spacer">&#160;</div>
        </div>
        <div align=center>
            <table>
            <tr><td>
                <a href="javascript:Validar()" class="singleButton singleButtonSelected" >
                    <?php echo $msgstr["continuar"]?> <i class="fas fa-arrow-right"></i>
                </a>
            </td></tr>
            </table>
        </div>
</form>
</div>
</div>
<?php include("../common/footer.php");?>

