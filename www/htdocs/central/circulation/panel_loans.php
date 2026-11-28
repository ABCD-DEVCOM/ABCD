<?php
/**
 * @program:   ABCD - ABCD-Central - https://abcd-community.org/
 * @copyright:  Copyright (C) 2009 BIREME/PAHO/WHO - VLIR/UOS
 * @file:      usuario_prestamos_presentar.php
 * @desc:      Analyzes the user and item for establishing the loan policy
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




include("../common/get_post.php");
include("../config.php");
session_start();
//foreach ($arrHttp as $var=>$value)  echo "$var=$value<br>";
if (isset($arrHttp["error"])){
	$msg_error_0=$arrHttp["error"];
	unset($arrHttp["error"]);
}

if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
$script_php="../circulation/panel_loans.php";

$debug="";

if (isset($arrHttp["db_inven"])){
	$dbinv=explode('|',$arrHttp["db_inven"]);
	$_SESSION["loans_dbinven"]=$dbinv[0];
}

if (isset($_SESSION["login"])) {
    $login = $_SESSION["login"];
} else {
    $login = "";
}

$lang=$_SESSION['lang'];

if ($use_ldap=="1") require_once ("../common/ldap.php");

include("../lang/admin.php");
include("../lang/prestamo.php");
include("fecha_de_devolucion.php");
include ('../dataentry/leerregistroisispft.php');

require_once("../circulation/grabar_log.php");

include("leer_pft.php");

//Calendario de días feriados
include("calendario_read.php");

//Horario de la biblioteca, unidades de multa, moneda
include("locales_read.php");

// se leen las politicas de préstamo y la tabla de tipos de usuario
include("loanobjects_read.php");

// se lee la configuración de la base de datos de usuarios
include("borrowers_configure_read.php");

// Se lee el prefijo y el formato para extraer el código de usuario
$us_tab=LeerPft("loans_uskey.tab","users");
$t=explode("\n",$us_tab);
$uskey=$t[0];



include("../reserve/reserves_read.php");
if (isset($arrHttp["reserve"])){
	include("../reserve/seleccionar_bd.php");
}

$valortag = Array();

$ec_output="" ;
$recibo_arr=array();

//Se averiguan los recibos que hay que imprimir
$recibo_list=array();
$Formato="";

if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/receipts.lst")){
		$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/receipts.lst";
	}else{
		if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/receipts.lst")){
			$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/receipts.lst";
		}
	}
if ($Formato!=""){
	$fp=file($Formato);
	foreach ($fp as $value){
		if (trim($value)!=""){
			$value=trim($value);
			$recibo_list[$value]=$value;
		}
	}
}

include("functions.php");


// ------------------------------------------------------
// INICIO DEL PROCESO
//--------------------------------------------------------------
	include("../common/header.php");
 	include("../common/institutional_info.php");
 	include("../circulation/scripts_circulation.php");
?>

<div class="sectionInfo">
	<div class="breadcrumb">
		<?php echo $msgstr["statment"]?>
	</div>
	<div class="actions"> </div>
	<?php include("submenu_prestamo.php");?>
</div>

<?php 
	$ayuda="circulation/loan.html";
	include "../common/inc_div-helper.php";
?>

<div class="middle form row m-0">
	<div class="formContent col-2 m-2">
		<?php include("inc_form_loan.php");?>
	</div>
	<div class="formContent col-9 m-2">

<?php
// ARE THE COPIES IN THE COPIES DATABASE OR IN THE BIBLIOGRAPHIC DATABASE?

if (isset($arrHttp["db_inven"])){
	if (isset($arrHttp["copies"]))
		$from_copies=$arrHttp["copies"];
	else
		$from_copies="N";
	$dbi=explode('|',$arrHttp["db_inven"]);
	if ($dbi[0]!="loanobjects"){

		$x=explode('|',$arrHttp["db_inven"]);
    	$var=LeerPft("loans_conf.tab",$x[0]);
		$prefix_in=trim($x[2]);
	}else{
		$prefix_in="IN_";
	}
}else{
	$prefix_in="IN_";
	$from_copies="Y";
}
if (isset($arrHttp["Opcion"])){
	if ( $arrHttp["Opcion"]=="reservar")
		$msg_1=$msgstr["reserve"];
	else
		if ($arrHttp["Opcion"]=="prestar") $msg_1=$msgstr["loan"];
}



$link_u="";
if (isset($arrHttp["usuario"])) $link_u="&usuario=".$arrHttp["usuario"];
if (isset($arrHttp["inventory"])) $presentar_reservas="N";
$nmulta=0;
$nsusp=0;
$prestamos=array();
$cont="";
$np=0;
$nv=0;

include("ec_include.php");  //se incluye el procedimiento para leer el usuario y los préstamos pendientes

//echo '<script> politica="'.$politica_raw.'"</script>';


if ($nsusp!=0 or $nmulta!=0) {
	$cont="N";
	unset($arrHttp["inventory"]);
}
if (count($prestamos)>0) {
	$ec_output.= "<strong><a class='bt bt-green' href=javascript:DevolverRenovar('D',politica)>".$msgstr["return"]."</a>
	<a class='bt bt-blue' href=javascript:DevolverRenovar('R',politica)>".$msgstr["renew"]."</a>";
	if (isset($ASK_LPN) AND $ASK_LPN=="Y"){
		$ec_output.=" ".$msgstr["days"]."<input type=text name=lpn size=4>";
	}
	$ec_output.= "</strong><p>";
}

if (isset($arrHttp["usuario"])){
	//Se obtiene el código, tipo y vigencia del usuario
	$formato=$pft_uskey.'\'$$\''.$pft_ustype.'\'$$\''.$pft_usvig.'\'$$\''.$pft_usmore;
	$formato=urlencode($formato);
	$query = "&Expresion=".trim($uskey).$arrHttp["usuario"]."&base=users&cipar=".$db_path.$actparfolder."users.par&Pft=".$formato;
	$contenido="";
	$IsisScript=$xWxis."cipres_usuario.xis";
	include("../common/wxis_llamar.php");
	$user="";
	$msgsusp="";
	$vig="";
	foreach ($contenido as $linea){
		$linea=trim($linea);
		if ($linea!="")  $user.=$linea;
	}
}else{
	$user="";
}

if (trim($user)==""){
	ProduceOutput("<h4>".$msgstr["userne"]."</h4>","");
	die;
}else{

	 if(isset($use_ldap) and $use_ldap){
	  if(!Exist($arrHttp["usuario"]) ) {
		  ProduceOutput("<h4>".$msgstr["ldapExi"]."</h4>","");
		  die;
       }
     }

    $arrHttp["ecta"]="S";
    if (isset($arrHttp["ecta"])){
    	if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){      //para ver si tiene activado el módulo de reservas. Se lee desde el abcd.def
			$reserves_arr=ReservesRead("CU_".$arrHttp["usuario"]);
			$reserves_user=$reserves_arr[0];
		}else{
			$reserves_user="";
		}
	}else
		$reserves_user="";
	if ($nsusp>0 or $nmulta>0) {
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
$ec_output.= "\n
<script>
  Vigencia='$vig'
  np=$np
  nv=$nv
</script>\n";
if ($msgsusp!=""){
	$ec_output.="<font color=red><h3>**".$msgstr[$msgsusp]."</h3></font>";
	if ($reserves_user!="")
		$ec_output.="<p><strong>".$msgstr["reserves"]."</strong><br>".$reserves_user."<p>";

	ProduceOutput($ec_output,"");
	die;
}
//OJO AGREGARLE AL TIPO DE USUARIO SI SE LE PUEDEN PRESTAR CUANDO ESTÁ VENCIDO
//if ($nv>0 and isset($arrHttp["inventory"])){
//	$ec_output.= "<font color=red><h3>".$msgstr["useroverdued"]."</h3></font>";
//	ProduceOutput($ec_output,"");
//	die;
//}
// Si viene desde la opción de prestar, se localiza el número de inventario solicitado

$xnum_p=$np;
$prestamos_este=0;
if (isset($arrHttp["inventory"]) and $vig=="" and !isset($arrHttp["prestado"]) and !isset($arrHttp["renovado"]) and !isset($arrHttp["devuelto"])){

	$ec_output.="<table class='table striped w-10'>";
	$ec_output.="<tr><td></td>
		<td width=50 align=center><strong>".$msgstr["inventory"]."</strong></td><td width=50 align=center><strong>".$msgstr["control_n"]."<strong></td><td align=center><strong>".$msgstr["reference"]."<strong></td><td align=center><strong>".$msgstr["typeofitems"]."</strong></td><td align=center><strong>".$msgstr["devdate"]."</td></tr>\n";

    $invent=explode("\n",trim(urldecode($arrHttp["inventory"])));
    $primera_vez="";
    foreach ($invent as $arrHttp["inventory"]){
    	$arrHttp["inventory"]=trim($arrHttp["inventory"]);
    	if ($arrHttp["inventory"]=="") continue;
    	$cont="Y";
    	/*if (isset($inventory_numeric) and $inventory_numeric =="Y"){
    		$i=0;
    		while (substr($arrHttp["inventory"],$i,1)=="0"){
    			$i++;
    			$arrHttp["inventory"]=substr($arrHttp["inventory"],$i,1);
    		}
    	}
    	$arrHttp["inventory"]=trim($arrHttp["inventory"]);
    	*/
    	if (isset($_SESSION["library"])) $arrHttp["inventory"]=$_SESSION["library"]."_".$arrHttp["inventory"];
    	$ec_output.="<tr>";
    	$este_prestamo="";
        $este_prestamo.= "<td  valign=top align=center><font color=red>".$arrHttp["inventory"]."</font></td>";
	//Se ubica el ejemplar en la base de datos de objeto
		$res=LocalizarInventario($arrHttp["inventory"]);
		$total=$res[0];
		$item=$res[1];
		if ($total==0){
			$este_prestamo.= "<td  valign=top></td><td></td><td   valign=top></td><td  valign=top></td><td   valign=top><font color=red>".$msgstr["copynoexists"]."</font></td>";
			$cont="N";
			$ec_output.="<td></td>".$este_prestamo;
 			//ProduceOutput($ec_output,"");
		}else{
		//se extrae la información del número de control del título y la base de datos catalográfica a la cual pertenece
			$tt=explode('||',$item);
			$control_number=$tt[0];

			$catalog_db=strtolower($tt[1]);
    		$tipo_obj=trim($tt[5]);      //Tipo de objeto

// se lee la configuración de la base de datos de objetos de préstamos
			$arrHttp["db"]="$catalog_db";
			$este_prestamo.="<td  valign=top align=center>$control_number  ($catalog_db)</td>";
            require_once("databases_configure_read.php");
			$ppres="";
    		$tipo_obj=trim(strtoupper($tipo_obj));
    		$tipo_us=$userdata[1];
    		$userdata[1]=trim(strtoupper($userdata[1]));
            if (isset($arrHttp["using_pol"])){
            	$ppres=$arrHttp["using_pol"];
            	$o=explode("|",$ppres);
            	$using_pol=$o[0]." - ".$o[1];
            	$tipo_obj=$o[0];
            }
			if (isset($politica[$tipo_obj][$userdata[1]])){
	    		$ppres=$politica[$tipo_obj][$userdata[1]];
	    		$using_pol=$tipo_obj." - " .$userdata[1];
			}
			if (trim($ppres)==""){
				if (isset($politica[0][$userdata[1]])) {
					$ppres=$politica[0][$userdata[1]];
					$using_pol="0 - " .$userdata[1];
				}
			}
			if (trim($ppres)==""){
				if (isset($politica[$tipo_obj][0])){
	    			$ppres=$politica[$tipo_obj][0];
	    			$using_pol=$tipo_obj." - 0" ;
	  			}
			}
			if (trim($ppres)==""){
				if (isset($politica["0"]["0"])){
					$ppres=$politica["0"]["0"];
					$using_pol="0 - 0";
				}
			}
			$obj=explode('|',$ppres);
			if (isset($obj[11]))
				$allow_reservation="Y";
			else
				$allow_reservation="N";
			$fechal_usuario="";
			$fechal_objeto="";
			if (!isset($obj[2]))
			    $total_prestamos_politica=0;
			else
				$total_prestamos_politica=$obj[2];
			if (trim($total_prestamos_politica)=="") $total_prestamos_politica=99999;
			if (isset($obj[15])){
				$fechal_usuario=$obj[15];
				$fecha_d=date("Ymd");
				if (trim($fechal_usuario)!=""){
					if ($fecha_d>$fechal_usuario){
						$este_prestamo.= "fecha límite del usuario ";
						$norenovar="S";
						$cont="N";
						//die;
					}
				}
			}
/*
			if (isset($obj[15])){
				$fechal_objeto=$obj[16];
				if (trim($fechal_objeto)!="" and $obj[5]!="F"){
					if ($fecha_d>$fechal_objeto){
						$este_prestamo.= "fecha límite del objeto ";
						$cont="N";
						$este_prestamo.="<hr>";
					}
				}
			}
*/
			//SE VERIFICA SI EL USUARIO TIENE PRÉSTAMOS VENCIDOS
            if ($nv>0 and isset($arrHttp["inventory"]) and $obj[12]!="Y" and $obj[13]!="Y"){
				$este_prestamo.= "<font color=red><h3>".$msgstr["useroverdued"]."</h3></font>";
				$cont="N";
			}
			//Se verifica si el usuario puede recibir más préstamos en total
			//SE ASIGNA EL TOTAL DE PRESTAMOS QUE PUEDE RECIBIR UN USUARIO  SEGUN EL TIPO DE OBJETO  (calculado en loanobjects_read.php)
			if (isset($tipo_u[$userdata[1]]))
				$tprestamos_p=$tipo_u[$userdata[1]];
			else
				$tprestamos_p=99999;
			if (trim($tprestamos_p)=="")    $tprestamos_p=99999;
			if ($cont=="Y"){
		// Se localiza el registro catalográfico utilizando los datos anteriores
				$ref_cat=ReadCatalographicRecord($control_number,$catalog_db,$arrHttp["inventory"]);

	 			if ($ref_cat==0){      //The catalographic record is not found
	 				$este_prestamo.= "<td   valign=top></td><td   valign=top></td><td   valign=top><font color=red>".$msgstr["catalognotfound"]." ($catalog_db)</font></td>";
					$cont="N";
	 			}
	 			if ($ref_cat>1){      //More than one catalographic record

	 				$este_prestamo.= "<td   valign=top></td><td   valign=top></td><td   valign=top><font color=red>".$msgstr["dupctrl"]." ($catalog_db)</font></td>";
					$cont="N";
	 			}
	 			if ($cont=="Y"){
		 			$tt=explode('###',trim($titulo));
		    		$obj_store=$tt[1];
					$tt=explode('||',$tt[0]);
					$titulo=$tt[0];
					if (isset($arrHttp["comments"]))
		    			$titulo.=" <font color=darkred>".$arrHttp["comments"]."</font>";
					$signatura=$tt[1];     //signatura topográfica
		    		$este_prestamo.= "<td  valign=top>$titulo</td>";
		    		$este_prestamo.= "<td  valign=top>";
		    		if (trim($ppres)==""){
						//$debug="Y";
						$este_prestamo.=$msgstr["nopolicy"]." ".$tipo_obj."-".$userdata[1]."<td></td>";
                        $grabar="N";
					}else{
						$este_prestamo.= $msgstr["policy"].": ". $using_pol;
						$grabar="Y";
					}
					$este_prestamo.="</td>";

	// se verifica si el ejemplar está prestado
					$tr_prestamos=LocalizarTransacciones($arrHttp["inventory"],"TR",$catalog_db);
					$Opcion="";
					$msg="";
					$msg_1="";
					if (isset($obj[3])) {
						$lapso=$obj[3];
					} else {
						$lapso="";
					}					
					if (trim($lapso)=="0"){
						$msg=$msgstr["not_avail_loan"];
						$msg.= "<font color=red>".$msgstr["not_avail_loan"]."</font><br>";
						$cont="N";
					}else{
						if (count($tr_prestamos)>0){   // Si ya existe una transacción de préstamo para ese número de inventario, el ejemplar está prestado
							$cont="N";
							$msg.="<font color=red>".$msgstr["itemloaned"]."<br></font>";
	        			}
	        		}
					//SE VERIFICA SI EL USUARIO YA TIENE UN MISMO EJEMPLAR, VOLUMEN Y TOMO DE ESE TÍTULO Y SI SE LE PERMITE O NO
					$var=PrestamoMismoObjeto($control_number,$arrHttp["usuario"],$catalog_db);
					$msg_1=$var[0];
					$items_prestados=$var[1];
					if ($msg_1!=""){
	        			$cont="N";
	        			$msg.="<font color=red>".$msg_1."</font>";
	        		}
	        		if ($msg!="")
	        			$este_prestamo.="<td  valign=top>".$msg."</td>";
	        		if ($cont=="Y"){
	        			$msg="";
	        			$ec_output.="<td  valign=top>";
	        			if ($grabar=="Y"){
	        				$tr=0;
	        				//SE LOCALIZA SI EL TITULO ESTÁ RESERVADO
	        				if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){         //para ver si tiene activado el módulo de reservas se lee desde el abcd.def
	        					$reservado=LocalizarReservas($control_number,$catalog_db,$arrHttp["usuario"],$items_prestados,$prefix_cn,$from_copies,$pft_ni);
	        					//echo "<pre>";print_r($reservado); die;
	        					if (isset($reservado[1])) {
	        						$mfn_reserva=$reservado[1];
	        					} else {
	        						$mfn_reserva="";
	        					}

	        					if (isset($reservado[3])){ 
									$tr=$reservado[3];
								} else {
									$tr="";
								}

	        					if(isset($reservado[2]))
	        						$codusuario_reserva=$reservado[2];
	        					else
	        						$codusuario_reserva="";
	        				}else{
	        					$reservado=array("continuar",0);
	        					$mfn_reserva=0;
	        					$codusuario_reserva="";
	        					$tr=1;
	        				}
	        				if (!isset($total_politica[$tipo_obj])) $total_politica[$tipo_obj]=0;

	        				if (isset($reservado[0])) {
	        					$reservado_0=$reservado[0];
	        				} else {
	        					$reservado_0="";
	        				}
	        				if ($reservado_0=="continuar"){
	        					//echo  "<p>np:".$np. " total_prestamos_usuario: $tprestamos_p total_prestamos_politica: ". $total_prestamos_politica ."  total_politica[$tipo_obj]: ". $total_politica[$tipo_obj]."<br>";
	        					if ($np<$tprestamos_p and $total_politica[$tipo_obj]< $total_prestamos_politica ){
	        						$total_politica[$tipo_obj]=$total_politica[$tipo_obj]+1;
	        						$np=$np+1;
	        						$xnum_p=$xnum_p+1;
	        						$prestamos_este=$prestamos_este+1;
									$ec_output.="$xnum_p. <input type=checkbox name=chkPr_".$xnum_p." value=0  id='".$arrHttp["inventory"]."'>";
	  								$ec_output.= "<input type=hidden name=politica value=\"".$ppres."\"> \n";
									
	  							}else{
	  								$grabar="N";
	  								$msg="<font color=red>".$msgstr["nomoreloans"]."</font>";
	  							}
	  						}else{
	  							if ($allow_reservation=="Y"){
	  								$grabar="N";
	  								$msg="<font color=red><a href='javascript:ShowReservations(\"CN_".$catalog_db."_"."$control_number\",\"$catalog_db\")'>".$msgstr["reserved_other_user"]."</a></font>";
	  								//echo $msg;
	  							}
	  						}

  						}
						
						$este_prestamo.="</td>";
						$ec_output.=$este_prestamo;
						$Opcion="prestar";
						$msg_1=$msgstr["loan"];
						if ($grabar=="Y"){
							if (isset($userdata[3])) {
								$us_more=$userdata[3];
							}else{
								$us_more="";
							}
							$devolucion=ProcesarPrestamo($arrHttp["usuario"],$arrHttp["inventory"],$signatura,$item,$tipo_us,$from_copies,$ppres,$prefix_in,$prefix_cn,$mfn_reserva,$codusuario_reserva,$tr,$us_more);

							if ($mfn_reserva!=0){
                                if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){

									$reserves_arr=ReservesRead("CU_".$arrHttp["usuario"]);
									$reserves_user=$reserves_arr[0];
								}else{
									$reserves_user="";
								}
							}
						}else{
							$devolucion=array();
						}
						$ec_output.="<td valign=top >$msg";

						if (count($devolucion)>0) {
							if (($config_date_format=="DD/MM/YY") or ($config_date_format=="d/m/Y")) {
								$ec_output.=substr($devolucion[0],6,2)."/".substr($devolucion[0],4,2)."/".substr($devolucion[0],0,4);
							}else{
								$ec_output.=substr($devolucion[0],4,2)."/".substr($devolucion[0],6,2)."/".substr($devolucion[0],0,4);
							}
							$ec_output.=" ".$devolucion[1];
							if ($codusuario_reserva!="" and $codusuario_reserva==$arrHttp["usuario"]) $ec_output.=" <font color=red><br>".$msgstr["rs04"]." <a href=\"javascript:EnviarCorreo('".$arrHttp["usuario"]."','"."'".$arrHttp["inventory"]."')\"><img src=../dataentry/img/toolbarMail.png></a> </font>";
						}
						$ec_output.="</td><td  valign=top ></td> ";
	           		}else{
	           			$ec_output.="<td></td>".$este_prestamo;
	           		}
				} else{
					$ec_output.="<td></td>".$este_prestamo;
				}
			}else{
				$ec_output.="<td></td>".$este_prestamo;
			}
		}
	}
	$ec_output.="</table>";


}

if ($prestamos_este>0) $ec_output.= "<strong><a class='bt bt-green' href=javascript:DevolverRenovar('D')>".$msgstr["return"]."</a></strong>\n";
if ($reserves_user!="")
	$ec_output.="<p><strong>".$msgstr["reserves"]." <font color=red>(user)</font></strong><br>".$reserves_user;

?>


<?php ProduceOutput($ec_output,""); ?>

<?php function ProduceOutput($ec_output,$reservas){
global $msgstr,$msg_error_0,$arrHttp,$signatura,$msg_1,$cont,$institution_name,$copies_title,$link_u,$recibo_arr,$db_path,$Wxis,$xWxis,$wxisUrl,$script_php,$prestamos_este,$xnum_p,$reserve_active,$nmulta,$nsusp,$cisis_ver,$css_name,$logo,$ILL,$meta_encoding, $actparfolder,$recibo;

 
	if ($recibo!=""){

 		$recibo="&recibo=$recibo";
 		$link_u.=$recibo;
 	}
?>


<form name="ecta">
<?php
	if ($xnum_p=="") $xnum_p=0;
	$ec_output.= "</form>";
	$ec_output.="<script>
		np=$xnum_p
		</script>\n";


	$ec_output.= "<form name='devolver' action='devolver_ex.php' method='post'>
	<input type='hidden' name='searchExpr'>";
	
	if (isset($arrHttp["usuario"])) $ec_output.= "<input type='hidden' name='usuario' value='".$arrHttp["usuario"]."'>";
	
	$ec_output.= "<input type='hidden' name='vienede' value='ecta'>
	<input type='hidden' name='lang' value='".$_SESSION['lang']."'>	
	<input type='hidden' name='lpn'>\n";
	if (isset($arrHttp["reserve"])) $ec_output.= "<input type=hidden name=reserve value=".$arrHttp["reserve"].">\n";
	$ec_output.= "</form>";


	$ec_output.= "<form name='solvencia' action='solvencia.php' method='post' target='solvencia'>
	<input type='hidden' name='lang' value='".$_SESSION['lang']."'>";
	if (isset($arrHttp["usuario"])) $ec_output.= "<input type='hidden' name='usuario' value='".$arrHttp["usuario"]."'>";
	$ec_output.= "</form>";

	$ec_output.= "<form name=multas action=multas_eliminar_ex.php method=post>
	<input type='hidden' name='Accion'>
	<input type='hidden' name='lang' value='".$_SESSION['lang']."'>";
	if (isset($arrHttp["usuario"])) $ec_output.= "<input type='hidden' name='usuario' value='".$arrHttp["usuario"]."'>";
	$ec_output.= "<input type='hidden' name='Tipo'>
	<input type='hidden' name='Mfn' value=\"\">";
	if (isset($arrHttp["reserve"])) $ec_output.= "<input type=hidden name=reserve value=".$arrHttp["reserve"].">\n";
	$ec_output.= "</form>";

	echo $ec_output;
	
	if ($reservas !=""){
		echo "<p><font color=red><strong>".$msgstr["total_copies"].": ".count($copies_title).". ".$msgstr["item_reserved"]."</strong></font><br>";
		echo $reservas ;
	}

	if (isset($arrHttp["prestado"]) and $arrHttp["prestado"]=="S"){
		if (isset($arrHttp["resultado"])){
			$inven=explode(';',$arrHttp["resultado"]);
			foreach ($inven as $inventario){
				echo "<p><font color=red>". $inventario." ".$msgstr["item"].": ".$msgstr["loaned"]." </font>";
				if (isset($arrHttp["policy"])){
					$p=explode('|',$arrHttp["policy"]);
					echo $msgstr["policy"].": " . $p[0] ." - ". $p[1];
				}
			}
		}
	}
	if (isset($arrHttp["devuelto"]) and $arrHttp["devuelto"]=="S"){
		echo "<hr><h3>".$msgstr['returned']."</h3>";
		echo "<table class='table striped w-10'>";
		echo "<tr><th>".$msgstr["item"]."</th><th>#</th><th>".$msgstr["returned"]."</th></tr>";
		if (isset($arrHttp["resultado"]) and isset($arrHttp["rec_dev"])){
			$inven=explode(';',$arrHttp["rec_dev"]);
			foreach ($inven as $inventario){
				if (trim($inventario)!=""){
					$Mfn=trim($inventario);
					echo "<tr><td>". $inventario."</td>";
					$Formato=" '<td>'v10,'</td><td>',mdl,v100'</td></tr>'";
					$Formato="&Pft=$Formato";
					$IsisScript=$xWxis."leer_mfnrange.xis";
					$query = "&base=trans&cipar=$db_path".$actparfolder."trans.par&from=$Mfn&to=$Mfn$Formato";
					include("../common/wxis_llamar.php");
					foreach ($contenido as $value){
						echo $value;
					}
				}
			}
		}
		echo "</table>";
	}

//SE VERIFICA SI ALGUNO DE LOS EJEMPLARES DEVUELTOS ESTÁ RESERVADO
	if (isset($arrHttp["lista_control"])) {
		$rn=explode(";",$arrHttp["lista_control"]);
		$res=array();
		foreach ($rn as $value){
			if (trim($value)!=""){
				if (!isset($res[$value]))
					$res[$value]=1;
				else
					$res[$value]=$res[$value]+1;
			}
		}

		if (count($res)>0){
			$Expresion="";
			foreach ($res as $key=> $value){
				if ($Expresion==""){
					$Expresion=$key;
				}else{
					$Expresion.="+".$key;
				}
			}
			if (!isset($reserve_active) or isset($reserve_active) and $reserve_active=="Y"){
				$reserves_arr= ReservesRead($Expresion);
				$reserves_title=$reserves_arr[0];
				if ($reserves_title!=""){
					echo "<!--p><hr><strong>".$msgstr["reserves"]." <font color=red>(title)</font></strong><br-->";
					echo $reserves_title."<p>";
				}
			}
		}
	}

	if (isset($arrHttp["renovado"]) and $arrHttp["renovado"]=="S"){
		if (isset($arrHttp["resultado"])){
			$inven=explode(';',$arrHttp["resultado"]);
			foreach ($inven as $inventario){
				if (trim($inventario)!="")
					echo "<p><font color=red>".$msgstr["item"]." ". $inventario." </font>";
			}
		}
	}else{

	}

//SE IMPRIMEN LOS RECIBOS DE PRÉSTAMOS
/*	if (count($recibo_arr)>0) {
		ImprimirRecibo($recibo_arr);
	}*/

//SE IMPRIMEN LOS RECIBOS DE DEVOLUCION
	if (isset($arrHttp["rec_dev"])){
		$Mfn_rec=$arrHttp["rec_dev"];
		$fs="r_return";
		$r=explode(";",$Mfn_rec);
		$rec_salida=array();

		foreach ($r as $Mfn){
			if ($Mfn!=""){
				$Formato="";
				if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/$fs.pft")){
					$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/$fs";
				}else{
					if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/$fs.pft")){
						$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/$fs";
					}
				}
				if ($Formato!="") {
                	$Formato="&Formato=$Formato";
					$IsisScript=$xWxis."leer_mfnrange.xis";
					$query = "&base=trans&cipar=$db_path".$actparfolder."trans.par&from=$Mfn&to=$Mfn$Formato";
					include("../common/wxis_llamar.php");
					$recibo="";
					foreach ($contenido as $value){
						$recibo.=trim($value);
					}
					$rec_salida[]=$recibo;
				}
			}
		}
		if (count($rec_salida)>0) {
			ImprimirRecibo($rec_salida);
		}
	}


	
	if ($xnum_p==0 and $nmulta==0 and $nsusp==0){
		if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/r_solvency.pft"))
			echo "<a class='bt bt-blue' href=javascript:ImprimirSolvencia('".$arrHttp["usuario"]."')><i class=\"fas fa-print\"></i> ".$msgstr["solvencia"]."</a>";
	}

	// HOOK (UI): plugins can append widgets to the borrower's loan panel
	if (function_exists('abcd_run_hook')) {
		echo abcd_run_hook('circulation_user_panel', '', [
			'user_code'   => $arrHttp["usuario"],
			'loans'       => $xnum_p,
			'fines'       => $nmulta,
			'suspensions' => $nsusp,
		]);
	}
?>

<?php
if (isset($arrHttp["reservaWeb"]) and $arrHttp["reservaWeb"]=="xY"){
?>	
	<form method="post" action="../output_circulation/rsweb.php">
	<input type="hidden" name="base" value="reserve">
	<input type="hidden" name="code" value="actives_web">
    <input type="hidden" name="name" value="rsweb">
    <input type="hidden" name="retorno" value="../circulation/estado_de_cuenta.php">
    <input type="hidden" name="reserva" value="S">
    <input type="hidden" name="reservaWeb" value="Y">
    <input type="submit" name=rsv_p value="Reservas web" >
     </form>
<?php
}
?>
	</div>
</div>

<?php include("../common/footer.php");?>


<?php
	if (isset($msg_error_0)){
		echo "<script>
		alert('".$msg_error_0."')
		</script>
		";
		unset($arrHttp["error"]);
		unset($msg_error_0);
	}
}  //END FUNCTION PRODUCEOUTPUT


if (!empty($recibo_arr)) ImprimirRecibo($recibo_arr);

?>

<?php include("forms.php");?>




