<?php
/**************** Modifications ****************

2022-03-23 rogercgui change the folder /par to the variable $actparfolder


***********************************************/



include("../common/opac-head.php");
//include("inc/leer_bases.php");


include($CentralPath."common/get_post.php");


$lang_db=$lang;

if (isset($_REQUEST["db_path"])) $db_path=$_REQUEST["db_path"];
include($CentralPath."lang/admin.php");
include($CentralPath."lang/prestamo.php");
$desde_web="Y";

function LeerCodigoUsuario(){
global $Wxis,$xWxis,$db_path,$CentralPath,$actparfolder,$lang;
	$tipo_u="";
	$Pft_tipou="'[TIPOU:]',@".$db_path."users/loans/".$lang."/loans_ustype.pft,/";
	$formato_us=$Pft_tipou."/@".$db_path."users/loans/".$lang."/loans_usdisp.pft";
   	$query = "&Expresion=CO_".$_REQUEST["usuario"]."&base=users&cipar=$db_path".$actparfolder."/users.par&Formato=".$formato_us;
   	$base="user";
  	$contenido=wxisLlamar($base,$query,$xWxis."opac/buscar.xis");
    $ec_output="";
	foreach ($contenido as $linea){
		$linea=trim($linea);
		if (substr($linea,0,8)=='[TIPOU:]'){
			$tipo_u=substr($linea,8);
			continue;
		}
		if (substr($linea,0,8)=='[TOTAL:]'){
			$total=substr($linea,8);
			if ($total==0) return $total;
		}else{

			$ec_output.= $linea."\n";
		}
	}
	return array($ec_output,$tipo_u);
}
//$sidebar="N";
$desde="ecta";
$indice_alfa="N";
?>


    <script>
function CancelReserve(Mfn){
	document.anular.Mfn.value=Mfn
	document.anular.submit()
}
function Renovar() {
	document.renovar.action="renovar_ex.php"
	marca="N"
	switch (np){     // n�mero de pr�stamos del usuario
		case 1:
			if (document.ecta.chkPr_1.checked){
				document.renovar.searchExpr.value=document.ecta.chkPr_1.id
				atraso=document.ecta.chkPr_1.value
				politica=document.ecta.politica.value
				marca="S"
			}
			break
		default:
			for (i=1;i<=np;i++){
				Ctrl=eval("document.ecta.chkPr_"+i)
				if (Ctrl.checked){
					marca="S"
					document.renovar.searchExpr.value=Ctrl.id
					atraso=Ctrl.value
					politica=document.ecta.politica[i-1].value
					break
				}
			}
	}
	fecha_d="<?php echo date("Ymd")?>"
	if (marca=="S"){
		p=politica.split('|')
		if (p[6]=="0"){     // the object does not accept renovations
			alert("<?php echo $msgstr["noitrenew"] ?>")
			return
		}
		if (atraso!=0){
			if (p[13]!="Y"){
				alert("<?php echo $msgstr["loanoverdued"]?>")
				return
			}
		}
		if (Trim(p[15])!=""){
			if (fecha_d>p[15]){
				alert("<?php echo $msgstr["limituserdate"]?>"+": "+p[15])
				return
			}
		}
		if (Trim(p[16])!=""){
			if (fecha_d>p[16]){
				alert("<?php echo $msgstr["limitobjectdate"]?>"+": "+p[16])
				return
			}
		}
		if (nMultas!=0){
			alert("**<?php echo $msgstr["norenew"]?>")
			return
		}
		document.renovar.submit()
		var division = document.getElementById("overlay");
		division.style.display="block"
		var division = document.getElementById("popup");
		division.style.display="block"
		var enlace = document.getElementById("enlace");
		enlace.href="javascript:void(0)"

	}else{
		alert("<?php echo $msgstr["markloan"]?>")
	}
}
</script>
<?php
// SIDEBAR
if ((!isset($_REQUEST["existencias"]) or $_REQUEST["existencias"] == "") and !isset($sidebar)) include($_SERVER['DOCUMENT_ROOT'] . "/".$opac_path."/components/sidebar.php");

?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
            <span data-feather="calendar" class="align-text-bottom"></span>
            This week
          </button>
        </div>
      </div>
<br>
<table width=100%>
<td>
<strong><h3><?php echo $msgstr["ecta"]?></h3></strong><p>

<form name="ecta" id="ecta">
<?php
include($CentralPath."circulation/leer_pft.php");

// se lee la configuración local
include($CentralPath."circulation/calendario_read.php");
include($CentralPath."circulation/locales_read.php");
// SE LEE LA RUTINA PARA CALCULAR EL LIMITE DE LA SUSPENSION
include($CentralPath."circulation/fecha_de_devolucion.php");
// se leen las politicas de préstamo
include($CentralPath."circulation/loanobjects_read.php");
// se lee la configuración de la base de datos de usuarios
include($CentralPath."circulation/borrowers_configure_read.php");

$user=LeerCodigoUsuario();
$tipo_u=$user[1];
$user=$user[0];

if ($user==''){
	if (isset($msgstr["iah_user_notfound"])){
		echo "<p><strong>".$msgstr["iah_usuario_notfound"]."</strong></p>";
	}else{
		echo "<p><strong>Usuario no existe</strong></p>";
    }
}

//SE LEE EL MAXIMO DE RENOVACIONES PERMITIDAS DE ACUERDO AL TIPO DE USUARIO
$fp=file($db_path."circulation/def/".$_REQUEST["lang"]."/typeofusers.tab");
foreach ($fp as $value){
	$value=trim($value);
	if ($value!=""){
		$xx_u=explode('|',$value."||");
		if ($xx_u[0]==$tipo_u){
			if (isset($xx_u[3])){
				$max_rsvr=$xx_u[3];
			} else {
				$max_rsvr=3;
			}
			break;
		}
	}
}

$ec_output="";
$arrHttp["vienede"]="orbita";
$desde_opac="Y";
$ecta_web="N";


include($CentralPath."circulation/ec_include.php");

if (substr(trim($ec_output),0,2)=="**" or trim($ec_output)==""){
	if (isset($msgstr["iah_usuario_notfound"])){
		echo "<p><strong>".$msgstr["iah_usuario_notfound"]."</strong></p>";
	}else{
	    echo "<p><strong>".$msgstr["user_notfound"]."</strong></p>";
	}
}else{
	echo $ec_output;
	if ((isset($def["WEBRENOVATION"]) and $def["WEBRENOVATION"]=="Y")){
		if (count($prestamos)>0 and !isset($_REQUEST["mostrar_reserva"])) {
			echo  "<strong><input type=button onclick=javascript:Renovar() id=renovar value=\"".$msgstr["renew"]."\"></strong><p>";
			if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ORBITA")
				if (isset($msgstr["iah_usuario_msgecta"])) echo $msgstr["iah_usuario_msgecta"];
		}
	}
	//SE LEEN LAS RESERVAS PENDIENTES

	include($CentralPath."reserve/reserves_read.php");
	$reservas_activas=0;
	$cuenta=0;
	$reserves_arr=ReservesRead("CU_".$arrHttp["usuario"],"S","","N");
	$reserves_user=$reserves_arr[0];
	echo $reserves_user;
	if ($reserves_user!=""){
		//echo "<p>".$msgstr["iah_reserve_msg"];
	}
	//if (isset($msgstr["opac_ecta"]))  echo "<br>".$msgstr["opac_ecta"]."<br>";
	echo $msgstr["rs01"].": " .$reserves_arr[1];
	if (!isset($max_rsvr) or $max_rsvr=="") $max_rsvr=3;
	echo "<br>".$msgstr["reserve_tit_4"].": " .$max_rsvr;
	$saldo_rsvr=$max_rsvr-$reserves_arr[1];
	if ($saldo_rsvr<=0){
		echo "<br><font color=red><strong>".$msgstr["no_more_reservations"]."</strong></font>";
	}
}

?>
</form>
<form name=renovar action=renovar_ex.php method=post>
<input type=hidden name=searchExpr>
<input type=hidden name=usuario value=<?php echo $arrHttp["usuario"]?>>
<input type=hidden name=vienede value=orbita>
<!--input type=hidden name=DB_PATH value=<?php echo $arrHttp["DB_PATH"]?>-->
<input type=hidden name=lang value=<?php echo $arrHttp["lang"]?>>
</form>
<form name=anular method=post action=reservar_anular.php>
<input type=hidden name=Mfn>
<input type=hidden name=usuario value=<?php echo $arrHttp["usuario"]?>>
<input type=hidden name=vienede value=orbita>
<!--input type=hidden name=DB_PATH value=<?php echo $arrHttp["DB_PATH"]?>-->
<input type=hidden name=lang value=<?php echo $arrHttp["lang"]?>>
<?php
	foreach ($arrHttp as $var=>$value){
		echo "<input type=hidden name=$var value=$value>\n";
	}
?>
</form>

</table>
<?php
//if (substr(trim($ec_output),0,2)=="**")
	echo "<input type=button name=cerrar value='".$msgstr["regresar"]."' onclick=javascript:document.regresar.submit()>";
//else
//	echo "<input type=button name=cerrar value='".$msgstr["cerrar"]."' onclick=javascript:self.close()>";
?>
</body>
</html>
<?php
if (substr(trim($ec_output),0,2)!="**"){
	echo"<script>top.resizeTo(900,600);</script>";
}
if (isset($arrHttp["error"])){
	echo "<script>alert(\"".$arrHttp["error"]."\")</script>";
}else{
	if (isset($arrHttp["resultado"])){
		$inven=explode(';',$arrHttp["resultado"]);
		$msg="";
		foreach ($inven as $inventario){
			$msg.=  $inventario.'\r'  ;
		}
		echo "<script>alert(\"".$msg."\")</script>";
	}
}
if ($ec_output!="**"){
	if ($nv>0 or $nmulta>0 or $nsusp>0){
		echo "<h4>".$msgstr["overdued"]."</h4>";
	}else{
		if ($user<>'' and $saldo_rsvr>0){
			echo "<br><br><input type=button value=\" ".$msgstr["completar_sol"]." \" onclick=javascript:CompletarSolicitud() style='height:50px;font-size:17px;border-radius:8px;background-color:#cccccc;font:black'> ";
			echo "<p>";
		}
	}
}
echo '<form name=regresar action=presentar_seleccion.php method=post>';
foreach ($_REQUEST as $key=>$value){
	echo "<input type=hidden name=$key value=\"$value\">\n";
}
echo "</form>";


//session_destroy();

?>
<script>
function CompletarSolicitud(){
	document.regresar.action="completar_solicitud.php";
	document.regresar.submit();
}
</script>
<?php
include("../common/opac-footer.php");
?>