<?php
/*
20220214 fho4abcd Do not use DOCUMENT_ROOT but the database base+ allow %path_database%
20220224 fho4abcd solve indexing
*/
/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      upload_img.php
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

ob_start();
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
include ("../config.php");


$db=$arrHttp["base"];
if (!isset($_SESSION["permiso"]["CENTRAL_EDREC"]) and !isset($_SESSION["permiso"][$db."_CENTRAL_EDREC"])
and !isset($_SESSION["permiso"]["CENTRAL_CREC"])  and !isset($_SESSION["permiso"][$db."_CENTRAL_CREC"])
and !isset($_SESSION["permiso"]["CENTRAL_ALL"])
and !isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
	echo "<h4>".$msgstr["invalidright"]."</h4>";
	die;
}

if (!isset($ext_allowed)){
	//extension allowed for uploading files (used in dataentry/)
	$ext_allowed=array("jpg","gif","png","pdf","doc","docx","xls","xlsx","odt");
}
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/soporte.php");

function NoImage(){
	global $msgstr,$arrHttp;
	echo "<form name=regresar method=post action=upload_file.php>\n";
	foreach($arrHttp as $var=>$value){
		echo "<input type=hidden name=$var value=$value>\n";
	}
	echo "</form>";
	echo "<font color=red face=arial size=2>".$msgstr["nouploadfile"]."<font color=black><p>";
	echo "<a href=javascript:document.regresar.submit()>".$msgstr["regresar"]."<a>";
	die;
}

//foreach($arrHttp as $var=>$value) echo "$var=$value<br>";
// =========== See equivalent code in dirs_explorer.php==========//
$img_path="";
if (file_exists($db_path.$arrHttp["base"]."/dr_path.def")){
	$def = parse_ini_file($db_path.$arrHttp["base"]."/dr_path.def");
    if (isset($def["ROOT"]) && trim($def["ROOT"]!="")){
        $img_path=trim($def["ROOT"]);
        $img_path=str_replace("%path_database%",$db_path,$img_path);
        $name_path=$msgstr["root_from_dr"];
        if (!file_exists($img_path)) mkdir($img_path,0770,true);
    }
}
if ($img_path==""){
    $img_path=$db_path.$arrHttp["base"]."/";
    $name_path="%path_database%".$arrHttp["base"]."/";
}

if (!is_dir($img_path)){
    echo "<h3>".$msgstr["dirne"]." (".$name_path.")</h3> ";
	NoImage();
	die;
}

$files = $_FILES['userfile'];

$store_in="";
if (isset($arrHttp["storein"])){
	$store_in = $arrHttp["storein"];
	$len=strlen($store_in);
	if (substr($store_in,$len-1,1)!="/")  $store_in.="/";
}
$uploaded_filenames = [];
foreach ($files['name'] as $key=>$name) {
	if (trim($name)==""){
		NoImage();
		break;
	}
	if ($name!=""){
	  	echo "$name<br>";
	  	$max=get_cfg_var ("upload_max_filesize");
	    if ((int)$files['size'][$key]==0){
	    	$max=get_cfg_var ("upload_max_filesize");
	    	echo "upload_max_filesize = $max<br>";
	    	echo "File to big. Could not be uploaded. Modify the parameter upload_max_filesize in php.ini";
	    	die;
	    }
	    foreach ($ext_allowed as $value){
	    	$ext_allowed[]=strtoupper($value);
	    }
	    $ext = pathinfo($name, PATHINFO_EXTENSION);
		if(!in_array(strtoupper($ext),$ext_allowed) ) {
			echo $msgstr["validextensions"]." ";
			foreach ($ext_allowed as $value) echo "$value ";
			echo ' &nbsp; (<b>$ext_allowed</b> in <b>config.php</b>)<br>';
    		NoImage();
    		die;
		}
		if ($files['size'][$key]) {
	      // clean up file name
	   		if (isset($fix_file_name))
				$name = strtr( $name, $fix_file_name );
	  		$location = $img_path.$store_in.$name;
	  		$location=str_replace('//',"/",$location);
	  		$loc=$store_in;
	  		if (substr($loc,0,1)=="/") $loc=substr($loc,1);
			echo "<html>\n";
			echo "<title>".$msgstr["uploadfile"]."</title>\n";
			echo "<script language=\"JavaScript\" type=\"text/javascript\" src=js/lr_trim.js></script>\n";
			echo "<body>\n";
			echo "<font face=verdana>\n";
			//echo "location: ".$location;
			if (!copy($files['tmp_name'][$key], $location)) {
				echo "<p>" . $msgstr["archivo"] . " " . $location . " " . $msgstr["notransferido"];
			} else {
				$uploaded_filenames[] = $loc . $name; // GRAVA NO NOSSO ARRAY PARA O JSON
				echo "<p>*** " . $msgstr["archivo"] . " " . $location . " " . $msgstr["transferido"];
				$subc_a="";
			  	if (isset($arrHttp["Tag"])){
			  		if (strpos($arrHttp["Tag"],"_")===false){
				  		if (isset($arrHttp["subc"])){
				  			if (strlen($arrHttp["subc"])>1){
				  				$subc_a=substr($arrHttp["subc"],0,1);
				  				if ($subc_a!="-")
				  					$subc_a="^".$subc_a;
								else
									$subc_a="";
				  				$subc_b="^".substr($arrHttp["subc"],1,1);
				  			}else{
				  				$subc_a="";
				  				$subc_b="^".substr($arrHttp["subc"],0,1);
				  			}
				  		}else{
				  			$subc_a="";
				  			$subc_b="^d";
				  		}
					}
				  	echo "<script>
						  campo=window.opener.document.forma1.".$arrHttp["Tag"].".value
						  if (Trim(campo)==\"\")
						  	campo+=\"".$subc_a."$loc"."$name\"\n
						  else
						  	campo+='\\r'+\"".$subc_a."$loc"."$name\"\n";
						  if (isset($arrHttp["descripcion"]) and trim($arrHttp["descripcion"])!="")
						  	echo "campo+=\"".$subc_b.$arrHttp["descripcion"]."\"\n";
			              echo "
						  window.opener.document.forma1.".$arrHttp["Tag"].".value=campo
						  window.opener.document.forma1.".$arrHttp["Tag"].".rows++
						  window.opener.top.img_dir=\"$loc\"
						  self.close()
						  </script>
				  		  ";

				}
	  			unlink($files['tmp_name'][$key]);
	  		}
	   	}
	 }
}

// --- RESPOSTA AJAX DE LOTE (Fora do foreach) ---
if (isset($_POST['ajax_upload']) && $_POST['ajax_upload'] == '1') {
	ob_end_clean(); // Apaga o HTML da memória
	header('Content-Type: application/json');
	echo json_encode([
		'success' => count($uploaded_filenames) > 0,
		'filenames' => $uploaded_filenames,
		'message' => $msgstr["upload_success"] ?? 'Upload concluído com sucesso',
	]);
	die();
}
// -----------------------------------------------
echo "</body>\n";
echo "</html>\n";
?>