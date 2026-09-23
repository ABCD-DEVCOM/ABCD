<?php
include("../common/get_post.php");
//include("../config.php");
$db_path=$arrHttp["db_path"];
$lang=$arrHttp["lang"];
$Mfn=$arrHttp["Mfn"];
//foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";  //die;
	$fecha_dev=date("Ymd");
	$hora_dev=date("H:i:s");
	$ValorCapturado="d1d130d131d132<1 0>2</1>";
	$ValorCapturado.="<130 0>$fecha_dev</130>";
	$ValorCapturado.="<131 0>$hora_dev</131>";
	$ValorCapturado.="<132 0>web</132>";
	$ValorCapturado=urlencode($ValorCapturado);
	$IsisScript=$xWxis."actualizar_registro.xis";
	$Formato="";
$query = "&base=reserve&cipar=$db_path" . "par/reserve.par&path_db=$db_path&login=web&Mfn=" . $Mfn . "&ValorCapturado=" . $ValorCapturado;
	include("../common/wxis_llamar.php");

header("Location:$OpacHttp"."opac_statment_call.php?usuario=".$arrHttp["usuario"]."&vienede=ecta_web&db_path=$db_path&lang=$lang");

?>
