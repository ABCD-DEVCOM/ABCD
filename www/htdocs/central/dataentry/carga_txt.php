<?php
/*
20220106 fho4abcd recreated accidentally deleted file. Included files for helper and back, improve html, add footer
20261107 Added support for conversion tables with a header row ("[HEADER]" mode) and loading the text from a file.
20261107 rogercgui Added upload support for conversion tables with a header row ("[HEADER]" mode) and loading the text from a file.
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include ("../config.php");
$lang=$_SESSION["lang"];

include("../lang/admin.php");
include("../lang/soporte.php");
include("carga_txt_hdr.php");

include("../common/header.php");
//foreach ($arrHttp as $var => $value) echo "$var = $value<br>";
$backtoscript="../dataentry/administrar.php"; // The default return script
if ( isset($arrHttp["backtoscript"])) $backtoscript=$arrHttp["backtoscript"];
$importtxtmsg=$msgstr["procesar"]." Text ".$msgstr["cnv_import"];

?>
<body>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<script language="JavaScript">
function LeerTxt(){
	url="carga_txt_ver.php?base=<?php echo $arrHttp["base"]."&cnv=".$arrHttp["cnv"]?>"
	msgwin=window.open(url,"","menu=no,scrollbars=yes,status=yes,width=800,height=380,resizable")
	msgwin.focus()
}

function ValidarRotulos(){
	a=Trim(document.FCKfrm.bdd.value)
	if (a==""){
		alert("<?php echo $msgstr["cnv_paste"]?>")
		return
	}
	msgwin=window.open("","Validacion","")
	document.RotulosFrm.Texto.value=a
	document.RotulosFrm.submit()
	msgwin.focus()
}
function Actualizar(){
	a=Trim(document.FCKfrm.bdd.value)
	if (a==""){
		alert("<?php echo $msgstr["cnv_paste"]?>")
		return
	}
	/* msgwin=window.open("","Actualizacion","resizable, scrollbars");*/
	document.FCKfrm.target="Actualizacion"
	document.FCKfrm.submit()
	msgwin.focus()
}
function CarregarArquivo(inp){
	if (!inp.files || inp.files.length==0) return
	var f=inp.files[0]
	var enc=document.getElementById("txtenc").value
	var rd=new FileReader()
	rd.onload=function(e){
		var t=e.target.result
		if (enc=="auto" && t.indexOf("\uFFFD")!=-1){
			// not valid UTF-8: read it again as ANSI (Windows-1252)
			var rd2=new FileReader()
			rd2.onload=function(e2){ PonerTexto(e2.target.result,f.name) }
			rd2.readAsText(f,"windows-1252")
			return
		}
		PonerTexto(t,f.name)
	}
	rd.readAsText(f,(enc=="auto")?"UTF-8":enc)
}
function PonerTexto(t,nombre){
	if (t.charCodeAt(0)==0xFEFF) t=t.substring(1)
	document.FCKfrm.bdd.value=t
	var n=t.replace(/\s+$/,"").split("\n").length
	document.getElementById("txtinfo").textContent=nombre+" - "+n+" "+document.getElementById("txtlbl").textContent
}
function EditConversion(){
    document.FCKfrm.action="../dataentry/carga_txt_cnv.php"
	document.FCKfrm.submit()
}
</script>

<div class="sectionInfo">
	<div class="breadcrumb">
    <?php echo $msgstr["cnv_".$arrHttp["accion"]]." ".$msgstr["cnv_".$arrHttp["tipo"]]?>
	</div>
	<div class="actions">
    <?php include "../common/inc_back.php"?>
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php";?>
<div class="middle form">
<div class="formContent">
    <div align=center>
    <h3><?php echo $msgstr["cnv_paste"]?></h3>
    <form action="carga_txt_ex.php" method="post"  name=FCKfrm >
    <input type=hidden name=base value="<?php echo $arrHttp["base"]?>">
    <input type=hidden name=cnv value="<?php echo $arrHttp["cnv"]?>">
    <input type=hidden name=cipar value="<?php echo $arrHttp["base"]?>.par">
    <input type=hidden name=accion value="import">
    <input type=hidden name=tipo value="txt">
    <div style="margin-bottom:10px">
        <?php echo $msgstr["txtimp_file"]?>:
        <input type="file" id="txtfile" accept=".csv,.tsv,.txt,text/plain,text/csv,text/tab-separated-values" onchange="CarregarArquivo(this)">
        &nbsp; <?php echo $msgstr["txtimp_enc"]?>:
        <select id="txtenc" onchange="if(document.getElementById('txtfile').files.length) CarregarArquivo(document.getElementById('txtfile'))">
            <option value="auto"><?php echo $msgstr["txtimp_enc_auto"]?></option>
            <option value="UTF-8">UTF-8</option>
            <option value="windows-1252">ANSI (Windows-1252)</option>
        </select>
        &nbsp; <span id="txtinfo"></span><span id="txtlbl" style="display:none"><?php echo $msgstr["txtimp_lines"]?></span>
    </div>
    <textarea rows=10 cols=150 name=bdd value=""></textarea><br><br>

    <a href="javascript:LeerTxt()" class="bt bt-gray" title='<?php echo $msgstr["cnv_dispcnvtab"]?>'>
        <i class="fas fa-tv"></i>&nbsp;<?php echo $msgstr["cnv_dispcnvtab"]?></a>&nbsp;

    <a href="javascript:EditConversion()" class="bt bt-gray" title='<?php echo $msgstr["cnv_aedit"]?>'>
        <i class="fas fa-edit"></i>&nbsp;<?php echo $msgstr["cnv_aedit"]?></a>&nbsp

    <a href="javascript:ValidarRotulos()" class="bt bt-gray" title='<?php echo $msgstr["validate"]?>'>
        <i class="fas fa-clipboard-check"></i>&nbsp;<?php echo $msgstr["validate"]?></a>&nbsp

    <a href="javascript:Actualizar()" class="bt bt-green" title='<?php echo $importtxtmsg?>'>
        <i class="fas fa-check"></i>&nbsp;<?php echo $msgstr["cnv_import"]?></a>
    </form>

    <form name=RotulosFrm action=valida_rotulos.php target=Validacion method=post>
    <input type=hidden name=Texto>
    <input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
    <input type=hidden name=cnv value=<?php echo $arrHttp["cnv"]?>>
    </form>
</div>
</div>
<?php
include "../common/footer.php";

