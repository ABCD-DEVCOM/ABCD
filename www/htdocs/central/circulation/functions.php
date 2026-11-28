<?php 
/**
 * This script was created to absorb all PHP functions of the Circulation Module.
 * 
 * 20230414 rogercgui Created the script
 * 
 */


function PrestamoMismoObjeto($control_number,$user,$base_origen){
global $copies_title,$msgstr,$obj;
	$msg="";
	$tr_prestamos=LocalizarTransacciones($control_number,"ON",$base_origen);
	$items_prestados=count($tr_prestamos);
	if ($items_prestados>0){
		foreach($tr_prestamos as $value){
			if (trim($value)!=""){
				$nc_us=explode('^',$value);
		   		$pi=$nc_us[0];                                   //GET INVENTORY NUMBER OF THE LOANED OBJECT
		   		$pv=$nc_us[14];                                  //GET THE VOLUME OF THE LOANED OBJECT
		   		$pt=$nc_us[15];                                  //GET THE TOME OF THE LOANED OBJECT
				$comp=$pi." ".$pv." ".$pt;
				foreach ($copies_title as $cop){
					$c=explode('||',$cop);
					$comp_01=$c[2];
					if (isset($c[6]))
						$comp_01.=" ".$c[6];
					if (isset($c[7]))
						$comp_01.=" ".$c[7];
					if (strtoupper($nc_us[10])==strtoupper($user)){    //SE VERFICA SI LA COPIA ESTÁ EN PODER DEL USUARIO

						if (strtoupper($comp_01)==strtoupper($comp) and $obj[14]!="Y"){
							if ($msg=="")
								$msg= $msgstr["duploan"];
							else
								$msg.="<br>".$msgstr["duploan"];
						}
					}
				}
			}
	    }

	}
	return array($msg,$items_prestados);
}


function Disponibilidad($control_number,$catalog_db,$items_prestados,$prefix_cn,$copies,$pft_ni) {
global $xWxis,$Wxis,$db_path,$msgstr,$wxisUrl,$actparfolder;
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
		$pft_ni="(".$ni_pft[0]."/)";
		if (isset($ni_pft[1]) and trim($ni_pft[1])!="")
			$pft_ni.="(".$ni_pft[1]."/)";

	}
	$query = "&Opcion=disponibilidad&base=$catalog_db&cipar=$db_path".$actparfolder."$catalog_db.par&Expresion=".$Expresion."&Pft=".urlencode($pft_ni);
	include("../common/wxis_llamar.php");
	$obj=array();
	foreach ($contenido as $value){
		$value=trim($value);
		if (trim($value)!="" and substr($value,0,8)!='$$TOTAL:')
			$obj[]=$value;
	}
	$disponibilidad=count($obj)-$items_prestados;
	return $disponibilidad;
}




function LocalizarReservas($control_number,$catalog_db,$usuario,$items_prestados,$prefix_cn,$copies,$pft_ni) {
global $xWxis,$Wxis,$db_path,$msgstr,$wxisUrl,$actparfolder;

// SE DETERMINA EL NUMERO DE EJEMPLARES DISPONIBLES
	$disponibilidad=Disponibilidad($control_number,$catalog_db,$items_prestados,$prefix_cn,$copies,$pft_ni);
// SE LEE LAS RESERVAS
	$IsisScript=$xWxis."cipres_usuario.xis";
	// Mfn
	// 10:codigo de usuario
	// 30:Fecha reserva
	// 31:Hora de reserva
	// 40:Fecha límite de retiro
	// 60:Fecha de asignacion de la reserva
	// 130:Fecha de cancelación de la reserva
	// 200:Fecha en que se ejecutó la reserva y se prestó el item al usuario
	// 1: Situación de la reserva
	$Pft=urlencode("f(mfn,6,0)'|'v10'|'v30'|'v31'|'v40'|'v60'|'v130'|'v200,'|',v1/");
	$Expresion=urlencode("CN_".$catalog_db."_".$control_number." AND (ST_3 or ST_0)");
	$query="&base=reserve&cipar=$db_path".$actparfolder."reserve.par&Expresion=$Expresion&Pft=$Pft";
	include("../common/wxis_llamar.php");

	$reservas=array();
	$reservas_3=array();
	$reservas_0=array();
	foreach ($contenido as $value){
		$value=trim($value);
		if ($value!=""){
			$r=explode('|',$value);
			$Mfn=$r[0];
			$fecha_reserva=$r[2];
			$hora_reserva=$r[3];
			$fecha_cancelacion=$r[6];  //Fecha en la cual el operador canceló la reserva
			$fecha_limite=$r[4];      //Fecha hasta la cual la reserva asignada está disponible
			$fecha_asignacion=$r[5];  //Fecha en la cual se asignó la reserva
			$fecha_prestamo=$r[7];    //Fecha en la cual se prestó el objeto reservado
			$status=$r[8];
			//SE BUSCAN LAS RESERVAS ASIGNADAS
			if ($fecha_cancelacion!=""  or $fecha_prestamo!="") continue;
			if ($fecha_limite!=""){
				if ($fecha_limite<date("Ymd")) continue;
			}

			if ($status==3){
				$reservas_3[$fecha_asignacion." ".$Mfn]=$value;
			}else{
				$reservas_0[$fecha_reserva." ".$hora_reserva." ".$Mfn]=$value;
			}
			//$reservas[$fecha_reserva." ".$hora_reserva." ".$Mfn]=$value;  //Total de rservas
		}
	}
	ksort($reservas_3);
	ksort($reservas_0);
	$Cod_usuario=0;
	$value="";


//DETERMINAMOS SI EL USUARIO ESTÁ EN LA COLA DE RESERVAS EN ESPERA Y SI ESTÁ LE ASIGNAMOS EL PRESTAMO
	$ixcola_3=0;
	$ixcola_0=0;
	$ixcola=0;
	$tr=$disponibilidad - (count($reservas_3)+count($reservas_0));
	$encontrado_3="N";
	$encontrado_0="N";

 	if (count($reservas_3)>0){
 		foreach ($reservas_3 as $value){
 			if (trim($value)!=""){
 				$ixcola_3=$ixcola_3+1;
 				$v=explode('|',$value);
 				if ($usuario==$v[1]) {
 					$mfn_3=$v[0];
 					$usuario_3=$v[1];
 					$encontrado_3="S";
 					break;
 				}
 			}
 		}
 	}
//SI SE ENCONTRÓ EN LA COLA DE RESERVAS EN ESPERA
	if ($encontrado_3=="S"){
// SI ES EL PRIMERO DE LA COLA O NO ES EL PRIMERO PERO HAY SUFICIENTE EJEMPLARES PARA ATENDER LA COLA DE RESERVA
// SE LE CONCEDE EL PRESTAMO
		if ($ixcola_3==1 or ($disponibilidad-$ixcola_3)>=0){
			return array("continuar",$mfn_3,$usuario_3,$tr);
		}else{
			return array("no_continuar",0,0,$disponibilidad);
		}
	}

// VEMOS SI EL USUARIO ESTÁ EN LA COLA DE RESERVAS PENDIENTES
	foreach ($reservas_0 as $value){
		if (trim($value)!=""){
			$ixcola_0=$ixcola_0+1;
			$v=explode('|',$value);
			if ($usuario==$v[1]){
				$encontrado_0="S";
				$mfn_0=$v[0];
 				$usuario_0=$v[1];
			}
		}
	}

//SI ESTA EN LA COLA DE RESERVAS PENDIENTES Y HAY SUFICIENTES EJEMPLARES DISPONIBLES
// PARA SU LUGAR COLA DE RESERVAS SE LE DA EL PRESTAMO
	if ($encontrado_0=="S"){
		$cola=$ixcola_0+$ixcola_3;
		if ($disponibilidad>0){
			return array("continuar",$mfn_0,$usuario_0,$tr);
		}
	}

//SI NO ESTA EN LA COLA DE RESERVAS PENDIENTES Y HAY SUFICIENTES EJEMPLARES DISPONIBLES
// PARA ATENDERLA COLA DE RESERVAS SE LE DA EL PRESTAMO
	if ($encontrado_0=="N"){
		$cola=$ixcola_0+$ixcola_3;
		if ($disponibilidad-$cola>0){
			return array("continuar",0,0,$tr);
		}
	}


}

function ActualizarReserva($diap,$horap){
global $db_path,$login;
	$ValorCapturado ="d1d200d201d202<1 0>4</1>";
	$ValorCapturado.="<200 0>".$diap."</200><201 0>".$horap."</201><202 0>".$login."</202>";
	$ValorCapturado.=urlencode($ValorCapturado);
	if (file_exists($db_path."reserve/pfts/".$_SESSION['lang']."/reserve.pft")){
		$Formato=$db_path."reserve/pfts/".$_SESSION['lang']."/reserve";
	}else{
		if (file_exists($db_path."reserve/pfts/".$_SESSION['lang']."/reserve.pft")){
			$Formato=$db_path."reserve/pfts/".$_SESSION['lang']."/reserve";
		}
	}
	$Formato="&Formato_reserva=$Formato";
	$query = "&reserva=".$ValorCapturado."$Formato";

	return $query;
}


function ProcesarPrestamo($usuario,$inventario,$signatura,$item,$usrtype,$copies,$ppres,$prefix_in,$prefix_cn,$mfn_reserva,$codusuario_reserva,$tr,$user_data) {
global $db_path,$Wxis,$wxisUrl,$xWxis,$pr_loan,$pft_storobj,$recibo_arr,$recibo_list,$arrHttp,$actparfolder,$login,$politica;
	$item_data=explode('||',$item);
	$nc=$item_data[0];                  // Control number of the object
	$bib_db=$item_data[1];
	$arrHttp["db"]=$bib_db;
	$item="$pft_storobj";
	// Read the bibliographic database that contains the object using the control mumber extracted from the copy
	$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
	if ($copies=="Y"){
		$Expresion="CN_".$nc;
	}else{
		//SE LEE EL PREFIJO A UTILIZAR PARA LOCALIZAR EL OBJETO A TRAVÉS DE SU NÚMERO DE INVENTARIO
		$Expresion=$prefix_cn.$nc;
	}
    $bib_db=strtolower($bib_db);
	$query = "&Opcion=disponibilidad&base=".$bib_db."&cipar=".$db_path.$actparfolder.$bib_db.".par&Expresion=".$Expresion."&Pft=".urlencode($item);
	include("../common/wxis_llamar.php");
	$obj="";
	foreach ($contenido as $value){
		$value=trim($value);
		if (trim($value)!="")
			$obj.=$value;
	}
	$objeto=explode('$$',$obj);
	$obj=explode('|',$ppres);
	$fp=date("Ymd h:i A");
	// DEVOLUTION DATE

	if ($tr<=0){
		if (trim($obj[4])=="") $obj[4]=2 ;
		$fd=FechaDevolucion($obj[4],$obj[5],"");    //lapso reserva
	}else{
		if (isset($arrHttp["date"])){
			$fd=$arrHttp["date"].date(" h:i A");;
		}else{
			if (isset($arrHttp["lpn"])){
				$fd=FechaDevolucion($arrHttp["lpn"],$obj[5],"");
			}else{
				if ($obj[5]=="F")  // la fecha de devolución fijada en la política
					$fd=$obj[16]." 24:00";
				else
					$fd=FechaDevolucion($obj[3],$obj[5],"");    //lapso normal
	       }
	    }
	}
	$ix=strpos($fp," ");
	$diap=trim(substr($fp,0,$ix));
	$horap=trim(substr($fp,$ix));
	$ix=strpos($fd," ");

	$diad=trim(substr($fd,0,$ix));
	$horad=trim(substr($fd,$ix));
    if (isset($obj[16]) and $obj[16]!=""){
    	if ($diad>$obj[16])
    		$diad=$obj[16];
    }
	$ValorCapturado="<1 0>P</1>";
	$ValorCapturado.="<10 0>".trim($inventario)."</10>";	// INVENTORY NUMBER
	if (isset($item_data[6])) $ValorCapturado.="<12 0>".$item_data[6]."</12>";         	// VOLUME
	if (isset($item_data[7])) $ValorCapturado.="<15 0>".$item_data[7]."</15>";          // TOME
	if (isset($arrHttp["year"]))    $ValorCapturado.="<17 0>".$arrHttp["year"]."</17>";       // AÑO REVISTA
	if (isset($arrHttp["volumen"])) $ValorCapturado.="<18 0>".$arrHttp["volumen"]."</18>";    // VOLUMEN REVISTA
	if (isset($arrHttp["numero"]))  $ValorCapturado.="<19 0>".$arrHttp["numero"]."</19>";     // NUMERO REVISTA
	$ValorCapturado.="<20 0>".$usuario."</20>";
	$ValorCapturado.="<30 0>".$diap."</30>";
	//if ($obj[5]=="H")
	$ValorCapturado.="<35 0>".$horap."</35>";
	$ValorCapturado.="<40 0>".$diad."</40>";
	if ($obj[5]=="H") {
		$ValorCapturado.="<45 0>".$horad."</45>";
	}else{
		$ValorCapturado.="<45 0></45>";
	}
	$ValorCapturado.="<70 0>".$usrtype."</70>";
	if (isset($arrHttp["using_pol"])){
		$pp=explode('|',$arrHttp["using_pol"]);
		$item_data[5]=$pp[0];
	}
	$ValorCapturado.="<80 0>".$item_data[5]."</80>";

	foreach ($politica as $obj_fine){
		foreach ($obj_fine as $value){
			$o=explode('|',$value);
			if (strtoupper($o[1])==strtoupper($usrtype) and  $item_data[5]==
			strtoupper($o[0])) $ValorCapturado.="<85 0>".$value."</85>";
			//$ValorCapturado.="<85 0>".$o[7]."</85>";
		}
}

	
	$ValorCapturado.="<95 0>".$item_data[0]."</95>";                   // Control number of the object
	$ValorCapturado.="<98 0>".$item_data[1]."</98>";             			// Database name
	if ( $signatura!="") $ValorCapturado.="<90 0>".$signatura."</90>";
	$ValorCapturado.="<100 0>".$objeto[0]."</100>";
	if (isset($_SESSION["library"])) $ValorCapturado.="<150 0>".$_SESSION["library"]."</150>";
	$ValorCapturado.="<400 0>".$ppres."</400>";
	if (isset($item_data[8]))  // Información complementaria del item
		$ValorCapturado.="<410 0>".$item_data["8"]."</410>";
	if (trim($user_data)!="")
		$ValorCapturado.="<420 0>".$user_data."</420>"; //informacion complementaria del usuario
	$ValorCapturado.="<120 0>^a".$login."^b".date("Ymd h:i A")."^tP</120>";
	if (isset($arrHttp["comments"]))
		$ValorCapturado.="<300 0>".$arrHttp["comments"]."</300>";
	$ValorCapturado=urlencode($ValorCapturado);
	$IsisScript=$xWxis."crear_registro.xis";
	$Formato="";
	$recibo="";
	if (isset($recibo_list["pr_loan"])){
		if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/r_loan.pft")){
			$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/r_loan";
		}else{
			if (file_exists($db_path."trans/pfts/".$_SESSION['lang']."/r_loan.pft")){
				$Formato=$db_path."trans/pfts/".$_SESSION['lang']."/r_loan";
			}
		}
	}
	if ($Formato!="") {
		$Formato="&Formato=$Formato";
		$Pft="mfn/";
	}
	$query = "&base=trans&cipar=$db_path".$actparfolder."trans.par&login=".$login."$Formato&ValorCapturado=".$ValorCapturado;



	//Se graba el log de prestamos
	if (file_exists($db_path."logtrans/data/logtrans.mst")){

		$datos_trans["BD"]=$item_data[1];
		$datos_trans["NUM_CONTROL"]=$item_data[0];
		$datos_trans["NUM_INVENTARIO"]=trim($inventario);
		$datos_trans["TIPO_OBJETO"]=$item_data[5];
		$datos_trans["CODIGO_USUARIO"]=$usuario;
		$datos_trans["TIPO_USUARIO"]=$usrtype;
		$datos_trans["FECHA_PROGRAMADA"]=$diad;
		$ValorCapturado=GrabarLog("A",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path,"RETORNAR");
        if ($ValorCapturado!="") {
			$query.="&logtrans=".$ValorCapturado;
		}
		
	}

	if ($codusuario_reserva!="" and $codusuario_reserva==$usuario){
		$res=ActualizarReserva($diap,$horap);
	    $query.=$res."&Mfn_reserva=$mfn_reserva";
	    if (file_exists($db_path."logtrans/data/logtrans.mst")){   //LOG DE TRANSACCIONES CON RESERVA ATENDIDA
			$datos_trans["BD"]=$item_data[1];
			$datos_trans["NUM_CONTROL"]=$item_data[0];
			$datos_trans["NUM_INVENTARIO"]=trim($inventario);
			$datos_trans["TIPO_OBJETO"]=$item_data[5];
			$datos_trans["CODIGO_USUARIO"]=$usuario;
			$datos_trans["TIPO_USUARIO"]=$usrtype;
			$ValorCapturado=GrabarLog("F",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path,"",$login);
			if ($ValorCapturado!="") $query.="&logtrans_1=".$ValorCapturado;
		}

	}
	include("../common/wxis_llamar.php");
	//foreach ($contenido as $value)  echo "$value<br>"; die;

    $recibo="";
	if ($Formato!="") {
		foreach ($contenido as $r){
			$recibo.=trim($r);
		}
		$recibo_arr[]=$recibo;
		//ImprimirRecibo($recibo);
	}
	// HOOK (event): a new loan was written to the trans database
	if (function_exists('abcd_do_action')) {
		abcd_do_action('circulation_loan_created', [
			'user_code'      => $usuario,
			'user_type'      => $usrtype,
			'inventory'      => trim($inventario),
			'control_number' => $item_data[0] ?? '',
			'database'       => $item_data[1] ?? '',
			'object_type'    => $item_data[5] ?? '',
			'call_number'    => $signatura,
			'title'          => $objeto[0] ?? '',
			'loan_date'      => $diap,
			'loan_time'      => $horap,
			'due_date'       => $diad,
			'due_time'       => $horad,
			'reservation_mfn'=> ($codusuario_reserva != "" and $codusuario_reserva == $usuario) ? $mfn_reserva : '',
			'library'        => $_SESSION["library"] ?? '',
			'operator'       => $login,
		]);
	}
	$fechas=array($diad,$horad);
	return $fechas;
}



// Se localiza el número de control en la base de datos bibliográfica
function ReadCatalographicRecord($control_number,$db,$inventory="null") {
global $Expresion,$db_path,$Wxis,$xWxis,$wxisUrl,$arrHttp,$pft_totalitems,$pft_ni,$pft_nc,$pft_typeofr,$titulo,$prefix_in,$prefix_cn,$multa,$pft_storobj,$actparfolder;
	//Read the FDT of the database for extracting the prefix used for indexing the control number
    $pft_typeofr=str_replace('~',',',$pft_typeofr);
	if (isset($arrHttp["db_inven"])){
		$dbi=explode("|",$arrHttp["db_inven"]);
	}else{
		$dbi[0]="loanobjects";
	}

	if (isset($arrHttp["db_inven"]) and $dbi[0]!="loanobjects"){

		$Expresion=trim($prefix_cn).trim($control_number);
	}else{
	    $Expresion="CN_".trim($control_number);
	}
	if ($control_number=="")
		$Expresion=$prefix_in.$inventory;
//    echo $Expresion;
	// Se extraen las variables necesarias para extraer la información del título al cual pertenece el ejemplar
	// se toman de databases_configure_read.php
	// pft_totalitems= pft para extraer el número total de ejemplares del título
	// pft_in= pft para extraer el número de inventario
	// pft_nc= pft para extraer el número de clasificación
	// pft_typeofr= pft para extraer el tipo de registro

	$formato_ex="'||'".$pft_nc."'||'".$pft_typeofr."'###',";
	//se ubica el título en la base de datos de objetos de préstamo
	$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";
	$Expresion=urlencode($Expresion);
	$formato_obj=$db_path."$db/loans/".$_SESSION['lang']."/loans_display.pft";
	if (!file_exists($formato_obj)) $formato_obj=$db_path.$db. "/loans/".$_SESSION['lang']."/loans_display.pft";
	//$formato_obj.=", /".urlencode($formato_ex).urlencode($pft_storobj);
    $formato_obj.=urlencode(", /".$formato_ex.$pft_storobj);
	$query = "&Opcion=disponibilidad&base=$db&cipar=$db_path".$actparfolder."$db.par&Expresion=".$Expresion."&Pft=@$formato_obj";
	include("../common/wxis_llamar.php");
	$total=0;
	$titulo="";
	foreach ($contenido as $linea){
		$linea=trim($linea);
		if (trim($linea)!=""){
			if (substr($linea,0,8)=='$$TOTAL:')
				$total=trim(substr($linea,8));
			else
				$titulo.=$linea."\n";
		}
	}
	return $total;
}




// Se localiza el número de inventario en la base de datos de objetos  de préstamo
function LocalizarInventario($inventory) {
global $db_path,$Wxis,$xWxis,$wxisUrl,$arrHttp,$pft_totalitems,$pft_ni,$pft_nc,$pft_typeofr,$copies_title,$prefix_in,$multa,$actparfolder;

    $Expresion=$prefix_in.$inventory;
	// Se extraen las variables necesarias para extraer la información del título al cual pertenece el ejemplar
	// se toman de databases_configure_read.php
	// pft_totalitems= pft para extraer el número total de ejemplares del título
	// pft_in= pft para extraer el número de inventario
	// pft_nc= pft para extraer el número de clasificación
	// pft_typeofr= pft para extraer el tipo de registro

	//READ LOANOBJECT DATABASE TO GET THE RECORD WITH THE ITEMS OF THE TITLE
	$IsisScript=$xWxis."loans/prestamo_disponibilidad.xis";

	$Expresion=urlencode($Expresion);
	if (isset($arrHttp["db_inven"])){
		$dbi=explode('|',$arrHttp["db_inven"]);
		$dbi_base=$dbi[0];
	}
	if (isset($arrHttp["db_inven"]) and $dbi_base!="loanobjects"){
	//IF NO LOANOBJECTS READ THE PFT FOR EXTRACTING THEN INVENTORY NUMBER AND THE TYPE OF RECORD
		$d=explode('|',$arrHttp["db_inven"]);
		$arrHttp["base"]=strtolower($d[0]);
		$arrHttp["db_inven"]=strtolower($d[0]);
		$pft_typeofrec=LeerPft("loans_typeofobject.pft",$d[0]);
		$pft_typeofrec=str_replace("/"," ",$pft_typeofrec);
		$pft_typeofrec=str_replace("\n"," ",$pft_typeofrec);
		$pft_typeofrec=trim($pft_typeofrec);
		if (substr($pft_typeofrec,0,1)=="(")
			$pft_typeofrec=substr($pft_typeofrec,1);
        if (substr($pft_typeofrec,strlen($pft_typeofrec)-1,1)==")")
			$pft_typeofrec=substr($pft_typeofrec,0,strlen($pft_typeofrec)-1);
		$pft_typeofrec=trim($pft_typeofrec);
	//SE SACAN LOS FORMATOS DE LOS DIFERENTES TIPOS DE REGISTRO DE LOS CAMPOS DE INVENTARIO
		$ixpni=strpos($pft_typeofrec,'~');
		if ($ixpni>0){
			$tofr1=substr($pft_typeofrec,0,$ixpni);
			$tofr2=substr($pft_typeofrec,$ixpni+1);
		}
		$pft_inf_add=LeerPft("item_inf_add.pft",$d[0]);
		$pft_inf_add=str_replace("/"," ",$pft_inf_add);
		$pft_inf_add=str_replace("\n"," ",$pft_inf_add);
		$inf_add=explode('~',$pft_inf_add);
		$pft_nc=LeerPft("loans_cn.pft",$d[0]);
		$pft_nc=str_replace("/"," ",$pft_nc);
		$pft_item_inf_add=LeerPft("item_inf_add.pft",$d[0]);
		$pft_item_inf_add=str_replace("/"," ",$pft_item_inf_add);
		$pft_ni=LeerPft("loans_inventorynumber.pft",$d[0]);
		$pft_ni=str_replace("/"," ",$pft_ni);
		$pft_ni=str_replace("\n"," ",$pft_ni);
		$ixpni=strpos($pft_ni,'~');
		if ($ixpni>0){
			$nvi1=substr($pft_ni,0,$ixpni);
			if (isset($_SESSION["library"])) $nvi1=str_replace('#library#',$_SESSION['library'],$nvi1);
			$formato_ex="('||".$d[0]."||',$nvi1,'||||||',".$tofr1.",'||||||'";
            if (isset($inf_add[0])) $formato_ex.=$inf_add[0];
			$formato_ex.="/)";
			$nvi1=substr($pft_ni,$ixpni+1);
			if (isset($_SESSION["library"])) $nvi1=str_replace('#library#',$_SESSION['library'],$nvi1);
			$formato_ex.=",('||".$d[0]."||',$nvi1,'||||||',".$tofr2.",'||||||'";
			if (isset($inf_add[1])) $formato_ex.=$inf_add[1];
			$formato_ex.="/)";
			$formato_ex=$pft_nc.",".$formato_ex;
		}else{
			if (isset($_SESSION["library"])) $pft_ni=str_replace('#library#',$_SESSION['library'],$pft_ni);
			$formato_ex="$pft_nc,('||".$d[0]."||',$pft_ni,'||||||',".$pft_typeofrec.",'||||||'$pft_item_inf_add/)";
		}
		if (isset($_SESSION["library"])) $formato_ex=str_replace('#v5000#',"'".$_SESSION["library"]."'",$formato_ex);
	}else{
		$arrHttp["base"]="loanobjects";
		$formato_ex="(v1[1]'||'v10[1],'||',v959^i,'||',v959^l,'||',v959^b,'||',v959^o,'||',v959^v,'||',v959^t,'||'/)";
    // control number||database||inventory||main||branch||type||volume||tome
	}
	$formato_obj=urlencode($formato_ex);
	$query = "&Opcion=disponibilidad&base=".$arrHttp["base"]."&cipar=".$db_path.$actparfolder.$arrHttp["base"].".par&Expresion=".$Expresion."&Pft=".$formato_obj;
	include("../common/wxis_llamar.php");
	$total=0;
	$copies_title=array();
	$item="";
    $cno="";
    $tto="";
	foreach ($contenido as $linea){
		$linea=trim($linea);
		if ($linea!=""){
			if (substr($linea,0,8)=='$$TOTAL:'){
				$total=trim(substr($linea,8));
			}else{
				$t=explode('||',$linea);
				if ($t[0]!="" ) $cno=$t[0];
				if ($t[5]!="")  $tto=$t[5];
				if ($t[0]=="" ) $t[0]=$cno;
				if ($t[5]=="")  $t[5]=$tto;
				$linea="";
				foreach ($t as $value){
					$linea.=trim($value)."||";
				}
				if (strtoupper($inventory)==strtoupper(trim($t[2]))) $item=$linea;
				$copies_title[]=$linea;
			}
		}
	}
	$ret=array($total,$item);
	//echo"**" .$total." - ".$item;   die;
	return $ret ;
}



//se busca el numero de control en el archivo de transacciones para ver si el usuario tiene otro ejemplar prestado
function LocalizarTransacciones($control_number,$prefijo,$base_origen) {
global $db_path,$Wxis,$xWxis,$wxisUrl,$arrHttp,$msgstr,$actparfolder;
	$tr_prestamos=array();
	$formato_obj=$db_path."trans/pfts/".$_SESSION['lang']."/loans_display.pft";
	if (!file_exists($formato_obj)) $formato_obj=$db_path."trans/pfts/".$_SESSION['lang']."/loans_display.pft";
	$query = "&Expresion=".$prefijo."_P_".$control_number;
	if (isset($arrHttp["year"]) or isset($arrHttp["volumen"])){
		$query.="_";
		if (isset($arrHttp["year"])) $query.="A:".$arrHttp["year"];
		if (isset($arrHttp["volumen"])) $query.="V:".$arrHttp["volumen"];
		if (isset($arrHttp["numero"])) $query.="N:".$arrHttp["numero"];
	}
	$query.="&base=trans&cipar=$db_path".$actparfolder."trans.par&Formato=".$formato_obj;
	$IsisScript=$xWxis."cipres_usuario.xis";
	include("../common/wxis_llamar.php");
	$prestamos=array();
	foreach ($contenido as $linea){
		if (trim($linea)!=""){
			$l=explode('^',$linea);
			if (isset($l[13])){
				if ($base_origen==$l[13])
					$tr_prestamos[]=$linea;
			}else{
				$tr_prestamos[]=$linea;
			}
        }
	}
	return $tr_prestamos;
}

// Calcula os dias de vencimento

function DiasVencimiento($dev){
global $feriados,$locales,$notrabaja,$config_date_format;
	$d=0;
    $df=0;
    $diaFeriado="F";
    $dia_sem="F";
    $today=date("Ymd");
    $total_days=0;
    // se determinan los días feriados
    $ii=0;
    while ($dev<$today){

    	$ii=$ii+1;
    	if ($ii>5000) {
    		echo "check script fecha_devolucion.php";
    		die;
    	}
    	$total_days=$total_days+1;
    	$dev_date=strtotime($dev."+1 days");
    	$fdev=date("Y-m-d",$dev_date);
    	$f=explode('-',$fdev);
     	$dev=date("Ymd",$dev_date);
    	$mes=$f[1];
    	$dia=$f[2];
    	if (isset($feriados[$mes*1]) and substr($feriados[$mes*1],$dia-1,1)=="F"){
    		$diaFeriado="F";
    		$df=$df+1;
    		$dia_sem="";
    	}else{
    		$diaFeriado="";
    		// se determina cuáles dias no trabaja la biblioteca
    		$dia_sem=date("w",$dev_date);
    		if (!isset($locales[$dia_sem]["from"])) {
    			$df=$df+1;
    			$dia_sem="F";
    		}else{
    		}
    	}
    }
    $lapso=$total_days-$df;
	return $lapso;

}

// se determina si el préstamo está vencido
function compareDate ($FechaP,$lapso_p){
global $locales,$config_date_format, $actparfolder;
//Se convierte la fecha a formato ISO (yyyymmaa) utilizando el formato de fecha local
	$f_date=explode('/',$config_date_format);
	switch ($f_date[0]){
		case "DD":
		case "d":
			$dia=substr($FechaP,0,2);
			break;
		case "MM":
		case "m":
			$mes=substr($FechaP,0,2);
			break;
	}
	switch ($f_date[1]){
		case "DD":
		case "d":
			$dia=substr($FechaP,3,2);
			break;
		case "MM":
		case "m":
			$mes=substr($FechaP,3,2);
			break;
	}
	$year=substr($FechaP,6,4);
	$exp_date=$year."-".$mes."-".$dia;

	$ixTime=strpos($FechaP," ");
	if ($lapso_p=="H") {
		$exp_date.=substr($FechaP,$ixTime);
		$todays_date = date("Y-m-d h:i A");
	}else{
		$todays_date = date("Y-m-d");
	}
	$today = strtotime($todays_date);
	$expiration_date = strtotime($exp_date);
	$diff=$expiration_date-$today;
	return $diff;

}//end Compare Date

function currency_local($moeda) {
    global $locales;
    if (isset($locales['decimals'])) {
        $decimals=$locales['decimals'];
    } else {
        $decimals="";
    }

    if (isset($locales['thousand'])) {
        $thousand=$locales['thousand'];
    } else {
        $thousand="";
    }
	if (($moeda>=0) || (!empty($moeda))) {
    	return $locales['currency']." ".number_format((float)$moeda,$decimals,$thousand,".");
	} else {
		return "0";
	}
}

// Funcao que aciona a impressao de recibos
function ImprimirRecibo($recibo_arr){
	$salida="";

if (is_array($recibo_arr) || is_object($recibo_arr))
{
	foreach ($recibo_arr as $Recibo){
		$salida=$salida.$Recibo;
	}
}
	$salida=str_replace('/','\/',$salida);
?>
<script>
	msgwin=window.open("","recibo","width=400, height=300, scrollbars, resizable")
	msgwin.document.write("<?php echo $salida?>")
	msgwin.document.close()
	msgwin.focus()
	msgwin.print()
	msgwin.close()
</script>
<?php
}
?>