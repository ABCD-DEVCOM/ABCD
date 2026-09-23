<?php
/**
 * @program:   ABCD - ABCD-Central - https://abcd-community.org/
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      reservar_lee_usuario.php
 * @desc:      Reads the user and its statment to determine if the user can reserve
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
include("../common/get_post.php");
include("../config.php");
foreach ($arrHttp as $var=>$value) echo "$var=$value<br>";
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/prestamo.php");
include("../lang/opac.php");

// Se localiza el número de inventario en la base de datos de objetos  de préstamo
function LocalizarInventario(){
global $db_path,$Wxis,$xWxis,$wxisUrl,$arrHttp,$pft_totalitems,$pft_in,$pft_nc,$pft_typeofr,$prefix_in,$multa;

    $Expresion="IN_".$arrHttp["inventory"];
	// Se extraen las variables necesarias para extraer la información del título al cual pertenece el ejemplar
	// se toman de databases_configure_read.php
	// pft_totalitems= pft para extraer el número total de ejemplares del título
	// pft_in= pft para extraer el número de inventario
	// pft_nc= pft para extraer el número de clasificación
	// pft_typeofr= pft para extraer el tipo de registro



	//READ LOANOBJECT DATABASE TO GET THE RECORD WITH THE ITEMS OF THE TITLE
	$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";

	$Expresion=urlencode($Expresion);
	if (isset($arrHttp["db"])){
		$d=explode('|',$arrHttp["db"]);
		$arrHttp["base"]=$d[0];
		$arrHttp["db"]=$d[0];
		$pft_typeofrec=LeerPft("loans_typeofobject.pft");
		$pp=explode('~',$pft_typeofrec);
		$pft_typeofrec=$pp[0];
		$formato_ex="(v900^n'||".$d[0]."||',v900^n,'||',v900^l,'||',v900^b,'||',".$pft_typeofrec."'||',v900^v,'||',v900^t,'||'/)";
	}else{
		$arrHttp["base"]="loanobjects";
		$formato_ex="(v1[1]'||'v10[1],'||',v959^i,'||',v959^l,'||',v959^b,'||',v959^o,'||',v959^v,'||',v959^t,'||'/)";
    // control number||database||inventory||main||branch||type||volume||tome
	}

	$formato_obj="";
//	$formato_obj=$db_path."biblo/loans/".$_SESSION["lang"]."/loans_display.pft";
//	if (!file_exists($formato_obj)) $formato_obj=$db_path."biblo/loans/".$lang_db."/loans_display.pft";
	$formato_obj.=urlencode($formato_ex);
	$query = "&Opcion=disponibilidad&base=".$arrHttp["base"]."&cipar=$db_path"."par/".$arrHttp["base"].".par&Expresion=".$Expresion."&Pft=$formato_obj";
	include("../common/wxis_llamar.php");
	$total=0;
	$copies_title=array();
	$item="";

	foreach ($contenido as $linea){
		$linea=trim($linea);
		if ($linea!=""){
			if (substr($linea,0,8)=='$$TOTAL:') {
				$total=trim(substr($linea,8));
			}else{
				$t=explode('||',$linea);
				if ($arrHttp["inventory"]==$t[2]) $item=$linea;
				$copies_title[]=$linea;
			}
		}
	}
	return $copies_title;
}


?>
<html>
<head>
	<title>Reserva</title>
	<meta http-equiv="Expires" content="-1" />
	<meta http-equiv="pragma" content="no-cache" />
	<META http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<meta http-equiv="Content-Language" content="pt-br" />
	<meta name="robots" content="all" />
	<meta http-equiv="keywords" content="" />
	<meta http-equiv="description" content="" />
 	<style>
 		BODY, INPUT, SELECT, TEXTAREA,td, th {
			font-family:  Arial, Verdana, Helvetica;
			font-size: 11px;
			color: #000;
		}

 	</style>
    <script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
    <script>top.resizeTo(900,500);</script>
    <script>
    	function AnularReserva(Mfn){
    		document.anular.Mfn.value=Mfn
    		document.anular.submit()
    	}
	</script>
</head>
<body>
<form name=anular method=post action=reservar_anular.php>
<input type=hidden name=Mfn>
<input type=hidden name=usuario value=<?php echo $arrHttp["usuario"]?>>
</form>
<br>
<table width=100%>
<td>
<h4>Solicitud de reserva</h4><p>
<?php
$ec_output="";
include("leer_pft.php");
include("locales_read.php");
include("calendario_read.php");
require_once ("fecha_de_devolucion.php");
// se lee la configuración de la base de datos de usuarios

//SE LOCALIZA EL USUARIO Y  LOS PRÉSTAMOS PENDIENTES
include("borrowers_configure_read.php");
# Se lee el prefijo y el formato para extraer el código de usuario
$us_tab=LeerPft("loans_uskey.tab","users");
$t=explode("\n",$us_tab);
$uskey=$t[0];
//Se obtiene el código, tipo y vigencia del usuario
$formato=$pft_uskey.'\'$$\''.$pft_ustype.'\'$$\''.$pft_usvig;
$formato=urlencode($formato);
$query = "&Expresion=".trim($uskey).$arrHttp["usuario"]."&base=users&cipar=$db_path"."par/users.par&Pft=$formato";
$contenido="";
$IsisScript=$xWxis."cipres_usuario.xis";
include("../common/wxis_llamar.php");
$user="";
$nmulta=0;
$nsusp=0;
$msgsusp="";
$vig="";
foreach ($contenido as $linea){
	$linea=trim($linea);
	if ($linea!="")  $user.=$linea;
}
if (trim($user)==""){
	echo "<h4>".$msgstr["userne"]."</h4>";
	die;
}else{
	if ($nsusp>0) {
		 $msgsusp= "pending_sanctions";
		 $vig="N";
	}else{
	//Se analiza la vigencia del usuario
		$userdata=explode('$$',$user);
	    if (trim($userdata[2])!=""){
	    	if ($userdata[2]<date("Ymd")){
	    		$msgsusp= "limituserdata";
				$vig="N";
	    	}
    	}
    }
}


include("ec_include.php");
if (trim($ec_output))
include("opac_reservas.php");
if ($nv>0){
	print "<font color=red><h3>".$msgstr["reservations_not_allowed"]."</h3></font>";
    //Tiene préstamos vencidos. No puede reservar
}
echo $ec_output;
$ec_output="";
$reservas_vencidas="";
if ($nv >0) {
	echo "<br>";
	echo $reserva_output;
	die;
}
if ($reservas_vencidas=="S"){
	echo "<br>" ;
	echo "<font color=red><h3>Tiene reservas vencidas. No puede reservar</h3></font>";
	echo $reserva_output;
	die;
}
if ($sanctions_output!=""){
	echo "<br>";
	echo $reserva_output;
	die;
}

//SE LEE EL OBJETO PARA LOCALIZAR TODAS SUS COPIAS
$copies_title=LocalizarInventario();
//SE DETERMINA SI EL USUARIO NO TIENE YA PRESTADO OTRO EJEMPLAR DEL MISMO TÍTULO
$yaprestado="";
foreach ($copies_title as $value){
	if (trim($value)!=""){
		$c=explode('|',$value);
		$inv_c=trim($c[0]);
		foreach ($prestamos as $prest){
			if (trim($prest)!=""){
				$p=explode('^',$prest);
				if ($inv_c==trim($p[12])){
					$yaprestado="S";
					break;
				}
			}
		}
	}
}
if ($yaprestado=="S"){
	echo "<br><font color=red><strong>Ya tiene prestado un ejemplar de ese título. No puede reservar</strong></font>";
	"<p><a href=javascript:self.close>".$msgstr["cerrar"]."</a>";
	echo "<br>";
	echo $reserva_output;
	die;
}

//SE DETERMINA SI HAY EJEMPLARES DISPONIBLES PARA LA RESERVA
//PARA ELLO SE DETERMINA A) EL NÚMERO DE COPIAS EXISTENTES, B)EL NÚMERO DE COPIAS PRESTADAS
// C) Y EL NÚMERO DE COPIAS RESERVADAS (A-B-C-1)
$fecha=date("Ymd");


//SE DETERMINA SI EL USUARIO DEVOLVIÓ UN EJEMPLAR DEL MISMO TITULO EN EL DIA DE
//SOLICITUD DE LA RESERVA
$Expresion="ON_X_".$arrHttp["ctrl_num"]."_".$fecha."_".$arrHttp["usuario"];
$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
$Expresion=urlencode($Expresion);
$query = "&base=trans&cipar=$db_path"."par/trans.par&Expresion=".$Expresion;
include("../common/wxis_llamar.php");
$value=implode("",$contenido);
if (substr($value,0,9)!='$$TOTAL:0'){
	echo "<font color=red><strong>No puede solicitar reservas sobre este título. Devuelto el mismo día</strong></font>";
	echo "<br>";
	echo $reserva_output;
	die;
}
die;
//SE DETERMINA EL NUMERO DE RESERVAS QUE TIENE EL USUARIO
//RESERVAS SE LLENA EN OPAC_RESERVAS.PHP
if (count($reservas)>=2){
	echo "<font color=red><strong>Ya alcanzó su límite de reservas<strong></font>";
	echo "<br>";
	echo $reserva_output;
	die;
}

//SE DETERMINA SI EL USUARIO TIENE YA UNA RESERVA SOBRE ESE TÍTULO
foreach ($reservas as $lr){
	$lr_arr=explode('|',$lr);
	if ($arrHttp["ctrl_num"]==$lr_arr[5]){
		echo "<font color=red><strong>Ya tiene una reserva sobre el título solicitado</strong></font>";
		echo "<br>";
		echo $reserva_output;
		die;
	}
}


//NUMERO DE COPIAS (OBTENIDAS DE LOANOBJECTS)
$num_copias=count($copies_title);

// COPIAS PRESTADAS
$Expresion="ON_P_".$arrHttp["ctrl_num"];
$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
$Expresion=urlencode($Expresion);
$query = "&base=trans&cipar=$db_path"."par/trans.par&Expresion=".$Expresion."&Pft=v40/";
include("../common/wxis_llamar.php");
$copias_prestadas=0;
$copias_pres=array();
foreach ($contenido as $value) {
	$value=trim($value);
	if ($value!="") {
		if (substr($value,0,8)!='$$TOTAL:'){
			$copias_prestadas=$copias_prestadas+1;
			$copias_pres[]=$value;
		}
	}

}


// COPIAS RESERVADAS
$Expresion="CN_".$arrHttp["ctrl_num"];
$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
$Expresion=urlencode($Expresion);
$query = "&base=reserve&cipar=$db_path"."par/reserve.par&Expresion=".$Expresion."&Pft=v40/";
include("../common/wxis_llamar.php");
$copias_reservadas=0;
foreach ($contenido as $value) {
	$value=trim($value);
	if ($value!="") {
		if (substr($value,0,8)!='$$TOTAL:'){
			if ($value<=$fecha){
				$copias_reservadas++;
			}
		}
	}

}
if ($num_copias-$copias_prestadas-$copias_reservadas-1<=0){
	echo "<font color=red><strong>No existen copias disponibles para la reserva</strong></font>";
	echo "<br>";
	echo $reserva_output;
	die;
}


//SE PROCESA LA RESERVA
//Primero se determina la fecha hasta tanto está activa la reserva

$f_dev=FechaDevolucion(1,"D","");

$ValorCapturado="<10 0>".$arrHttp["usuario"]."</10><20 0>".$arrHttp["ctrl_num"]."</20><30 0>".date('Ymd')."</30><31 0>".date("H:i:s")."<32 0>web</32><40 0>".substr($f_dev,0,8)."</40>";
$IsisScript=$xWxis."actualizar.xis";
$query = "&base=reserve&cipar=$db_path" . "par/reserve.par&path_db=$db_path&login=abcd&Mfn=New&Opcion=crear&ValorCapturado=" . $ValorCapturado;include("../common/wxis_llamar.php");
foreach ($contenido as $linea){
	if (substr($linea,0,4)=="MFN:") {
    	$arrHttp["Mfn"]=trim(substr($linea,4));
	}else{
		if (trim($linea!="")) $salida.= $linea."\n";
	}
}
if (isset ($arrHttp["Mfn"])) {
	print "<span class=titulo1><strong>".$msgstr["reserve_success="]."</strong></span>";
}



echo "<br>";
include("opac_reservas.php");
echo $reserva_output;

?>

</body>
</html>