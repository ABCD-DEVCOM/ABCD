<?php
/*
20220207 fho4abcd div-helper
*/
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";

include("../lang/admin.php");
include("../common/header.php");
// This script runs always inside a frame: no institutional info
include "../common/inc_div-helper.php";
global $ValorCapturado;
include("actualizarregistro.php");
require_once ('plantilladeingreso.php');


function VariablesDeAmbiente($var,$value){
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
			}
		}
}


/////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////

$arrHttp=Array();
foreach ($_REQUEST as $var => $value) {
	if (trim($value)!="") VariablesDeAmbiente($var,$value);
}

foreach ($arrHttp as $var => $value) {
	if (substr($var,0,3)=="tag" ){
		$tag=explode("_",$var);
		if (substr($tag[0],3)>3000 and substr($tag[0],3)<4000){  //IF LEADER, REFORMAT THE FIELD FOR ELIMINATING |
			$v=explode('|',$value);
			$value=$v[0];
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
foreach ($valortag as $tag=>$value){
	if (strlen($tag)==1) $tag="000".$tag;
	if (strlen($tag)==2) $tag="00".$tag;
	if (strlen($tag)==3) $tag="0".$tag;
	$ValorCapturado.=$tag.$value."$$$";
}
if (isset($arrHttp["check_select"])){
    	$dummy=array();
    	$dummy=explode("\n",$arrHttp["check_select"]);
    	foreach ($dummy as $value){
    		if (trim($value)!=""){
	    		$ixD=strpos($value,"_");
	    		if ($ixD>0){
		    		$parte1=substr($value,0,$ixD);
		    		$parte2=substr($value,$ixD+1);
		    		$k=trim(substr($parte1,3));
		    		$key=trim(substr($parte1,3));
					if (strlen($key)==1) $key="000".$key;
					if (strlen($key)==2) $key="00".$key;
					if (strlen($key)==3) $key="0".$key;
				//	$parte2=stripslashes($parte2);
				//	$parte2=str_replace("'","&acute;",$parte2);
					unset($p2);
					$p2=explode("_",$parte2);
					if (isset($p2[2])){
					}else{
				    	$ValorCapturado.=$key.$parte2."$$$";
					}
				}
             }
    	}
	}

$_SESSION["valdef"]=$ValorCapturado;
echo "<br><br><center><h2>".$msgstr["valdef"]." ".$msgstr["actualizados"]."</h2>";



?>