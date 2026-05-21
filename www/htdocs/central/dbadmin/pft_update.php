<?PHP
/*
20220129 fho4abcd backbutton+divhelper+more feedback messages
20231228 fho4abcd Add separator parameter,
20231228 fho4abcd remove _h.txt file if no heading specified
20231228 fho4abcd remove unused code
20240115 fho4abcd Store extra options
/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      pft_update.php
 * @desc:      Updates pft´s files
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
//error_reporting(E_ALL);
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
$lang=$_SESSION["lang"];
include("../common/get_post.php");
include("../config.php");

include("../lang/dbadmin.php");
include("../lang/admin.php");

if (!isset($arrHttp["Modulo"])) $arrHttp["Modulo"]="";
include("../common/header.php");
if (isset($arrHttp["encabezado"])){
	include("../common/institutional_info.php");
}
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"]="";
?>
<div class="sectionInfo">
    <div class="breadcrumb">
    <?php echo $msgstr["pft"]." - ". $msgstr["update"].": ".$arrHttp["nombre"].".pft (".$arrHttp["base"].")"?>
    </div>

    <div class="actions">
	<?php
    if ($arrHttp["Opcion"]=="new"){
        $backtoscript="../common/inicio.php?reinicio=s";
		include "../common/inc_back.php";
	}else{
		if ($arrHttp["Modulo"]=="dataentry"){
            $backtoscript="pft.php?Modulo=dataentry";
            include "../common/inc_back.php";
		}else{
            $backtoscript="pft.php";
            include "../common/inc_back.php";
            include "../common/inc_home.php";
        }
	}
	?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php";?>
<div class="middle form">
<div class="formContent">
<?php
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
if (isset($arrHttp["pft"]))
	$arrHttp["pft"]=stripslashes($arrHttp["pft"]);
else
	$arrHttp["pft"]=stripslashes($arrHttp["pftedit"]);

$arrHttp["nombre"]=trim(strtolower($arrHttp["nombre"]));
$arrHttp["nombre"]=str_replace(".pft","",$arrHttp["nombre"]);
$archivobase=$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$arrHttp["nombre"].".pft";
$archivo=$db_path.$archivobase;
$archivo_hbase=$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$arrHttp["nombre"]."_h.txt";
$archivo_h=$db_path.$archivo_hbase;
//Proces the pft file itself
$fp=fopen($archivo,"w");
if (!$fp){
	echo "<h2 style='color:red'>$archivobase ".$msgstr["revisarpermisos"]."</h2>";
	die;
}
fputs($fp, $arrHttp["pft"]);
fclose($fp);
unset($fp);
echo "<h3>$archivobase ".$msgstr["updated"]."</h3>";

//Process the headings
if (isset($arrHttp["headings"])){
	$fp=fopen($archivo_h,"w");
	$red=fwrite($fp,$arrHttp["headings"]);
	fclose($fp);
	echo "<h3>".$archivo_hbase." ".$msgstr["created"]."</h3>";
} else{
	if (file_exists($archivo_h)){
		if (unlink($archivo_h)){
			echo "<h3>".$archivo_hbase." ".$msgstr["eliminados"]."</h3>";
		} else {
			echo "<h3 style='color:red'>".$archivo_hbase." ".$msgstr["nodeleted"]."</h3>";
		}
	}
}

//Process formatos.dat
if (file_exists($db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/formatos.dat")){
	$fp=file($db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/formatos.dat");
}else{
	if (file_exists($db_path.$arrHttp["base"]."/pfts/".$lang_db."/formatos.dat")){
		$fp=file($db_path.$arrHttp["base"]."/pfts/".$lang_db."/formatos.dat");
	}
}
$flag="";
// construct the options
$options="|";
if (isset($arrHttp["tipoacro"])) {
	$options.=$arrHttp["tipoacro"];
}
$options.="|";
if (isset($arrHttp["separacro"])){
	$options.=Trim($arrHttp["separacro"]);
}
$options.="|";
if (isset($arrHttp["prefixsubfieldacro"])){
	$options.=Trim($arrHttp["prefixsubfieldacro"]);
}
$options.="|";
if (isset($arrHttp["fieldquoteacro"])){
	$options.=($arrHttp["fieldquoteacro"]);
}
// DELETE THE FILE FORMAT NAME FROM THE DESCRIPTION OF THE FORMAT
$desc=$arrHttp["descripcion"];
$datbase=$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/formatos.dat";
$dat=$db_path.$datbase;
$fp_out=fopen($dat,"w");
if (isset($fp)){
	foreach ($fp as $value){
		if (trim($value)!=""){
			$f=explode('|',$value);
			if ($f[0]==$arrHttp["nombre"]) {
				fputs($fp_out,$arrHttp["nombre"]."|".$desc.$options."\n");
				$flag="S";
			}else{
				fputs($fp_out,trim($value)."\n");
			}
		}
	}
}
fclose($fp_out);
if ($flag!="S"){
	$fp_out=fopen($dat,"a");
	fputs($fp_out,$arrHttp["nombre"]."|".$desc.$options."\n");
	fclose($fp_out);
}
// echo that formatos.dat is updated
echo "<h3>".$datbase." ".$msgstr["updated"]."</h3>";

echo "</div></div>";
include("../common/footer.php");
