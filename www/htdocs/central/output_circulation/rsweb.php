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
$script_php="../output_circulation/rsweb.php";

$debug="";
if (!isset($_SESSION["login"])){
	echo "falta login"; die;
}
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include("../common/get_post.php");
if (!isset($arrHttp["base"])) $arrHttp["base"]="reserve";
//foreach ($arrHttp as $var=>$value)  echo "$var=>$value<br>";  //die;
if (!isset($arrHttp["vp"])) $arrHttp["vp"]="";
include("../config.php");
//var_dump($def);die;
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/prestamo.php");

$bd=$arrHttp["base"];
/////////////////////////////////////////////////////////////////////////////////////////////
//Se lee el bases.dat de central para verificar si la base de datos trabaja con copies
$fpBases=file($db_path."bases.dat");
$with_copies=array();
foreach ($fpBases as $value) {
	$value=trim($value);
	if ($value!=""){
		$vbd=explode('|',$value);
		if (isset($vbd[2]) and $vbd[2]=="Y"){
			$with_copies[$vbd[0]]="Y";
		}
	}
}
function LlamarWxis($IsisScript,$query){
global $db_path,$Wxis,$wxisUrl,$xWxis;
	include("../common/wxis_llamar.php");
	return $contenido;
}

function DeterminarReservasActivas($db_path,$base,$lang,$msgstr,$Ctrl,$fecha_r,$usuario){
global $arrHttp,$xWxis,$Wxis;
	$data="";
	$Disp_format="rsvr.pft";
	$Pft=$db_path."reserve/pfts/".$lang."/".$Disp_format;
	if (!file_exists($Pft)){
		echo $msgerr= $Disp_format. " ** ".$msgstr["falta"];
		die;
	}
	$Pft=urlencode("v15`$$$`V20`$$$`V10, `$$$`V30,`$$$`V40,`$$$`V1".'`$$$MFN:`f(mfn,1,0)`$$$`,@'.$Pft);
//v15: Base de datos
//v20: Número de control
//v10: Código de usuario
//v30: Fecha de reserva
//v40: Esperar hasta
//v1:  Situacion
	$Expresion="(ST_0 or ST_3) and CN_".$base."_".$Ctrl;
	$ec="";
	$Sort="";
	$Expresion=urlencode($Expresion);
	$query = "&base=reserve&cipar=$db_path"."par/reserve.par&Expresion=$Expresion&Opcion=buscar&Pft=".$Pft;
	$IsisScript=$xWxis."buscar_ingreso.xis";

	include("../common/wxis_llamar.php");
	$ix=0;
	$salida="";
	foreach ($contenido as $value){
		$value=trim($value);
		$vv=explode('$$$',$value);
		if (isset($vv[2]) and isset($vv[3])){
			$ix=$ix+1;
			if ($ix==1 and $vv[2]==$usuario) return "";
			$fecha=substr($vv[3],6,2)."/".substr($vv[3],4,2)."/".substr($vv[3],0,4);
			$salida.= "<tr><td>".$vv[2]."</td><td>".$fecha."</td></tr>\n";
		}

	}
	if ($salida!="")
		$salida="<table><th>Usuario</th><th>F.reserva</th>".$salida."</table>";
    return $salida;
}


function LeerPft($pft_name,$base){
global $arrHttp,$db_path,$lang_db;
	$pft="";
	$archivo=$db_path.$base."/loans/".$_SESSION["lang"]."/$pft_name";
	if (!file_exists($archivo)) $archivo=$db_path.$base."/loans/".$lang_db."/$pft_name";
	$fp=file_exists($archivo);
	if ($fp){
		$fp=file($archivo);
		foreach ($fp as $value){
			$pft.=$value;
		}

	}
    return $pft;
}

function LeerNumeroClasificacion($base){
global $db_path,$lang_db,$prefix_nc,$prefix_in;
	$prefix_nc="";
	$archivo=$db_path.$base."/loans/".$_SESSION["lang"]."/loans_conf.tab";
	if (!file_exists($archivo)) $archivo=$db_path.$base."/loans/".$lang_db."/loans_conf.tab";
	$fp=file_exists($archivo);
	if ($fp){
		$fp=file($archivo);
		foreach ($fp as $value){
			$ix=strpos($value," ");
			$tag=trim(substr($value,0,$ix));
			switch($tag){
				case "IN": $prefix_in=trim(substr($value,$ix));
					break;
				case "NC":
					$prefix_nc=trim(substr($value,$ix));
					break;
			}
		}
	}else{
		$prefix_nc="CN_";
	}
}

//se busca el numero de control en el archivo de transacciones para ver si el usuario tiene otro ejemplar prestado
function LocalizarTransacciones($control_number,$prefijo,$base_origen){
global $db_path,$Wxis,$xWxis,$wxisUrl,$arrHttp,$msgstr;

	$tr_prestamos=array();
	$formato_obj=$db_path."trans/pfts/".$_SESSION["lang"]."/loans_display.pft";
	if (!file_exists($formato_obj)) $formato_obj=$db_path."trans/pfts/".$lang_db."/loans_display.pft";
	$query = "&Expresion=ON_P_".$control_number;
	if (isset($arrHttp["year"]) or isset($arrHttp["volumen"])){
		$query.="_";
		if (isset($arrHttp["year"])) $query.="A:".$arrHttp["year"];
		if (isset($arrHttp["volumen"])) $query.="V:".$arrHttp["volumen"];
		if (isset($arrHttp["numero"])) $query.="N:".$arrHttp["numero"];
	}
	$query.="&base=trans&cipar=$db_path"."par/trans.par&Formato=".$formato_obj;
	$IsisScript=$xWxis."cipres_usuario.xis";
	include("../common/wxis_llamar.php");
	$prestamos=array();
	foreach ($contenido as $linea){
		if (trim($linea)!=""){
			$l=explode('^',$linea);
			$NC=$l[12];
			if (isset($l[13])){
				if ($base_origen==$l[13])
					$tr_prestamos[$l[0]]=$linea;
			}else{
				$tr_prestamos[$l[0]]=$linea;
			}
        }
	}
	return $tr_prestamos;
}

function Disponibilidad($control_number,$catalog_db,$items_prestados,$prefix_cn,$copies,$pft_ni){
global $xWxis,$Wxis,$db_path,$msgstr,$wxisUrl;
	//DETERMINAMOS EL TOTAL DE EJEMPLARES QUE TIENE EL TITULO
	$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
	if ($copies=="Y"){
		$Expresion="CN_".$catalog_db."_".$control_number;
		$catalog_db="loanobjects";
		$pft_ni="(v959/)";
	}else{
		//SE LEE EL PREFIJO A UTILIZAR PARA LOCALIZAR EL OBJETO A TRAVÉS DE SU NÚMERO DE INVENTARIO
		$Expresion=$prefix_cn.$control_number;
		$catalog_db=strtolower($catalog_db);
		$ni_pft=explode('~',$pft_ni);
		$pft_ni=$ni_pft[0];
		if (isset($ni_pft[1]) and trim($ni_pft[1])!="")
			$pft_ni.="(".$ni_pft[1]."/)";

	}
	$query = "&Opcion=disponibilidad&base=$catalog_db&cipar=$db_path"."par/$catalog_db.par&Expresion=".$Expresion."&Pft=".urlencode($pft_ni);
	include("../common/wxis_llamar.php");
	$obj=array();
	foreach ($contenido as $value){
		$value=trim($value);
		if (trim($value)!="" and substr($value,0,8)!='$$TOTAL:')
			$obj[]=$value;
	}
	$disponibilidad=count($obj)-count($items_prestados);
	return $disponibilidad;
}


function MostrarRegistroCatalografico($dbname,$CN,$with_copies){
global $msgstr,$arrHttp,$db_path,$xWxis,$tagisis,$Wxis,$wxisUrl,$lang_db,$Pft_ni,$prefix_nc,$prefix_in;
	$pref_cn="";
	if (!isset($with_copies[$dbname]) or $with_copies[$dbname]=="N" or trim($with_copies[$dbname]=="N")){
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
	if (isset($with_copies[$dbname]) and $with_copies[$dbname]=="Y"){
		$copies="Y";
		$db_copies="loanobjects";
		$Pft_copies="(v959^i/)";
		$Expresion=$pref_cn.$dbname."_".$CN;
	}else{
		$copies="N";
		$db_copies=$dbname;
		$Pft_copies="($Pft_ni/)";
		$Expresion=$pref_cn.$CN;
	}
	$query = "&Opcion=disponibilidad&base=$db_copies&cipar=$db_path"."par/$db_copies.par&Expresion=".$Expresion."&Pft=$Pft_copies";
	include("../common/wxis_llamar.php");
	$items_existentes=$contenido;
	$items_prestados=LocalizarTransacciones($CN,$pref_cn,$dbname);

    $disponibilidad=Disponibilidad($CN,$dbname,$items_prestados,$pref_cn,$copies,$Pft_copies);
	return array($salida,$items_existentes,$items_prestados);
}

include("../common/header.php");

//include("../common/institutional_info.php");
include("../circulation/scripts_circulation.php");

switch ($arrHttp["code"]){
	case "today":
		$fecha=date("Ymd");
		$Expresion="(ST_0 or ST_3) and FR_$fecha";
		$ec="S";
		$Nombre=$msgstr["rs00"];
        break;
	case "actives":
		$Expresion="(ST_0 or ST_3)";
		$Nombre=$msgstr["rs01"];
		$ec="S";
		break;
	case "actives_web":
		$Expresion="(ST_0 or ST_3) and OR_WEB";
		$Nombre=$msgstr["rs00"];
		$ec="S";
		break;
	case "assigned":
  		$Expresion="ST_3";
  		$Nombre=$msgstr["web_reserve"];
  		$ec="S";
  		break;

}
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

echo "<H3> ";
echo "<br>".$msgstr["o_issued"].": ".date("d-m-Y")."</h3>";
echo "<p><table border=0 width=80% bgcolor=#cccccc>";
$ec="S";

//SE LEE EL NÚMERO DE CONTROL DE LAS RESERVAS VIGENTES
$archivo=$db_path."reserve/pfts/".$_SESSION["lang"]."/rsvr_h.txt";
if (!file_exists($archivo)){
	$archivo=$db_path."reserve/pfts/".$lang_db."/rsvr_h.txt";

}
if (file_exists($archivo)){
	$fp=file($archivo);
	$ixt=0;
	foreach ($fp as $value){
		$value=trim($value);
		if (trim($value)!=""){
			$ixt=$ixt+1;
			if ($ixt>6) break;
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
			$ixt=0;
			foreach($t as $head){
				$ixt=$ixt+1;
				if ($ixt>6) break;
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
//v10: Código de usuario
//v30: Fecha de reserva
//v40: Esperar hasta
//v1:  Situacion


$Sort="";
//$Sort="v30";

if (isset($arrHttp["user"])) $Expresion.=" and CU_".$arrHttp["user"];
//echo "<a href=\"javascript:Eliminar('$Expresion')\">eliminar</a>";
echo $Expresion;
$Expresion=urlencode($Expresion);

if ($ec=="S"){
	echo "<td>".$msgstr["vence"]."</td>"."<td>".$msgstr["suspen"]."</td>"."<td>".$msgstr["multas"]."</td>"."<td>".$msgstr["reserve"]."</td><td></td>";
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
			if ($status==0) continue;
			if ($esperar_hasta!="" and $status==3 and $esperar_hasta>=date("Ymd")) continue;
		}
		$Pft_ni=LeerPft("loans_inventorynumber.pft",$v[0]);
		LeerNumeroClasificacion($v[0]);
		$Referencia=MostrarRegistroCatalografico($v[0],$v[1],$with_copies);
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
		$ixt=0;

		foreach ($reservas as $rr){
			$fecha_de_reserva="";
			if (trim($rr)=="#REFER#"){
				echo "<td bgcolor=white valign=top>".$Referencia[0]."<br>ejemplares:<br>";
				echo "<table bgcolor=#cccccc><td bgcolor=white><strong>".$msgstr["inventory"]."</strong></td><td bgcolor=white><strong>".$msgstr["devdate"]."</strong></td>";
				foreach ($Referencia[1] as $value) {
					$value=trim($value);
					if ($value=="" or substr($value,0,6)=="[MFN:]" or substr($value,0,8)=="[TOTAL:]")  continue;
					echo "<tr><td bgcolor=white>$value</td>";
					echo "<td bgcolor=white>";
					if (isset($Referencia[2][$value])){
						$x=explode('^',$Referencia[2][$value]);
						$fdev=$x[9];
						echo substr($fdev,0,4)."-".substr($fdev,4,2)."-".substr($fdev,6,2);
					}else{
						if ($vencidos==0 and $total_susp==0 and $total_multa==0){
							if (isset($with_copies[$bd]) and $with_copies[$bd]=="Y")
								$copies="Y";
							else
								$copies="N";
							echo "<a href='javascript:Prestar(\"".$value."\",\"".$arrHttp["usuario"]."\",\"".$bd."\",\"".$prefix_in."\",\"(".$Pft_ni."/)\",\"$copies\")'>Prestar</a>";
                        }
					}
					echo "</td>";
					echo "</tr>";
				}
				echo "</table>";
				echo "</td>";
			}else{
				$ixt=$ixt+1;
				if ($ixt>5) continue;
				IF ($ixt==2) $rr=str_replace(",",'<br>',$rr);
				echo "<td bgcolor=white valign=top nowrap>$rr</td>";
			}
		}
		if ($ec=="S"){
			echo "<td bgcolor=white valign=top width=10 align=center>" ;
			if ($vencidos==0) $vencidos="";
			if ($total_susp==0) $total_susp="";
			if ($total_multa==0) $total_multa="";
			echo "$vencidos</td><td bgcolor=white valign=top width=10 align=center>$total_susp</td><td bgcolor=white valign=top align=center>$total_multa</td>";
			//echo "</td>";
		}
		echo "<td bgcolor=white nowrap valign=top>";
		if ($esperar_hasta!=""){
			if (date("Ymd")>$esperar_hasta ){
				 echo "<font color=red>".$msgstr["rs03"]."</font><br>";
            }
     	}

     	$rsrv_act=DeterminarReservasActivas($db_path,$v[0],$lang,$msgstr,$v[1],$fecha_reserva,$arrHttp["usuario"]);
     	echo $rsrv_act;
		echo "</td>";
		echo "<td bgcolor=white nowrap valign=top>";
		if ($vencidos=="" and $total_susp=="" and $total_multa=="")
			echo '<a href=javascript:SendMail("assigned",'.$Mfn.') alt="'.$msgstr["mail_notif_res_assign"].'" title="'.$msgstr["mail_notif_res_assign"].'"><img src=../dataentry/img/mail_p.png></a>';
		else
			echo "<img src=../dataentry/img/mail_p.png>";
		echo "&nbsp; <a href=\"javascript:PrintReserve('assigned',".$Mfn.")\"><img src=../dataentry/img/toolbarPrint.png></a>";
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

<font size=1 color=#cccccc><?php //echo "$Disp_format - $tit_format"?></font>
</body>

</html>


<form name="prestar" method="post" action="<?php
if (isset($LOAN_POLICY) AND $LOAN_POLICY=="BY_USER")
	echo "../circulation/ask_policy.php>";
else
    echo "../circulation/panel_loans.php>";
?>" >
	<input type="hidden" name="usuario">
	<input type="hidden" name="inventory">
	<input type="hidden" name="inventory_sel">
	<input type="hidden" name="Opcion" value="prestar">
	<input type="hidden" name="db_inven">
	<input type="hidden" name="reservaWeb" value="Y">
	<input type="hidden" name="loan_policy" value="<?php if (isset($LOAN_POLICY)) ECHO $LOAN_POLICY?>">
	<input type="hidden" name="copies">
</form>

<script>
function Prestar(inventario,usuario,base,prefijo,formato,copies){
	document.prestar.usuario.value=usuario
	document.prestar.inventory.value=inventario
	document.prestar.inventory_sel.value=inventario
	if (copies=="Y") base="loanobjects"
	document.prestar.db_inven.value=base+"||"+prefijo+"|ifp|"+formato
	document.prestar.copies.value=copies
	document.prestar.submit()
}
</script>