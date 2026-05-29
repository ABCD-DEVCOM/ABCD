<?php
/*
* This script runs in a pop-up window, launched by function Campos in campos.js
* Modifications
20250402 fho4abcd Improved html, removed obsolete code and redundant spacing
20250402 fho4abcd Added standard breadcrumb & action div. Action buttons: moved to top, modified text, hover and color
20250402 fho4abcd Added translations for js. Added standard helper and footer
20260529 rogercgui Implement type IND for indicators, added historical fallback for older databases that still use ‘S’ for indicators, added comments and improved code readability

*/
  session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value) echo "<xmp>$var=$value</xmp>";
include("../config.php");

include ("../lang/admin.php");
include("../common/header.php");

// Extract the subfields
$fp=explode("\n",$arrHttp["SubC"]);

$subc="";
foreach ($fp as $linea){
	$linea=trim($linea);
	if (trim($linea)!=""){
		$l=explode('|',$linea);
		$subc.=$l[5];
	}
}
//echo $subc;
$ix=-1;
$base=$arrHttp["base"];
if (!isset($arrHttp["is_marc"]))  $arrHttp["is_marc"]="N";
?>
<body>
<script>
<?php
if (isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"]) or  isset($_SESSION["permiso"][$base."_CENTRAL_ALL"])  or  isset($_SESSION["permiso"][$base."_ACTPICKLIST"])){
    echo "act_picklist='Y'\n";
}else{
    echo "act_picklist='N'\n";
}
echo "is_marc='".$arrHttp["is_marc"]."'\n";
echo "PickList=new Array()\n";
echo "NamePickList=new Array()\n";
echo "SubCampos=new Array()\n";

// Array para reconstruir a variável SubC global do JS legado
$subc_js_array = array();

foreach ($fp as $linea) {
	$linea = trim($linea);
	if ($linea != "") {
		$l = explode('|', $linea);

		// Processes only Indicators (IND) and Subfields (S), as per the original logic
		if (isset($l[0]) && ($l[0] == "S" || $l[0] == "IND")) {

			// Adds the entire row to the array that JavaScript uses to build the table in a rigid manner
			$subc_js_array[] = $linea;

			$code = isset($l[5]) ? trim($l[5]) : "";
			$key = $code;

			// Power the I1 and I2 switches for the indicators
			if ($l[0] == "IND") {
				$key = "I" . $code;
			}
			// Historical fallback for older databases that still use ‘S’ for indicators
			elseif (count($subc_js_array) <= 2 && isset($arrHttp["is_marc"]) && $arrHttp["is_marc"] == "S") {
				if ($code == "1" || $code == "2") {
					$key = "I" . $code;
				}
			}

			// Populate the picklists and their names based on the configuration
			if (isset($l[11]) && trim($l[11]) != "") {
				echo "NamePickList['" . $key . "']='" . addslashes(trim($l[11])) . "'\n";
				PickList($key, trim($l[11]));
			} else {
				echo "PickList['" . $key . "']=''\n";
			}

			// Alimenta os rótulos de forma indexada
			$label = isset($l[2]) ? trim($l[2]) : $key;
			echo "SubCampos['" . $key . "']='" . addslashes($label) . "'\n";
		}
	}
}

// RECONSTRUCT THE SUBC VARIABLE
// The JavaScript 'editarocurrencias.js' performs a .split("\\n") on it and bases the entire for loop on it
$subc_string_final = implode("\\n", $subc_js_array);
echo "SubC = \"" . addslashes($subc_string_final) . "\";\n";

// Next statements define variables for the textstrings used in the js files
echo "trn_mod_picklist=\"".$msgstr["mod_picklist"]."\"\n";
echo "trn_reload_picklist=\"".$msgstr["reload_picklist"]."\"\n";
echo "trn_addoccur=\"".$msgstr["addoccur"]."\"\n";
echo "trn_deloccur=\"".$msgstr["deloccur"]."\"\n";
echo "trn_fillempty=\"".$msgstr["fillempty"]."\"\n";
echo "trn_moveoccdown=\"".$msgstr["moveoccdown"]."\"\n";
echo "trn_moveoccup=\"".$msgstr["moveoccup"]."\"\n";
echo "trn_occlist=\"".$msgstr["occlist"]."\"\n";
echo "trn_movesubfdown=\"".$msgstr["movesubfdown"]."\"\n";
echo "trn_movesubfup=\"".$msgstr["movesubfup"]."\"\n";
echo "trn_occsearch=\"".$msgstr["occsearch"]."\"\n";

?>
function RefrescarPicklist(tabla,Ctrl,valor){
	ValorOpcion=valor
	document.refrescarpicklist.picklist.value=tabla
	document.refrescarpicklist.Ctrl.value=Ctrl
	document.refrescarpicklist.valor.value=valor
	msgwin=window.open("","Picklist","width=20,height=10,scrollbars, resizable")
	document.refrescarpicklist.submit()
	msgwin.focus()
}

function AgregarPicklist(tabla,Ctrl,valor){
	ValorOpcion=valor
	document.agregarpicklist.picklist.value=tabla
	document.agregarpicklist.Ctrl.value=Ctrl
	document.agregarpicklist.valor.value=valor
	msgwin=window.open("","Picklist","width=600,height=500,scrollbars, resizable")
	document.agregarpicklist.submit()
	msgwin.focus()
}

//SE ACTUALIZA EL SELECT CON LA TABLA ACTUALIADA
ValorTabla=""
SelectName=""
ValorOpcion=""
function AsignarTabla(){
	opciones=ValorTabla.split('$$$$')
	var Sel = document.getElementById(SelectName);
	Sel.options.length = 0;
	var newOpt =Sel.appendChild(document.createElement('option'));
	newOpt.text = "";
	newOpt.value = " ";
	for (x in opciones){
		op=opciones[x].split('|')
		if (op[0]=="")
			op[0]=op[1]
		if (op[1]=="")
			op[1]=op[0]
		var newOpt =Sel.appendChild(document.createElement('option'));
		newOpt.text = op[1];
		newOpt.value = op[0];
		if (op[0]==ValorOpcion)
			newOpt.selected=true
	}
}

function EnviarArchivo(Tag,subc){
	msgwin=window.open("","Upload","status=yes,resizable=yes,toolbar=no,menu=no,scrollbars=yes,width=750,height=180,top=100,left=5");
	msgwin.document.close();
	msgwin.document.writeln("<html><title><?php echo $msgstr["uploadfile"]?></title><body link=black vlink=black bgcolor=white>\n");
	msgwin.document.writeln("<form name=upload action=upload_img.php method=POST enctype=multipart/form-data>\n");
	msgwin.document.writeln("<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>\n");
	msgwin.document.writeln("<input type=hidden name=Tag value="+Tag+">")
	msgwin.document.writeln("<input type=hidden name=subc value=\""+subc+"\">")
	msgwin.document.writeln("  <?php echo $msgstr["storein"]?>: <input type=text name=storein size=40 value=\"\" onfocus=blur()>\n");
	msgwin.document.writeln(" <a href=dirs_explorer.php?Opcion=explorar&base=<?php echo $arrHttp["base"]?>&tag="+Tag+" target=_blank>explorar</a>")
	msgwin.document.writeln("<br>");
	msgwin.document.writeln("<table width=100%>");
	msgwin.document.writeln("<tr><td class=menusec1><?php echo $msgstr["archivo"]?></td><td class=menusec1></td>\n");
	msgwin.document.writeln("<tr><td><input name=userfile[] type=file size=50></td><td></td>\n");
	msgwin.document.writeln("</table>\n");
	msgwin.document.writeln("  <input type=submit value='<?php echo $msgstr["uploadfile"]?>'>\n");
	msgwin.document.writeln("</form>\n");
	msgwin.document.writeln("</body>\n");
	msgwin.document.writeln("</html>\n");
	msgwin.focus()  ;
}
</script>
<script language="JavaScript" type="text/javascript" src=js/terminoseleccionado.js?<?php echo time(); ?>></SCRIPT>
<script language="JavaScript" type="text/javascript" src=js/lr_trim.js></SCRIPT>
<script language=javascript>
base=window.opener.top.base
url_indice=""
Ctrl_activo=""
function getElement(psID) {
	if(!document.all) {
		return document.getElementById(psID);

	} else {
		return document.all[psID];
	}
}

function AbrirIndiceAlfabetico(xI,Prefijo,SubC,Separa,db,cipar,tag,Formato){
	Ctrl_activo=getElement(xI)
	document.forma1.Indice.value=xI
	Separa="&delimitador="+Separa
	Prefijo=Separa+"&tagfst="+tag+"&prefijo="+Prefijo
	ancho=200
	url_indice="capturaclaves.php?opcion=autoridades&base="+db+"&cipar="+cipar+"&Tag="+xI+Prefijo+"&indice="+xI+"&repetible=0"+"&Formato="+Formato+"&postings=10&sfe=s"
	msgwin=window.open(url_indice,"indice","width=600, height=520,resizable, scrollbar")
	msgwin.focus()
	return
}

function AbrirTesauro(Tag,base,prefijo){
	Url="../tesaurus/index.php?base="+base+"&Tag="+Tag
	myleft=screen.width-450
	msgwin=window.open(Url,"Tesauro","width=450, height=530,  scrollbars, status, resizable location=no, left="+myleft)
	msgwin.focus()
}

function AbrirIndice(ira){
	url_indice=url_indice+ira
	ancho=screen.width-500-20
	msgwin=window.open(url_indice,"Indice","status=yes,resizable=yes,toolbar=no,menu=yes,scrollbars=yes,width=600,height=520,top=20,left="+ancho)
	msgwin.focus()
}
</script>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr["editsubctag"]. ": ".$arrHttp["tag"]?>
    </div>
    <div class="actions">
	<a class="bt bt-blue" href=javascript:AceptarCambios() title="<?php echo $msgstr["updateocclist"]?>">
		<i class="far fa-eye"></i> <?php echo $msgstr["viewocclist"]?></a>
	<a class="bt bt-green" href=javascript:ActualizarForma() title="<?php echo $msgstr["updatedeform"]?>">
		<i class="fas fa-share"></i> <?php echo $msgstr["actualizar"]?></a>
	<a class="bt bt-red" href="javascript:self.close()">
		<i class="fas fa-times"></i> <?php echo $msgstr["cancelar"]?></a>
	&nbsp;
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php
include "../common/inc_div-helper.php";
?>
<div class="middle form">
	<div class="formContent">

<form name=agregarpicklist action=../dbadmin/picklist_edit.php method=post target=Picklist>
   <input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
   <input type=hidden name=picklist>
   <input type=hidden name=Ctrl>
   <input type=hidden name=valor>
   <input type=hidden name=desde value=dataentry>
</form>

<form name=refrescarpicklist action=../dbadmin/picklist_refresh.php method=post target=Picklist>
   <input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
   <input type=hidden name=picklist>
   <input type=hidden name=Ctrl>
   <input type=hidden name=valor>
   <input type=hidden name=desde value=dataentry>
</form>

<form name=forma1>
    <input type=hidden name=tagcampo>
    <input type=hidden name=occur>
    <input type=hidden name=ep>
    <input type=hidden name=NoVar>
    <input type=hidden name=Indice value="">
    <input type=hidden name=base>
    <input type=hidden name=cipar>
    <input type=hidden name=Formato>
    <input type=hidden name=Repetible>
    <input type=hidden name=Indice>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class=td2>
	<tr>
	    <td>
		<script language="JavaScript" type="text/javascript" src=js/editarocurrencias.js?<?php echo time(); ?>></SCRIPT>
	    </td>
	<tr>
	    <td id="asubc">
	        <!-- the content of this cell is created/modified by functions in editarocurrencias.js-->
	    </td>
    </table>
</form>
<script language=javascript>
  	if (Occ>0) {
  		TerminoSeleccionado()
  	}else{
  		Redraw("")
  	}
</script>
</div>
</div>
<?php include("../common/footer.php")?>

<?php

// ===============================================
function PickList($ix,$file){
global $db_path,$lang_db,$arrHttp;
	$Options="";
	$archivo=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$file;
	if (!file_exists($archivo)) $archivo=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$file;
	$fp=array();
	if (file_exists($archivo)){
		$fp=file($archivo);
		$Options="";
		foreach ($fp as $value) {
			$value=rtrim($value);
			if ($value!=""){
				$Options.=$value.'$$$$';
			}
		}
	}else{
		$Options='$$$$';
	}

	echo "PickList['$ix']='".str_replace("'","&#39;",$Options)."'\n";
}
?>