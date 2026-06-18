<?php
/* Modifications
2021-03-20 guilda Error when searching due to quoting
2021-04-23 fho4abcd Line endings,body tag
2021-06-10 fho4abcd Remove password argument
2021-07-07 fho4abcd Improve leader reformat (was broken since update to OPAC)
2021-07-22 fho4abcd Repair PHP errors due to previous (Improve leader format...)
20220711 fho4abcd Use $actparfolder as location for .par files
20221222 fho4abcd Translations+new style buttons for search dialog. Add div-helper
20230106 fho4abcd Don't set arrHttp values to **INVALID** if they contain the string "script"+
		  debug parameter for ActualizarRegistro +
		  check existence of indexvalue in creation of wks values in case "reintentar"
20230119 fho4abcd Remove scripts in saved display+improve html for saved display
20230120 fh04abcd Improved html+remove edit scripts if display is not in edit mode+defaults for $tl and $nr
20230130 fho4abcd Improve setting of browseby menu. Code for showing record value improved and extended for selected records
20230210 fho4abcd Show backbutton of actualized record for non-dataentry cases (e.g. acces/users/...). Remove unused cases
20230321 rogercgui Updates compatibility for PHP versions higher than 8xx: $tag=(int)trim(substr($linea,0,4))*(int)1;
20240128 fho4abcd Show always the footer. Shows only one div-helper/toolbar in case an edit without a format is done
20240425 fho4abcd Improve html
20251201 rogercgui Sets default value to 1 if the value from is lost - corrects the format change after searching
20251211 fho4abcd Code indented correctly and comments translated (diff is now useless but it reads much better)
		  Improve html due to open form by scripts_dataentry.php
		  Translated some texts and added some comments
20251223 fho4abcd HTML5
20260617 rogercgui Fix login undefined error in PHP 8.1+ when session variable is not set (e.g. when session expires and user tries to save a record)
*/

/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      fmt.php
 * @desc:      Search form for z3950 record importing
 * @author:    Guilda Ascencio
 * @since:     20091203
 * @version:   1.0
 *
 * == BEGIN LICENSE ==
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Lesser General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * == END LICENSE ==
 */
session_start();
unset($_SESSION["REC_PASS"]);
set_time_limit(0);
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
	die;
}
$lang=$_SESSION["lang"];
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING );
global $arrHttp, $valortag,$variables,$maxmfn;
global $xEditor,$xUrlEditor,$Marc,$Leader,$fdt,$tab_prop,$Html_ingreso,$tl,$nr;
$tab_prop="";
$ArchivoTexto="";  //to enter the name of the text file for continuation
$FdtHtml="";
$kardex="";   //to indicate the presence of a KK
$valortag = Array();
$arrHttp = Array();
$fdt = Array();
$tipom = "";
$fondocelda="#ffffff";
$llamada="";
$query="";
$ver=false;
$base="";
$marc=false;
$ixicampo=-1;
$maxmfn=0;
$etiqueta=Array();
$tl="";
$nr="";
$Mfn="";
$Html_ingreso="";
$Rtl="";
$Rnr="";

require_once("../common/get_post.php");
if (isset($_REQUEST["Expresion"])) $_REQUEST["Expresion"]=urldecode($_REQUEST["Expresion"]);

// debug
//if (isset($arrHttp["ValorCapturado"])) echo "<xmp>".$arrHttp["ValorCapturado"]."</xmp>";
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>"; //die;
//die;
require_once("../config.php");

require_once ('leerregistroisis.php');
require_once ("combo_inc.php");  //Procesar el combo box
require_once ('dibujarhojaentrada.php');
require_once ('actualizarregistro.php');
require_once ('plantilladeingreso.php');

require_once ("../lang/dbadmin.php");
require_once ("../lang/admin.php");
include("../lang/acquisitions.php");

//PARA COLOCAR EL ENCODING DE LA PÁGINA CORRECTAMENTE DE ACUERDO AL CHARSET DE LA BASE DE DATOS
$dummy=$meta_encoding;
$meta_encoding=$charset;
include("../common/header.php");
?>
<body>
<script src=../dataentry/js/selectbox.js></script>
<?php

// ---------------- functions ----------------
function InsertarEnlaces($base){
    global $msgstr;
    // Insert link to display the fst
    ?>&nbsp; &nbsp; <a class='bt bt-blue' href="../dbadmin/fst_leer.php?base='<?php echo $base;?>'"
    title="<?php echo $msgstr['show'].' '.$msgstr['fst'];?>" target=_blank>FST</a>
    <?php
}

function ReadWorksheetsRights(){
    global $db_path,$arrHttp,$msgstr,$lang_db;
    //READ THE DATAENTRY WORKSHEET TO DETERMINE THE AVAILABILITY FOR THE OPERATOR
    if (file_exists($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/formatos.wks")){
	$fp = file($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/formatos.wks");
    }else{
	if (file_exists($db_path.$arrHttp["base"]."/def/".$lang_db."/formatos.wks"))
	    $fp = file($db_path.$arrHttp["base"]."/def/".$lang_db."/formatos.wks");
    }
    $i=0;
    $wks_p=array();
    if (isset($fp)) {
	foreach($fp as $linea){
	    if (trim($linea)!="") {
		$linea=trim($linea);
		$l=explode('|',$linea);
		$cod=trim($l[0]);
		$nom=trim($l[1]);
		$avail_wks=0;
		if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$arrHttp["base"]."_fmt_ALL"]) or
		    isset($_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_ALL"]) or $avail_wks==1){
		    $i=$i+1;
		    $wks_p[$cod]="Y";
		}
	    }
	}
    }
    return $wks_p;
}

function CambiarFormatoRegistro(){  // ChangeRegisterFormat
    // apply conversion to the record captured from the database
    global $valortag,$arrHttp,$db_path,$Wxis,$xWxis,$wxisUrl;
    $ValorCapturado="";
    foreach ($valortag as $key => $lin){
	if (trim($key)!=""){
	    //$lin=stripslashes($lin);
	    $sal=explode("\n",$lin);
	    foreach ($sal as $l){
		$ValorCapturado.="<$key 0>".$l."</$key>";
	    }
	}
    }
    $file=$db_path."/cnv/".$arrHttp["cnvtabsel"];
    $fp=file($file);
    $Pft="";
    foreach ($fp as $value) {
	$value=trim($value);
	if ($value!=""){
	    $ix=strpos($value,':');
	    $Pft.="'$$^^$$".substr($value,0,$ix).":'".substr($value,$ix+1)."/";
	}
    }
    $ValorCapturado=urlencode($ValorCapturado);
    $IsisScript=$xWxis."z3950_cnv.xis";
    $Pft=urlencode($Pft);
    $query = "&base=".$arrHttp["base"] ."&cipar=$db_path".$actparfolder.$arrHttp["base"].".par&ValorCapturado=".$ValorCapturado."&Pft=$Pft";
    include("../common/wxis_llamar.php");
    $res=implode("\n",$contenido);
    $res=explode("$$^^$$",$res);
    $ValorCapturado="";
    $tag="";
    $valortag=array();
    foreach ($res as $value){
	$ixpos=strpos($value,':');
	$tag=substr($value,0,$ixpos);
	$campo=substr($value,$ixpos+1);
	$c=explode("\n",$campo);
	foreach($c as $val){
	    $val=trim($val);
	    if ($val!=""){
		if (isset($valortag[$tag]))
		    $valortag[$tag].="\n".$val;
		else
		    $valortag[$tag]=$val;
	    }
	}
    }
    // foreach ($valortag as $tag=>$val) echo "$tag=$val<br>";
}

function EjecutarBusqueda(){ //RunSearch
    global $arrHttp,$db_path,$xWxis,$Wxis,$valortag,$tl,$nr,$Mfn,$wxisUrl,$lang_db,$msgstr,$registro,$Expresion,$Total_Search,$actparfolder;
    // Sets default value to 1 if the value from is lost - corrects the format change after searching
    if (!isset($arrHttp["from"]) || trim($arrHttp["from"]) == "" || (int)$arrHttp["from"] < 1) {
	$arrHttp["from"] = "1";
    }
    $Expresion=trim($arrHttp["Expresion"]);
    //if (substr($Expresion,0,1)!='"') $Expresion='"'.$Expresion.'"';
    $arrHttp["Expresion"]=$Expresion;
    if (!isset($arrHttp["Formato"]))$arrHttp["Formato"]="";
    $Formato=$arrHttp["Formato"];
    if ($Formato!="ALL" and $Formato!=""){
	$Formato.=".pft";
	$a=$db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$Formato;
	if (!file_exists($a)){
	    $a=$db_path.$arrHttp["base"]."/pfts/".$lang_db."/".$Formato;
	}
	$Formato=$a;
    }
    $contenido="";
    $registro="";
    $IsisScript=$xWxis."buscar_ingreso.xis";
    $Expresion=str_replace('¨',"'",$Expresion);
    $query = "&base=".$arrHttp["base"] ."&cipar=$db_path".$actparfolder.$arrHttp["cipar"]."&Expresion=".urlencode($Expresion)."&count=1&from=".$arrHttp["from"]."&Formato=$Formato&prologo=@prologoact.pft&epilogo=@epilogoact.pft";
    include("../common/wxis_llamar.php");
    $ficha_bib=$contenido;
    if ($arrHttp["Formato"]=="") $arrHttp["Formato"]="ALL";
    foreach ($ficha_bib as $linea){
	$linea=trim($linea);
	if ($linea!="") {
	    if (substr($linea,0,6)=='$$REF:'){
		$ref=substr($linea,6);
		$f=explode(",",$ref);
		$bd_ref=trim($f[0]);
		$pft_ref=trim($f[1]);
		$a=$db_path.$bd_ref."/pfts/".$_SESSION["lang"]."/".$pft_ref;
		if (!file_exists($a)){
		    $a=$db_path.$bd_ref."/pfts/".$lang_db."/".$pft_ref;
		}
		$pft_ref=$a;
		$expr_ref=trim($f[2]);
		$ixp=strpos($expr_ref,'_');
		if ($ixp>0){
		    $pref_rel=trim(substr($expr_ref,0,$ixp+1));
		    $expr_ref=trim(substr($expr_ref,$ixp+1));
		    $b_rel=explode('$$',$expr_ref);
		    $expr_ref="";
		    foreach ($b_rel as $xx){
			$xxy=$pref_rel.$xx;
			if ($expr_ref==""){
			    $expr_ref=$xxy;
			}else{
			    $expr_ref.=' or '.$xxy;
			}
		    }
		}
		$reverse="";
		if (isset($f[3])) 	$reverse="ON";
		$IsisScript=$xWxis."buscar.xis";
		$query = "&cipar=$db_path".$actparfolder.$bd_ref. ".par&Expresion=".$expr_ref."&Opcion=buscar&base=".$bd_ref."&Formato=$pft_ref&prologo=NNN&count=90000";
		if ($reverse!=""){
		    $query.="&reverse=On";
		}
		$debug_x=1;
		include("../common/wxis_llamar.php");
		$ixcuenta=0;
		foreach($contenido as $linea_alt) {
		    if (trim($linea_alt)!=""){
			$ll=explode('|^',$linea_alt);
			if (isset($ll[1])){
			    $ixcuenta=$ixcuenta+1;
			    $SS[trim($ll[1])."-$ixcuenta"]=$ll[0];
			}else{
			    $registro.= "$linea_alt\n";
			}
		    }
		}
		if (isset($SS) and count($SS)>0){
		    ksort($SS);
		    foreach ($SS as $linea_alt)
			$registro.= "$linea_alt\n";
		}
	    }else{
		if (substr($linea,0,6)=="[MFN:]"){
		    $arrHttp["Mfn"]=trim(substr($linea,6));
		    echo "\n<script>top.Mfn_Search=".$arrHttp["Mfn"]."
			top.mfn=".$arrHttp["from"]."
			if (top.mfn==0) top.mfn=1
			top.browseby='search'\n</script>\n";
		}else{
		    if (substr($linea,0,8)=="[TOTAL:]"){
			if(trim(substr($linea,8))==0){
			    //echo "Total: 0";
			    return "0";
			    echo "\n<script>top.Max_Search=0
				</script>\n";
			    break;
			}else{
			    $Total_Search=trim(substr($linea,8));
			    echo "\n<script>top.Max_Search=".trim(substr($linea,8))."
				\n</script>\n";
			}
		    }else{
			if ($arrHttp["Formato"]!="ALL"){
			    $arrHttp["Opcion"]="ver";
			    $registro.= "$linea\n";
			}else{
			    $registro.=$linea."\n";
			    $arrHttp["Opcion"]="ver";
			}
		    }
		}
	    }
	}
    }
    echo "<script>
	var item=top.menu.toolbar.getItem('browseby');
	item.selElement.options[1].selected =true \n";
	if (!isset($arrHttp["cambiolang"]))  // Si se dibuja el formulario luego de un cambio de lenguaje, no se actualiza la casilla ir_a porque da un error en javascript
    echo "top.menu.document.forma1.ir_a.value=top.mfn.toString()+'/'+top.Max_Search.toString()";
    echo "</script>\n";
    if ($arrHttp["Formato"]!="ALL"){
	return "no";
    }
    $contenido=explode("\n",$registro);
    $valortag=array();
    $ic=-1;
    foreach($contenido as $linea){
	if ($ic==-1){  //para saltar la primera línea que tiene el mfn en el formato all
	    $ic=2;
	}else{
	    $linea=trim($linea);
	    if ($linea!=""){
		$pos=strpos($linea, " ");
		if (is_integer($pos)) {
		    $tag=trim(substr($linea,0,$pos));
		    ////El formato ALL envía un <br> al final de cada línea y hay que quitarselo
		    $linea=rtrim(substr($linea, $pos+2,strlen($linea)-($pos+2)-5));
		    if ($tag==1002){
			$maxmfn=$linea;
		    }else{
			if (!isset($valortag[$tag])){
			    $valortag[$tag]=$linea;
			}else {
			    $valortag[$tag]=$valortag[$tag]."\n".$linea;
			}
		    }
		}
	    }
	}
    }
    return "si";
}

// ******
// Using the variable ValorCapturado, create a structure with the Isis tag and the field value.
// The tag and value must be separated by a space.

function CargarMatriz($var){ // LoadMatrix
    global $valortag,$variables;
    $var=urldecode($var);
    $filas=explode("\n",$var);
    foreach ($filas as $lin){
	$lin=trim($lin);
	if (trim($lin)!=""){
	    //$lin= stripslashes($lin);
	    $pos=strpos($lin, " ");
	    if (is_integer($pos)) {
		$indice=substr($lin,0,$pos);
		$valor=trim(substr($lin,$pos+1));
		if (trim($valor)!=""){
		    if (substr($indice,0,1)=="0") $indice=substr($indice,1);
		    if (substr($indice,0,1)=="0") $indice=substr($indice,1);
		    if (!isset($valorcap[$indice])) $valorcap[$indice]="";
		    if ($valorcap[$indice]!=""){
			$valorcap[$indice]=$valorcap[$indice]."\n".substr($lin,$pos+1);
		    }else{
			$valorcap[$indice]=substr($lin,$pos+1);
		    }
		}
	    }
	}
    }
    //COPY THE CAPTURED RECORD TO THE EMPTY FIELDS
    foreach ($valorcap as $tag=>$value){
	if (!isset($valortag["$tag"])) $valortag[$tag]=$value;
	$variables["tag$tag"]=$value;
    }
}

function ColocarMfn(){ //PutMFN
    global $arrHttp;
    $arrHttp["Mfn"]=str_replace("<","",$arrHttp["Mfn"]); //Ojo: Averiguar porque se trae el <
    if (!isset($arrHttp["ventana"])){
	?><script>
	var currentMfn=<?php echo $arrHttp["Mfn"];?>;
	var Listar_pos=0;
	if (top.window.frames.length>0){
	    if (top.browseby=="search"){
		top.mfn=currentMfn;
		top.Mfn_Search=currentMfn;
		<?php if (!isset($arrHttp["cambiolang"])) { ?>
		    top.menu.document.forma1.ir_a.value=top.Search_pos.toString()+'/'+top.Max_Search.toString();
		<?php } ?>
	    }else if (top.browseby=="selected_records"){
		RegSel=top.RegistrosSeleccionados.replace(/__/g,"_")
		if (RegSel.substr(0,1)=="_") RegSel=RegSel.substr(1)
		SelLen=RegSel.length
		if (RegSel.substr(SelLen-1,1)=="_") RegSel=RegSel.substr(0,SelLen-1)
		ss=RegSel.split("_")
		sslength=ss.length
		for (let i=0; i<sslength; i++) {
		    if (ss[i]==currentMfn){
			Listar_pos=i+1;
			break;
		    }
		} 
		<?php if (!isset($arrHttp["cambiolang"])) { ?>
		    top.menu.document.forma1.ir_a.value=Listar_pos.toString()+'/'+ss.length;
		<?php } ?>
	    }else{
		if (currentMfn.toString()!="New"){
		    top.mfn=currentMfn;
		    <?php if (isset($arrHttp["Maxmfn"])) { ?>top.maxmfn=<?php echo $arrHttp["Maxmfn"]?>;<?php } ?>
		}
		<?php if (!isset($arrHttp["cambiolang"])) { ?>
		top.menu.document.forma1.ir_a.value=top.mfn.toString()+'/'+top.maxmfn.toString();
		<?php } ?>
	    }
	}
	</script>
	<?php
    }
}

function VariablesDeAmbiente($var,$value){ //EnvironmentalVariables
    global $arrHttp,$variables;
    if (substr($var,0,3)=="tag") {
	$ixpos=strpos($var,"_");
	if ($ixpos!=0) {
	    $occ=explode("_",$var);
	    if (trim($value)!=""){
		if (isset($occ[2])) $value="^".trim($occ[2]).$value;
		$var=$occ[0]."_".$occ[1];
		if (is_array($value)) {
		    $value = implode("\n", $value);
		}
	    }
	    if (isset($arrHttp[$var])){
		$arrHttp[$var].=$value;
	    }else{
		if (trim($value)!="") $arrHttp[$var]=$value;
	    }
	}else{
	    if (is_array($value)) {
		$value = implode("\n", $value);
	    }
	    if (isset($arrHttp[$var])){
		$arrHttp[$var].="\n".$value;
	    }else{
		if (trim($value)!="") $arrHttp[$var]=$value;
	    }
	}
    }else{
	if (trim($value)!="") {
	    $arrHttp[$var]=$value;
	    // if (stripos($value,"script")!==false){// unknown why this check is here. Breaks also legal entries
	    // $arrHttp[$var]="**INVALID**";
	    // }
	}
    }
}

function GetRecordType(){
global $arrHttp,$valortag,$tm,$nr,$tl,$tym;
    if (isset($arrHttp["wks"]))
    return;
    if (isset($tm)){   //to see if there is a type of material and it is not previously fixed
	foreach ($tm as $linea){
	    $tym=explode('|',trim($linea));
	    if (count($tym)>1){  // The entry sheet is not taken from the entry sheets menu
		if (!isset($valortag[$tl])){
		    $arrHttp["wks"]=$tym[0];
		    $arrHttp["wk_tipom_1"]=$tym[1];
		    $arrHttp["wk_tipom_2"]=$tym[2];
		    break;
		}else{
		    if (!isset($valortag[$nr])) $valortag[$nr]="";
		    if ($valortag[$tl]==$tym[1] and $tym[2]==""  or $valortag[$tl]==$tym[1] and $valortag[$nr]==$tym[2]) {
			$arrHttp["wks"]=$tym[0];
			$arrHttp["wk_tipom_1"]=$tym[1];
			$arrHttp["wk_tipom_2"]=$tym[2];
		    }
		}
	    }
	}
   }
}


// ==================================================================================================
// INICIO DEL PROGRAMA
// ==================================================================================================
//
// Note: The `include get_post.php` is not used here because the method for reading variables is different.

$ValorCapturado="";
$arrHttp=Array();
foreach ($_REQUEST as $var => $value) {
    if (trim($value)!="") VariablesDeAmbiente($var,$value);
}
if (!isset($arrHttp["Expresion"]))     $arrHttp["Expresion"]="";

//READ THE RIGHTS OF THE USERS FOR THE DATAENTRY WORKSHEETS
$wks_avail=ReadWorksheetsRights();

if (isset($arrHttp["wks"])){
    $arrHttp["wks_a"]=$arrHttp["wks"];
    $wk=explode('|',$arrHttp["wks"])  ;
    $arrHttp["wks"]=$wk[0];	    // Entry sheet name
    if (isset($wk[1]))
	$arrHttp["wk_tipom_1"]=$wk[1]; // Tipo de registro 1
    else
	$arrHttp["wk_tipom_1"]="";
    if (isset($wk[2]))
	$arrHttp["wk_tipom_2"]=$wk[2]; // Tipo de registro 2
    else
	$arrHttp["wk_tipom_2"]="";
    if (isset($wk[4]))
	$arrHttp["wk_tag_tipom_1"]=$wk[4]; // Tag correspondiente al Tipo de registro 1
    else
	$arrHttp["wk_tag_tipom_1"]="";
    if (isset($wk[5]))
	$arrHttp["wk_tag_tipom_2"]=$wk[5]; // Tag correspondiente al Tipo de registro 2
    else
	$arrHttp["wk_tag_tipom_2"]="";
}
echo "\n<script>top.toolbarEnabled=\"\"\n</script>";

// Settings for returning to the script browse.php  (added 14-04-2009)
$retorno="";  // To return to browse.php
$return="";   // To return to the procedure that invoked browse.php
if (isset($arrHttp["retorno"])){
    $retorno =str_replace('~','&',$arrHttp["retorno"]);  // the return URL is reconstructed
    $retorno.="?encabezado=s&base=".$arrHttp["base"]."&unlock=s&Mfn=".$arrHttp["Mfn"];
}
if (isset($arrHttp["return"])){
     $retorno.="&return=".$arrHttp["return"];
}
if (isset($arrHttp["from"])){
    $retorno.="&from=".$arrHttp["from"];
}
if (isset($arrHttp["Status"])){    // value=1 indicates record deleted
    $retorno.="&Status=".$arrHttp["Status"];
}
// end settings

if ($arrHttp["Opcion"]=="ver" or $arrHttp["Opcion"]=="cancelar" or $arrHttp["Opcion"]=="buscar" or ($arrHttp["Opcion"]=="actualizar") or $arrHttp["Opcion"]=="save" ) {
    if (isset($arrHttp["sort"]))
	$sort="&sort=".$arrHttp["sort"];
    else
	$sort="";
}
$login = isset($_SESSION["login"]) ? $_SESSION["login"] : "";
$arrHttp["Notificacion"]="N";
//If there is a leader, it is read to see in which fields the type of literature and the bibliographic level are found.
if (file_exists($db_path.$arrHttp["base"]."/def/".$lang_db."/typeofrecord.tab")){
    $fpLeader=file($db_path.$arrHttp["base"]."/def/".$lang_db."/typeofrecord.tab");
    foreach ($fpLeader as $value){
	$line_1=trim($value);
	break;
    }
    $tmLeader=explode(" ",$line_1);
    if (!isset($LEADER_TAG)){
	$lid[0]=3000;
	$lid[1]=3999;
    }else{
	if ($LEADER_TAG!=""){
	    $ll=explode("-",$LEADER_TAG);
	    $lid[0]=$ll[0];
	    $lid[1]=$ll[1];
	}
    }
}
foreach ($arrHttp as $var => $value) {
    if (substr($var,0,3)=="tag" ){
	$tag=explode("_",$var);
	if (count($tag)>0){ //IF LEADER, REFORMAT THE FIELD FOR ELIMINATING |
	    if ( isset($lid) and substr($tag[0],3)>=$lid[0] and substr($tag[0],3)<=$lid[1]  or
		 isset($tmLeader) and count($tmLeader)>0 and
		 (substr($tag[0],3)==$tmLeader[0] or isset($tmLeader[1]) and substr($tag[0],3)==$tmLeader[1])
		) {  
		$v=explode('|',$value);
		$value=$v[0];
	    }
	}
	if (isset($variables[$tag[0]])){
	    $variables[$tag[0]].="\n".$value;
	    $valortag[substr($tag[0],3)].="\n".$value;
	}else{
	    $variables[$tag[0]]=$value;
	    $valortag[substr($tag[0],3)]=$value;
	}
    }
}
// debug foreach ($variables as $key => $value) echo "$key=$value<br>";
// If the option is 'copiar_captura', the database is changed to be able to read the definition files.
if ($arrHttp["Opcion"]=="captura_bd") {
    $basecap=$arrHttp["base"];
    $ciparcap=$arrHttp["cipar"];
    $arrHttp["base"]=$arrHttp["basecap"];
    $arrHttp["cipar"]=$arrHttp["ciparcap"];
}
// This variable is for storing the tables that need to be generated with JavaScript.
$Tabla_sel=array();
$base =$arrHttp["base"];
$cipar =$arrHttp["cipar"];
if (isset($arrHttp["Mfn"]))$Mfn=$arrHttp["Mfn"];
$login = isset($arrHttp["login"]) ? $arrHttp["login"] : "";
if (!isset($arrHttp["ver"])) $arrHttp["ver"]="";
if ($arrHttp["ver"]=="S") {
    $ver=true;
} else {
    $ver=false;
}
$capturar="NO";
$actualizar="N";
if (isset ($arrHttp["Opcion"]))  {
    if ($arrHttp["Opcion"]=="actualizar" || $arrHttp["Opcion"]=="save") $actualizar="SI";
    if ($arrHttp["Opcion"]=="crear") $actualizar="SI";
    // If the "nuevo" option was selected in the top menu, it will change to "crear" so that the new record can be created.
    if ($arrHttp["Opcion"]=="nuevo" or $arrHttp["Opcion"]=="nuevoregistro" ||$arrHttp["Opcion"]=="nuevoregistrousuario") {
	$Mfn="New";
	$arrHttp["Opcion"]="crear";
    }
}

//--------------------------------------------------------------------------
// Read file with record types
unset ($tm);
$tor="";
if (file_exists($db_path.$base."/def/".$_SESSION["lang"]."/typeofrecord.tab")){
    $tor=$db_path.$base."/def/".$_SESSION["lang"]."/typeofrecord.tab";
}else{
    if (file_exists($db_path.$base."/def/".$lang_db."/typeofrecord.tab"))
	$tor=$db_path.$base."/def/".$lang_db."/typeofrecord.tab";
}
// the record types table is loaded
// $tl and $nr are the leader tags where the type of record is stored
$tl="";
$nr="";
if ($tor!=""){
    $fp = file($tor);
    $ix=0;
    $tm[]="";
    foreach ($fp as $linea){
	$linea=trim($linea);
	if ($linea!=""){
	    if ($ix==0){
		$ij=strpos($linea," ");
		if ($ij===false) {
		    $tl=$linea;
		}else{
		    $tl=trim(substr($linea,0,$ij));
		    $nr=trim(substr($linea,$ij));
		}
		$ix=1;
	    }else{
		$tm[]=trim($linea);
	    }
	}
    }
}
$i=-1;
$ValorCapturado="";
if (!isset($arrHttp["encabezado"])and $arrHttp["Opcion"]!="captura_bd"){
    if (!isset($arrHttp["cambiolang"]) and !isset($arrHttp["ventana"]))
	echo "<script>
		if (top.window.frames.length>0) top.PrenderEdicion()\n";
	echo "</script>\n";
}

$OpcionDeEntrada=$arrHttp["Opcion"];
if (!isset($arrHttp["Formato"])) $arrHttp["Formato"]="";
if ($arrHttp["Opcion"]=="ver" and $arrHttp["Formato"]=="") $arrHttp["Opcion"]="leer";
$recdel="";

// debug 
//echo "<br>switch option=".$arrHttp["Opcion"]."<br>";
switch ($arrHttp["Opcion"]) {
    case "reintentar":  //retry: IF A VALIDATION ERROR OCCURS THE RECORD IS REDISPLAYED
    case "save":	//save (after validation error)
	if ($arrHttp["Opcion"]=="reintentar"){
	    $reintentar="S";
	    if ($arrHttp["Mfn"]=="New"){
		$arrHttp["Opcion"]="crear";
	    }else{
		$arrHttp["Opcion"]="editar";
	    }
	}
	CargarMatriz($arrHttp["ValorCapturado"]);
	// debug echo "<xmp>";var_dump($variables);echo "</xmp>";
	// debug echo "<br>worksheet=".$arrHttp["wks"];
	if (isset($tm) and !isset($arrHttp["wks"])){   //to see if there is a type of material and it is not previously set
	    foreach ($tm as $linea){
		$tym=explode('|',trim($linea));
		    if (!isset($valortag[$tl])){
			$arrHttp["wks_a"]=$linea;
			$arrHttp["wks"]=$tym[0];
			$arrHttp["wk_tipom_1"]=$tym[1];
			$arrHttp["wk_tipom_2"]=$tym[2];
			break;
		    }
		    if (isset($tym[1]) and isset($tym[2])){
			if ($valortag[$tl]==$tym[1] and $tym[2]==""  or
			    $valortag[$tl]==$tym[1] and isset($valortag[$nr]) and $valortag[$nr]==$tym[2]) {
			    $arrHttp["wks_a"]=$linea;
			    $arrHttp["wks"]=$tym[0];
			    $arrHttp["wk_tipom_1"]=$tym[1];
			    $arrHttp["wk_tipom_2"]=$tym[2];
			}
		    }
	    }
	}
	PlantillaDeIngreso();
	if ($arrHttp["Opcion"]=="reintentar"){
	    echo "<script>
			if (top.window.frames.length>0){
				top.xeditar='S'
				top.PrenderEdicion()
			}
		</script>\n";
	}
	break;
    case "buscar":	//Search
	include("scripts_dataentry.php");
	//$arrHttp["Expresion"]='"'. $arrHttp["Expresion"].'"';
	$resultado=EjecutarBusqueda();
	$_SESSION["Expresion"]=$arrHttp["Expresion"];
	$ix=0;
	if (isset($_SESSION["history"])){
	    $ix=count($_SESSION["history"]);
	    $found="N";
	    //$arrHttp["Expresion"]=str_replace('"','',$arrHttp["Expresion"]);
	    foreach ($_SESSION["history"] as $history){
		$h=explode('$$|$$',$history);
		if ($h[0]=$arrHttp["base"]){
		    if ($arrHttp["Expresion"]==$h[1]){
			$found="Y";
			break;
		    }
		}
	    }
	}
	$_SESSION["history"][$ix]=$arrHttp["base"].'$$|$$'.$arrHttp["Expresion"].'$$|$$'.$Total_Search;
	$_SESSION["refinar"]=$arrHttp["Expresion"];
		/*	if (isset($arrHttp["Expresion"]))
		if (!isset($_SESSION["Expresion"][$arrHttp["base"]][$arrHttp["Expresion"]]))
			$_SESSION["Expresion"][$arrHttp["base"]][$arrHttp["Expresion"]]=$resultado;
	*/
		if ($resultado === "0" || $resultado === 0) {
			include "../common/inc_div-helper.php";

			$arrHttp["Opcion"] = "ninguna";

			// PHP 8.1+: Safe extraction and protection against XSS in textarea
			$safe_expression = isset($arrHttp["Expresion"]) ? htmlspecialchars((string)$arrHttp["Expresion"], ENT_QUOTES, 'UTF-8') : '';

			echo "<div class=\"middle form\">\n";
			echo "    <div class=\"formContent\">\n";
			echo "        <table style='width:100%'><tr><td>\n";
			echo "            " . $msgstr["expresion"] . ":<br>\n";
			echo "            <textarea name=\"nueva_b\" cols=\"150\" rows=\"1\">" . $safe_expression . "</textarea>\n";
			echo "            <br><a class='bt bt-green' href=\"javascript:NuevaBusqueda()\">" . $msgstr["buscar"] . "</a>\n";

			InsertarEnlaces($arrHttp["base"]);

			echo "            <h4>" . $msgstr["selected_records"] . ": " . htmlspecialchars((string)$resultado, ENT_QUOTES, 'UTF-8') . "</h4>\n";

			$arrHttp["Mfn"] = 1;
			ColocarMfn();

			echo "        </td></tr></table>\n";
			echo "    </div>\n";
			echo "</div>\n";
			echo "</form>\n"; // closes open form from scripts_dataentry

			// PHP 8.1+: Simplified validation logic (short-circuit)
			if (!isset($arrHttp["footer"]) || strtoupper($arrHttp["footer"]) !== "N") {
				include("../common/footer.php");
			}

			die;
	}else{
	    include("toolbar_record.php");
			if ($resultado != "no") {	//resultado=no indica que ya se formateo el registro
				$ver = "s";
				if ($arrHttp["Formato"] != "") {
					echo "<table style='width:100%'><tr><td><font size+1>$registro.</td></table>";
				} else {
					$res = LeerRegistro($base, $cipar, $arrHttp["Mfn"], $maxmfn, "leer", $arrHttp["login"], "");
					echo $arrHttp["Opcion"] . " " . $clave_proteccion;
					$ver = true;
					if (isset($arrHttp["wks"])) {
					} else {
						GetRecordType();
					}
					PlantillaDeIngreso();
				}
				if (isset($tm)) {   //para ver si hay tipo de material  y no viene fijado anteriormente
					foreach ($tm as $linea) {
						$linea = trim($linea);
						$tym = explode('|', trim($linea));

						// PROTEÇÃO PHP 8: Garante que os índices existam após o explode
						$tym_0 = isset($tym[0]) ? $tym[0] : "";
						$tym_1 = isset($tym[1]) ? $tym[1] : "";
						$tym_2 = isset($tym[2]) ? $tym[2] : "";

						if (!isset($valortag[$tl])) {
							$arrHttp["wks_a"] = $linea;
							$arrHttp["wks"] = $tym_0;
							$arrHttp["wk_tipom_1"] = $tym_1;
							$arrHttp["wk_tipom_2"] = $tym_2;
							break;
						}

						// PROTEÇÃO PHP 8: Garante que os valores lidos da base existam antes de comparar
						$vt_tl = isset($valortag[$tl]) ? $valortag[$tl] : "";
						$vt_nr = isset($valortag[$nr]) ? $valortag[$nr] : "";

						if (($vt_tl == $tym_1 and $tym_2 == "") or ($vt_tl == $tym_1 and $vt_nr == $tym_2)) {
							$arrHttp["wks_a"] = $linea;
							$arrHttp["wks"] = $tym_0;
							$arrHttp["wk_tipom_1"] = $tym_1;
							$arrHttp["wk_tipom_2"] = $tym_2;
						}
					}
					PlantillaDeIngreso();
					if (!isset($arrHttp["encabezado"])) ColocarMfn();
				}
			} else {
				$ver = "s";
				//echo "<br><input type=checkbox value=".$arrHttp["Mfn"]." onclick=javascript:SeleccionarRegistro(".$arrHttp["Mfn"].")> ".$msgstr["seleccionar"];
				echo "<table style='width:100%'><tr><td>";
				if ($arrHttp["Formato"] != "") {
					echo "<div id=results>" . $registro . "</div>";
				} else {
					$res = LeerRegistro($base, $cipar, $arrHttp["Mfn"], $maxmfn, "leer", $login, "");
					$ver = true;
					if (isset($arrHttp["wks"])) {
					} else {
						GetRecordType();
					}
					PlantillaDeIngreso();
				}
			}
		}
		echo "</td></table>\n";
		echo "</form>"; // closes open form from scripts_dataentry
		if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and strtoupper($arrHttp["footer"]) != "N"))
			include("../common/footer.php");
		die;
		break;
	case "ver":	//View:Present the record in the selected format.
		include ("scripts_dataentry.php");
	$salida= LeerRegistroFormateado($arrHttp["Formato"]);
	if ($arrHttp["Opcion"]!="actualizar" and $record_deleted=="Y") include "../common/inc_div-helper.php";
	if ($record_deleted=="N") include("toolbar_record.php");
	$arrHttp["Opcion"]=="ninguna";
	echo "<div class=\"middle form\">";
	echo "<div class=\"formContent\">\n";
	echo "<table><tr><td>\n";
	echo $salida;// contains via .xis file also prologact.pft code
	echo "</td></table>\n" ;
	//SE AVERIGUA SE SE VA A LEER LA INFORMACIÓN DE OTRA BASE DE DATOS
	if (isset($record_deleted) and $record_deleted=="Y"){
	    echo "<a href=javascript:Undelete(".$arrHttp['Mfn'].")>".$msgstr["undelete"]."</a>";
	}
	if (!isset($arrHttp["capturar"])){
	    ColocarMfn();
	}
	echo "</div></div>";
	echo "</form>"; // closes open form from scripts_dataentry
	if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and strtoupper($arrHttp["footer"])!="N"))
	    include("../common/footer.php");
	die;
	break;
    case "presentarformulario":
	PresentarFormulario("nuevo");
	return;
	break;
    case "presentar_captura":	// Edit for new and copy at end
	$ver="";
	if (isset($arrHttp["cnvtabsel"])){
	    $res=LeerRegistro($arrHttp["basecap"],$arrHttp["ciparcap"],$arrHttp["Mfn"],$maxmfn,"editar",$login,"");
	    CambiarFormatoRegistro();
	}else{
	    $res=LeerRegistro($arrHttp["basecap"],$arrHttp["ciparcap"],$arrHttp["Mfn"],$maxmfn,"editar",$login,"");
       	}
       	$arrHttp["Mfn"]="New";
	$arrHttp["Opcion"]="crear";
	break;
    case "captura_bd":	// copy record from another database
	$ver="S";
	$res=LeerRegistro($arrHttp["basecap"],$arrHttp["ciparcap"],$arrHttp["Mfn"],$maxmfn,"editar",$login,"");
	$arrHttp["Opcion"]="crear";
	$arrHttp["capturar"]="S";
	break;
    case "capturar":
	echo "\n<script>
		top.xeditar=\"S\"
		if (top.window.frames.length>0) top.PrenderEdicion()
		</script>\n";
	if (isset($arrHttp["Mfn"]) and $arrHttp["Mfn"]!="New")
	    $res=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,"editar",$arrHttp["login"],"");
	// echo "<xmp>".$arrHttp["ValorCapturado"]."</xmp>";
	CargarMatriz($arrHttp["ValorCapturado"]);
	$arrHttp["Opcion"]="crear";
	break;
    case "cancelar":	// Cancel edit
	if ($arrHttp["Mfn"]=="New") {
	    include("toolbar_record.php");
	    echo "<div class=\"middle form\">
			<div class=\"formContent\">";
	    echo "<h3>".$msgstr["recnotupdated"]."</h3>" ;
	    echo "</form></div></div></div>\n";
	    if (!isset($arrHttp["ventana"])){
		if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and $arrHttp["footer"] !="N"))
		    include("../common/footer.php");
	    }else{
		echo "<script>self.close()</script>";
	    }
	    die;
	    break;
	}
	$arrHttp["unlock"] ="S";
	$res=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,"leer",$arrHttp["login"],"");
	if (isset($arrHttp["Formato"]) and $arrHttp["Formato"]!=""){
	    include("toolbar_record.php");
	    echo "<div class=\"middle form\">
			<div class=\"formContent\">\n";
	    echo "<dd><table><tr><td>";
	    echo LeerRegistroFormateado($arrHttp["Formato"]);
	    echo "</td></tr></table></dd>\n" ;
	    echo "</div></div>";
	    if (!isset($arrHttp["ventana"])){
		if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and $arrHttp["footer"] !="N"))
		    include("../common/footer.php");
	    }else{
		echo "<script>self.close()</script>";
	    }
	    die;
	}else{
	    $res=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,"leer",$arrHttp["login"],"");
	    unset($arrHttp["unlock"]);
	    $res=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,"leer",$arrHttp["login"],"");
	    $ver=true;
	    if (isset($arrHttp["wks"])){
	    }else{
		GetRecordType();
	    }
	    PlantillaDeIngreso();
	    break;
	}
    case "editar":	// Edit record
	$arrHttp["lock"] ="S";
    case "password_ok":
    case "leer":
	$res=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,$arrHttp["Opcion"],$login,"");
	if ($clave_proteccion!="" and $_SESSION["profile"]!="adm"
	    and !isset($_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_EDITPROTECTEDRECORD"])
	    and !isset($_SESSION["permiso"][$arrHttp["base"]."_CENTRAL_ALL"])){
	    if ($arrHttp["Opcion"]!="password_ok"){
		if ($password_protection!="")
		    $_SESSION["REC_PASS"]=$password_protection;
		else
		    $_SESSION["REC_PASS"]=$clave_proteccion;
		$_SESSION["MFN"]=$arrHttp["Mfn"];
		echo "<script>
			if (top.window.frames.length>0){
				top.xeditar=''
				top.ApagarEdicion()
			}
			self.location.href=\"check_record_password.php?base=".$arrHttp["base"]."&Mfn=".$arrHttp["Mfn"]."&Formato=".$arrHttp["Formato"]."\"
			</script>";
	    }
	    $arrHttp["Opcion"]="editar";
	}
	if ($res=="LOCKREJECTED") {
	    echo "<script>
		alert('".$arrHttp["Mfn"].": ".$msgstr["reclocked"]."')
		if (top.window.frames.length>0){
			top.xeditar=''
			top.ApagarEdicion()
			top.Menu(\"leer\")
		}
		</script>";
	    die;
	    break;
	}
	if (isset($arrHttp["wks"])){
	}else{
	    GetRecordType();
	}
	PlantillaDeIngreso();
	if (!isset($arrHttp["encabezado"]))ColocarMfn();
	break;
    case "actualizar":	// Save record
	break;
    case "eliminar":	// Delete record
	include ("scripts_dataentry.php");
	include "../common/inc_div-helper.php";
	echo "<div class=\"middle form\">
		<div class=\"formContent\">";
	$res=ActualizarRegistro("fmt_A");
	if (trim($res)=="DELETED"){
	    echo "<h4>".$arrHttp["Mfn"]." ". $msgstr["recdel"]."</h4>";
	    $record_deleted="Y";
	    if (!isset($arrHttp["ventana"])) echo "<a href=javascript:Undelete(".$arrHttp["Mfn"].")>".$msgstr["undelete"]."</a>";
	}else{
	    echo "<h4>".$arrHttp["Mfn"]." ". $msgstr["notdeleted"]."</h4>";
	    $record_deleted="N";
	}
	if (isset($arrHttp["ventana"])){
	    echo "<a href='javascript:window.opener.location.reload(true); self.close()'>";
	    echo $msgstr["cerrar"]."</a>";
	}
	echo "\n<script>if (top.window.frames.length>0) top.ApagarEdicion()</script>
		</div></div>\n";
	echo "</form>"; // closes open form from scripts_dataentry
	if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and strtoupper($arrHttp["footer"])!="N"))
	    include("../common/footer.php");
	return;
	break;
    default:
}

if ($actualizar=="SI"){
    GetRecordType();
    // READ END CODE
    $validar="N";
    if ($arrHttp["Opcion"]!="save"){
	$ext=".end";
	include("fmt_begin_end.php");
	if (isset($rec_validation)){
	    $validar="Y";
	}else{
	    $validar="N";
	}
	if (isset($arrHttp["Validar"]))  $validar="N";
    }
    unset($rec_validation);
    unset($default_values);
    $xtl="";
    $xnr="";
    if (isset($valortag[$Rtl])) $xtl=trim($valortag[$Rtl]);
    if (isset($valortag[$Rnr])) $xnr=trim($valortag[$Rnr]);
    $nuevo="";
    if ($arrHttp["Mfn"]=="New") $nuevo="s";
    $regSal=ActualizarRegistro("fmt_B");
    if ($nuevo=="s") $arrHttp["Maxmfn"]=$arrHttp["Mfn"];
    $arrHttp["Opcion"]="ver";
    $ver="S";
    reset($valortag);
    if (!isset($arrHttp["Formato"])or $arrHttp["Formato"]==""){
	$regSal=LeerRegistro($base,$cipar,$arrHttp["Mfn"],$maxmfn,$arrHttp["Opcion"],$login,$arrHttp["Formato"]);
	$arrHttp["Notificacion"]="N";
	require_once('ingresoadministrador.php');// shows div-helper, toolbar and record content
    }else{
	$regSal=LeerRegistroFormateado($arrHttp["Formato"]);
	// Show the toolbar for further edits on this record
	// The included file shows only the toolbar in case "encabezado" is not set:not set in the dataentry menu
	if (!isset($record_deleted)) $record_deleted="N";
	if ($record_deleted=="N")include("toolbar_record.php");
	// Outside the dataentry menus there is a need for breadcrumb, actions (back) and helper
	if (isset($arrHttp["encabezado"])) {
	    include("../common/institutional_info.php");
	    ?>
	    <div class="sectionInfo">
		<div class="breadcrumb">
		   <?php echo $msgstr["update_rec"]?>
		</div>
	    <div class="actions">
		<?php
		    //$backtoscript="../dataentry/inicio_main.php";
		    include "../common/inc_back.php";
		?>
		</div>
		    <div class="spacer">&#160;</div>
		</div>
	    <?php
	    unset ($wiki_help);
	    include "../common/inc_div-helper.php";
	}
	echo "<div class='middle form'>\n";
	echo "<div class='formContent'>\n";
	echo "<dd><table><tr><td>";
	echo $regSal;
	echo "</td></tr></table></dd>\n" ;
	echo "</div></div>";
    }
}else{
    //se lee la fdt de la base de datos
    if ($arrHttp["Opcion"]=="crear" or $arrHttp["Opcion"]=="capturar") {
	if (isset($_SESSION["valdef"])){
	    $fp=explode('$$$',$_SESSION["valdef"]);
	    foreach ($fp as $linea){
		$linea=rtrim($linea);
		$tag=(int)trim(substr($linea,0,4))*(int)1;
		if (trim(substr($linea,4))!=""){
		    if (!isset($valortag[$tag]))
			$valortag[$tag]=substr($linea,4);
		    else
			$valortag[$tag].="\n".substr($linea,4);
		}
	    }
	}
	if (isset($arrHttp["wk_tag_tipom_1"]) and $arrHttp["wk_tag_tipom_1"]!=""){
	    $valortag[$arrHttp["wk_tag_tipom_1"]]= $arrHttp["wk_tipom_1"];
	}
	if (isset($arrHttp["wk_tag_tipom_2"]) and $arrHttp["wk_tag_tipom_2"]!=""){
	    $valortag[$arrHttp["wk_tag_tipom_2"]]= $arrHttp["wk_tipom_2"];
	}
	PlantillaDeIngreso();
    }
    // READ BEGIN CODE
    $ext=".beg";
    include("fmt_begin_end.php");
    // se  colocan los valores por defecto
    foreach ($fdt as $campo) {
	if (trim($campo)!=""){
	    $tt=explode('|',$campo);
	    if (isset($tt[15]) and $arrHttp["Opcion"]=="crear" and isset($valortag[$tt[1]]))
		if (trim($valortag[$tt[1]])=="") $valortag[$tt[1]]= $tt[15];
	}
    }
    $llamada=$query;
    $arrHttp["Notificacion"]="N";
    require_once('ingresoadministrador.php');// Administrator login ?
    if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and $arrHttp["footer"] !="N")){
	include("../common/footer.php");
    }
    die;
}
if (!isset($arrHttp["encabezado"]) and $arrHttp["Opcion"]!="captura_bd" and !isset($arrHttp["ventana"]))
	if ($arrHttp["Opcion"]!="eliminar" ) ColocarMfn();

if (isset($arrHttp["ventana"])){
    echo "<script>
	 window.opener.location.reload(true)
	 self.close()
	</script>";
}
if (!isset($arrHttp["footer"]) or (isset($arrHttp["footer"]) and $arrHttp["footer"] !="N")){
    include("../common/footer.php");
}
?>
