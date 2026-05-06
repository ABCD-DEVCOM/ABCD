<?php
/*
20220715 fho4abcd Use $actparfolder as location for .par files
*/
/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      show.php
 * @desc:
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
if (!isset($_REQUEST["vienede"])){
	session_start();
	if (!isset($_SESSION["permiso"])){
		header("Location: ../common/error_page.php") ;
	}
	if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
	$lang=$_SESSION["lang"];
}
include("../common/get_post.php");
include("../config.php");
if (isset($arrHttp["vienede"])) {
	$lang=$arrHttp["lang"];
	$_SESSION["lang"]=$lang;
}

include("../lang/acquisitions.php");
include("../lang/admin.php");

include("../common/abcd_ref.php");

//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
if (!isset($arrHttp["Expresion"])) $arrHttp["Expresion"]="";
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"]="";
include("../common/header_display.php");
?>

<body class="formContent">

<form name="forma1" method="post" action="show.php">
<input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
<input type="hidden" name="Expresion" value="<?php echo urlencode($arrHttp["Expresion"]);?>">
<input type="hidden" name=Opcion value="<?php echo $arrHttp["Opcion"]; ?>">

<?php
if (isset($arrHttp["Formato"])){
	$Pft=$arrHttp["Formato"].".pft";
}else{
	$Pft=$arrHttp["base"].".pft";
}
if (!isset($arrHttp["from"])) $arrHttp["from"]=1;
if (!isset($arrHttp["to"])) $arrHttp["to"]="20";
$Formato=$db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/$Pft" ;
if (!file_exists($Formato)) $Formato=$db_path.$arrHttp["base"]."/pfts/".$lang_db."/$Pft"  ;
if (isset($arrHttp["Mfn"]))
	$query = "&base=".$arrHttp["base"]."&cipar=$db_path".$actparfolder.$arrHttp["base"].".par"."&from=".$arrHttp["Mfn"]."&to=".$arrHttp["Mfn"]."&Formato=@$Formato&Opcion=rango";
else
	$query = "&base=".$arrHttp["base"]."&cipar=$db_path".$actparfolder.$arrHttp["base"].".par"."&Expresion=".urlencode($arrHttp["Expresion"])."&Formato=@$Formato&Total=s&Opcion=buscar&from=".$arrHttp["from"]."&to=".$arrHttp["to"];
if (isset($arrHttp["sort"])){
	$IsisScript=$xWxis."sort.xis";
	$query.="&sort=".$arrHttp["sort"];
}else{
	$IsisScript=$xWxis."imprime.xis";
}
include("../common/wxis_llamar.php");
$rec=0;
$total=0;
foreach ($contenido as $value){
	$value=trim($value);
	if (trim($value)!=""){
		if (isset($arrHttp["nodiacritics"]))
			$value=str_replace($accent,$noaccent,$value);
		if (substr($value,0,9)=="[RECORD:]") {
			$rec=substr($value,9);
			echo "<font color=darkred>".$rec."</font>";
		}else{
			if (substr($value,0,8)=="[TOTAL:]") {
				$total=substr($value,9);
			}else{
				if (substr($value,0,6)=='$$REF:'){
				   echo ABCD_Ref($value,"");
				}else{
					echo $value;
				}
			}
		}
	}
}
$rec=$rec+1;
echo "<input type=hidden name=from value=".$rec.">";
if (!isset($arrHttp["Mfn"])){
	if ($total >$rec){
		 echo "<input type=submit value=".$msgstr["continuar"]." onclick=document.forma1.submit()>";
	}
}else{

}
if (!isset($arrHttp["hide"])) {
	echo "<p>";
	echo "&nbsp; &nbsp;<input class='bt bt-red' type=submit value=".$msgstr["cerrar"]." onclick=self.close()>";
}
?>
</form>
</div>
</div>

</body>
</html>