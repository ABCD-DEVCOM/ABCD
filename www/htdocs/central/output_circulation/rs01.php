<?php
/**
 * @program:   ABCD - ABCD-Central - http://reddes.bvsaude.org/projects/abcd
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      rs01.php
 * @desc:      Objetos reservados
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
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
$script_php="../output_circulation/rs01.php";

$debug="";
if (!isset($_SESSION["login"])) die;
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include("../common/get_post.php");
if (!isset($arrHttp["base"])) $arrHttp["base"]="reserve";
//foreach ($arrHttp as $var=>$value)  echo "$var=>$value<br>";  //die;
if (!isset($arrHttp["vp"])) $arrHttp["vp"]="";
include("../config.php");
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/prestamo.php");

$bd=$arrHttp["base"];

if (isset($arrHttp["code"]) and $arrHttp["code"] =="reassign")
	$Nombre=$msgstr["assign_reserve"];
else
	$Nombre=$msgstr[$arrHttp["name"]];

function LlamarWxis($IsisScript,$query){
global $db_path,$Wxis,$wxisUrl,$xWxis;
	include("../common/wxis_llamar.php");
	return $contenido;
}

function MostrarRegistroCatalografico($dbname,$CN){
global $msgstr,$arrHttp,$db_path,$xWxis,$tagisis,$Wxis,$wxisUrl,$lang_db;
	$pref_cn="";
	$archivo=$db_path.$dbname."/loans/".$_SESSION["lang"]."/loans_conf.tab";
	if (!file_exists($archivo)) $archivo=$db_path.$dbname."/loans/".$lang_db."/loans_conf.tab";
	$fp=file_exists($archivo);
	if ($fp){
		$fp=file($archivo);
		foreach ($fp as $value){
			$t=explode(" ",trim($value));
			if ($t[0]=="NC")
				$pref_cn=$t[1];
		}
	}
	if ($pref_cn=="") $pref_cn="CN_";
	$Expresion=$pref_cn.$CN;
	$formato_obj=$db_path.$dbname."/loans/".$_SESSION["lang"]."/loans_display.pft";
	if (!file_exists($formato_obj)) $formato_obj=$db_path.$dbname."/loans/".$lang_db."/loans_display.pft";
	$arrHttp["count"]="";
	$Formato=$formato_obj;
	$IsisScript=$xWxis."buscar_ingreso.xis";
	$query = "&base=$dbname&cipar=$db_path"."par/".$dbname.".par&Expresion=".$Expresion."&Formato=$formato_obj";
	include("../common/wxis_llamar.php");
	$salida="";
	foreach ($contenido as $value){
		if (substr($value,0,8)!="[TOTAL:]" and substr($value,0,6)!="[MFN:]" and trim($value)!="")
			$salida.=$value;
	}

	return $salida;
}

include("../common/header.php");

//include("../common/institutional_info.php");
include("../circulation/scripts_circulation.php");
?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $Nombre;
		?>
	</div>
	<div class="actions">
		<?php 
		//$backtoscript="../circulation/estado_de_cuenta.php?reserve=S";
		//include "../common/inc_back.php"; ?>
	</div>
<?php //include("../circulation/submenu_prestamo.php");?>
</div>

<div class="middle form">
	<div class="formContent">
<?php
echo "<H3>";
echo "<br>".$msgstr["o_issued"].": ".date("d-m-Y")."</h3>";
echo "<p><table border=0 width=80% bgcolor=#cccccc>";

//SE LEE EL NÚMERO DE CONTROL DE LAS RESERVAS VIGENTES
$archivo=$db_path."reserve/pfts/".$_SESSION["lang"]."/rsvr_h.txt";
if (!file_exists($archivo))
	$archivo=$db_path."reserve/pfts/".$lang_db."/rsvr_h.txt";
if (file_exists($archivo)){
	$fp=file($archivo);
	foreach ($fp as $value){
		$value=trim($value);
		if (trim($value)!=""){
			echo "<td><strong>$value</strong></td>";
		}
	}
}else{
	$archivo=$db_path."reserve/pfts/".$_SESSION["lang"]."/tit_reserve_01.tab";
	if (!file_exists($archivo)){
		$archivo=$db_path."reserve/pfts/".$lang_db."/tit_reserve_01.tab";
		if (!file_exists($archivo)){
			$msgerr= $archivo. " ** ".$msgstr["falta"];
			die;
		}
	}
	$fp=file($archivo);
	foreach ($fp as $value){
		if (trim($value)!=""){
			$t=explode("|",$value);
			foreach($t as $head){
				echo "<td>$head</td>";
			}
		}
		break;
	}
}
$data="";
$Disp_format="rsvr.pft";
$Pft=$db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$Disp_format;
if (!file_exists($Pft)){
	$Pft=$db_path.$arrHttp["base"]."/pfts/".$lang_db."/".$Disp_format;
}
if (!file_exists($Pft)){
	$Disp_format="reserve_01.pft";
	$Pft=$db_path.$arrHttp["base"]."/pfts/".$_SESSION["lang"]."/".$Disp_format;
	if (!file_exists($Pft)){
		$Pft=$db_path.$arrHttp["base"]."/pfts/".$lang_db."/".$Disp_format;
		if (!file_exists($pft)){
			echo $msgerr= $Disp_format. " ** ".$msgstr["falta"];
			die;
		}
	}
}
$Pft=urlencode("v15`$$$`V20`$$$`V10, `$$$`V30,`$$$`V40,`$$$`V1".'`$$$`f(mfn,1,0)`$$$`,@'.$Pft);
//v15: Base de datos
//v20: Número de control
//v30: Fecha de reserva
//v40: Esperar hasta
//v1:  Situacion
switch ($arrHttp["code"]){
	case "today":
		$fecha=date("Ymd");
		$Expresion="(ST_0 or ST_3) and FA_$fecha";
		$ec="S";
        break;
  	case "actives":
  		$Expresion="(ST_0)";
  		$ec="S";
  		break;
  	case "assigned":
  		$Expresion="ST_3";
  		$ec="S";
  		break;
  	case "overdued":
  		$Expresion="(ST_0 or ST_3)";
  		$ec="N";
  		break;
  	case "attended":
  		$Expresion="ST_4";
  		$ec="N";
  		break;
  	case "cancelled":
  		$Expresion="(ST_1 or ST_2)";
  		$ec="N";
  		break;
  	case "reassign":
  		$Expresion="ST_0 and CN_".$arrHttp["base_reserve"]."_".$arrHttp["ncontrol"];
  		$ec="S";
  		break;

}
$Sort="";
if (isset($arrHttp["sort"])){
	switch ($arrHttp["sort"]) {
		case "name":
			$Sort="ref(['users']l(['users']'CO_'v10),v30)";
			break;
		case "date_reservation":
			$Sort="v30";
			break;
		case "date_assigned":
			$Sort="v60";
			break;
		case "date_loaned":
			$Sort="v200";
			break;
	}
}
if (isset($arrHttp["user"])) $Expresion.=" and CU_".$arrHttp["user"];
echo $Expresion." ";
echo "<a href=\"javascript:Eliminar('$Expresion')\">eliminar</a>";
$Expresion=urlencode($Expresion);
if ($ec=="S"){
	echo "<td>".$msgstr["vence"]."</td>"."<td>".$msgstr["suspen"]."</td>"."<td>".$msgstr["multas"]."</td><td></td>";
}
$query = "&base=reserve&cipar=$db_path"."par/reserve.par&Expresion=$Expresion&Opcion=buscar&Formato=".$Pft;
if ($Sort==""){
	$IsisScript=$xWxis."imprime.xis";
}else{
	$query.='&sortkey='.urlencode($Sort);
	$IsisScript=$xWxis."sort.xis";
}
$contenido=LlamarWxis($IsisScript,$query);

$key_comp="";
$found=0;
foreach ($contenido as $value){
	$value=trim($value);
	if ($value!=""){
		$v=explode('$$$',$value);
		$found=$found+1;
		//SE LEEN LA BASE DE DATOS Y EL NÚMERO DE CONTROL PARA UBICAR EL TÍTULO Y DETERMINAR LOS EJEMPLARES DISPONIBLES
		$bd=$v[0];
		$Control_no=$v[1];
		$arrHttp["usuario"]=$v[2];
		$fecha_reserva=$v[3];
		$esperar_hasta=$v[4];
		$Mfn=$v[6];
		$status=$v[5];
		if ($arrHttp["code"]=="overdued"){
			if ($esperar_hasta=="" and $status==0) continue;
			if ($esperar_hasta!="" and $status==3 and $esperar_hasta>=date("Ymd")) continue;
		}
		$Referencia=MostrarRegistroCatalografico($v[0],$v[1]);
		if ($ec=="S"){
			//SE LEEN LOS PRESTAMOS PENDIENTES DEL USUARIO PARA DETERMINAR SI HAY ATRASOS
			$query = "&Expresion=TRU_P_".$arrHttp["usuario"]."&base=trans&cipar=$db_path"."par/trans.par&Pft=V40/";
			$IsisScript=$xWxis."cipres_usuario.xis";
			$pp=LlamarWxis($IsisScript,$query);
			$vencidos=0;
			foreach ($pp as $prestamo)  {
				$prestamo=trim($prestamo);
				if ($prestamo!=""){
					if ($prestamo<date("Ymd")){
						$vencidos=$vencidos+1;
					}
				}
			}
			//SE LEEN LAS SUSPENSIONES DEL USUARIO
			$Expresion="(TR_S_".$arrHttp["usuario"]." or TR_M_".$arrHttp["usuario"].") and ST_0";

			$query = "&Expresion=$Expresion&base=suspml&cipar=$db_path"."par/suspml.par&Pft=v1'|',v60/";
			$IsisScript=$xWxis."cipres_usuario.xis";
			$total_multa=0;
			$total_susp=0;
			$sm=LlamarWxis($IsisScript,$query);
			foreach ($sm as $suspml)  {
				$suspm=trim($suspml);
				if ($suspml!=""){
					$vdate=explode('|',$suspml);
					switch ($vdate[0]){
						case "S":
							if ($vdate[1]>date("Ymd"))
								$total_susp=$total_susp+1;
							break;
						case "M":
							$total_multa=$total_multa+1;
							break;
					}
				}
			}
		}
		$reservas=explode("|",$v[7]);
		echo "<tr>";
		foreach ($reservas as $rr){
			if (trim($rr)=="#REFER#"){
				echo "<td bgcolor=white valign=top>$Referencia</td>";
			}else{
				echo "<td bgcolor=white valign=top nowrap>$rr</td>";
			}
		}
		if ($ec=="S"){
			echo "<td bgcolor=white valign=top width=10 align=center>" ;
			if ($vencidos==0) $vencidos="";
			if ($total_susp==0) $total_susp="";
			if ($total_multa==0) $total_multa="";
			echo "$vencidos</td><td bgcolor=white valign=top width=10 align=center>$total_susp</td><td bgcolor=white valign=top align=center>$total_multa</td>";
			echo "</td>";
		}
		echo "<td bgcolor=white nowrap valign=top>";
		if ($esperar_hasta!="" and $status!=4 ){
			if (date("Ymd")>$esperar_hasta ){
				if ($status!=1 and $status!=2) echo "<font color=red>".$msgstr["rs03"]."</font>";
			}else{
				if ($status !=1 and $status!=2){
					echo '<a href=javascript:SendMail("assigned",'.$Mfn.') alt="'.$msgstr["mail_notif_res_assign"].'" title="'.$msgstr["mail_notif_res_assign"].'"><img src=../dataentry/img/mail_p.png></a>';
			    	echo "&nbsp; <a href=\"javascript:PrintReserve('assigned',".$Mfn.")\"><img src=../dataentry/img/toolbarPrint.png></a>";
				}
			}
		}
		if ($arrHttp["code"]!="attended" and $arrHttp["code"]!="cancelled")
			echo "&nbsp;<a href=javascript:CancelReserve(".$Mfn.",'".$bd."','".$Control_no."')><img src=../dataentry/img/toolbarCancelEdit.png alt='".$msgstr["cancel"]."' title='".$msgstr["cancel"]."'></a>";
		if ($arrHttp["code"]=="reassign"){
			echo "&nbsp;<a href=javascript:AssignReserve(".$Mfn.")><img src=../dataentry/img/barEdit.png height=16 alt='".$msgstr["rs02"]."' title='".$msgstr["rs02"]."'></a>";
		}
		echo "</td>";
		echo "</tr>";
	}
}
if ($key_comp!=""){
	echo "</form>";
}
?>
</table>
<?php
if ($found==0){
	echo "<h2>".$msgstr["nfres"]."</h2>";
}
?>
</div>

</div>




