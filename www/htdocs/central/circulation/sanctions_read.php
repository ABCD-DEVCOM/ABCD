<?php
/**
 * 2022-07-14 rogercgui add $actparfolder variable; 
 * 
 * 
 * 
 **/	

// se determina si la suspensi�n est� vencida

function PrepararFechaSanciones($FechaP){
global $locales,$config_date_format;
//Se convierte la fecha al formato de fecha local
	$df=explode('/',$config_date_format);
	switch ($df[0]){
		case "d":
			$dia=substr($FechaP,6,2);
			break;
		case "m":
			$mes=substr($FechaP,6,2);
			break;
	}
	switch ($df[1]){
		case "d":
			$dia=substr($FechaP,4,2);
			break;
		case "m":
			$mes=substr($FechaP,4,2);
			break;
	}
	$year=substr($FechaP,0,4);
	return $dia."-".$mes."-".$year;
}

function CalculaVencimiento ($FechaP){
global $locales,$arrHttp,$CentralPath;
//Se convierte la fecha a formato ISO (yyyymmaa) utilizando el formato de fecha local
	$dia=substr($FechaP,6,2);
	$mes=substr($FechaP,4,2);
	$year=substr($FechaP,0,4);
	$exp_date=$year."-".$mes."-".$dia;
	$todays_date = date("Y-m-d");
	$today = strtotime($todays_date);
	$expiration_date = strtotime($exp_date);
	$diff=$expiration_date-$today;
	return $diff;

}//end Compare Date

// se leen las suspensiones
	global $locales;
	$sanctions_output="" ;
	$formato_obj="v1'|',v10'|',v20'|',v30'|',v40'|',v50' ".$locales['currency']."|',v60'|',mhl,v100'|',f(mfn,1,0),'|',v110,'|'v120/";
	if (isset($Expr_b)){
		$Expresion=$Expr_b;
	}else{
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){
			$Expresion="(TR_S_".$arrHttp["usuario"]." or "."TR_M_".$arrHttp["usuario"].") and ST_0";
		}else{
			$Expresion="(TR_S_".$arrHttp["usuario"]." or "."TR_M_".$arrHttp["usuario"]." or "."TR_N_".$arrHttp["usuario"].") and ST_0";
		}
   	}

   	$query = "&Expresion=$Expresion"."&base=suspml&cipar=$db_path".$actparfolder."suspml.par&Pft=".$formato_obj;
	$IsisScript=$xWxis."cipres_usuario.xis";
	if (isset($CentralPath) and $CentralPath!="")
		include($CentralPath."common/wxis_llamar.php");
	else
		include("../common/wxis_llamar.php");
	$susp=array();
	$susp_historico=array();
	$multa=array();
	$nota=array();
	$nsusp=0;
	foreach ($contenido as $linea){
		$p=explode('|',$linea);
		switch($p[0]){
			case "S":
				if (isset($p[6])){
					if ($p[1]==0){
						$dif= CalculaVencimiento ($p[6]);   // se verifica si la suspensi�n est� vigente
						if ($dif>=0){
							$susp[]=$linea;
							$nsusp=$nsusp+1;
						}else{
							if (isset($Expr_b)){
								$susp_historico[]=$linea;
							}
						}
					}
				}
				break;
			case "M":
				if (trim($linea)!=""){
					$multa[]=$linea;
				}
				break;
			case "N":
				if (trim($linea)!=""){
					$nota[]=$linea;
				}
				break;
		}
	}
	if (count($susp)+count($susp_historico)>0) {
		$susp_total=array_merge($susp_historico, $susp);
		$sanctions_output.= "<br><strong>".$msgstr["suspensions"]."</strong>
		<table width=95% bgcolor=#cccccc> ";
		if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELSUS"]))
		if (!isset($Expr_b)) $sanctions_output.="<th></th>";
		$sanctions_output.= "<th>".$msgstr["date"]."</th><th>".$msgstr["cause"]."</th><th>".$msgstr["expire"]."</th><th>".$msgstr["comments"]."</th>";
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){

		}else{
			$sanctions_output.="<th>".$msgstr["operator_date_created"]."</th>";
			if (isset($Expr_b))
				$sanctions_output.= "<th>Situaci�n</th><th>Cancelado/Pagado</th>";
		}
		//$sanctions_output.=
		foreach ($susp_total as $linea) {
			$p=explode("|",$linea);
			$fecha1=PrepararFechaSanciones($p[3]);
			$fecha2=PrepararFechaSanciones($p[6]);
			$sanctions_output.=  "<tr>";
			if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELSUS"])){
				if (!isset($Expr_b)) $sanctions_output.= "<td bgcolor=white><input type=checkbox name=susp value=".$p[8]."></td>";
			}
			$sanctions_output.= "<td bgcolor=white nowrap align=center>".$fecha1."</td><td bgcolor=white nowrap align=center>".$p[4]."</td><td bgcolor=white nowrap align=center>".$fecha2."<td bgcolor=white>".$p[7]."</td>";
            if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){
           	}else{
           		$sanctions_output.="<td bgcolor=white nowrap align=center>".$p[10]."</td>";
           		if (isset($Expr_b))
           			$sanctions_output.="<td bgcolor=white nowrap align=center>".$p[1]."</td><td bgcolor=white nowrap align=center>".$p[9]."</td>";
           	}
           	$sanctions_output.= "</tr>";

		}
		$sanctions_output.=  "</table>";
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){

		}else{
			if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELSUS"])){
				if (!isset($Expr_b)) {
					$sanctions_output.="<a href=javascript:DeleteSuspentions('C')>".$msgstr["cancel"].'</a> &nbsp; | &nbsp; ';
					$sanctions_output.="<a href=javascript:DeleteSuspentions('D')>".$msgstr["delete"].'</a><br>';
				}
			}
        }
		$sanctions_output.="</dd>";
		$sanctions_output.= "\n<script>nSusp=".$nsusp."</script>";

	}

// se procesan las Multas

	$nmulta=0;
	if (count($multa)>0) {
		$sanctions_output.=  "<br><strong>".$msgstr["fine"]."</strong><table width=95% bgcolor=#cccccc>";
		if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"]))
		if (!isset($Expr_b)) $sanctions_output.="<th></th>";
		$sanctions_output.="<th>".$msgstr["date"]."</th><th>".$msgstr["concept"]."</th><th>".$msgstr["amount"]."</th><th>".$msgstr["comments"]."</th>";
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){

		}else{
			$sanctions_output.="<th>".$msgstr["operator_date_created"]."</th>";
			if (isset($Expr_b))
				$sanctions_output.= "<th>Situaci�n</th><th>Cancelado/Pagado</th>";
		}
		foreach ($multa as $linea) {
			if (trim($linea)!=""){
				$p=explode("|",$linea);
				if (($p[1]==0 or trim($p[1])=="") or isset($Expr_b)){
					$nmulta=$nmulta+1;
					$fecha1=PrepararFechaSanciones($p[3]);
					$sanctions_output.= "<tr>";
					if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"]))
						if (!isset($Expr_b))  $sanctions_output.="<td bgcolor=white><input type=checkbox name=pay value=".$p[8]."></td>";
					$sanctions_output.="<td bgcolor=white nowrap align=center>".$fecha1."</td><td bgcolor=white nowrap align=center>".$p[4]."</td><td bgcolor=white nowrap align=center>".$p[5]."<td bgcolor=white>".$p[7]."</td>";

	            	if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){
	            	}else{
	            		$sanctions_output.="<td bgcolor=white nowrap align=center>".$p[10]."</td>";
           				if (isset($Expr_b))
           					$sanctions_output.="<td bgcolor=white nowrap align=center>".$p[1]."</td><td bgcolor=white nowrap align=center>".$p[9]."</td>";
	            	}
	            	$sanctions_output.= "</tr>";
	            }
	 		}
		}
		$sanctions_output.= "</table>";
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){

		}else{
			if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"])){
        		if (!isset($Expr_b)) {
	        		$sanctions_output.="<a href=javascript:PagarMultas('P')>".$msgstr["pay"]."</a> &nbsp; | &nbsp; " ;
	        		$sanctions_output.="<a href=javascript:PagarMultas('C')>".$msgstr["cancel"]."</a> &nbsp; | &nbsp; " ;
	        		$sanctions_output.="<a href=javascript:PagarMultas('D')>".$msgstr["delete"]."</a><p>";
	       		}
    		}
    	}
    	$sanctions_output.= "\n<script>nMultas=".$nmulta."</script>";
	}

//se procesan las notas
    $nnota=0;
	if (count($nota)>0) {
		$sanctions_output.=  "<br><strong>".$msgstr["comments"]."</strong><table width=95% bgcolor=#cccccc>";
		if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"]))
			if (!isset($Expr_b)) $sanctions_output.="<th></th>";
		$sanctions_output.="<th>".$msgstr["date"]."</th><th>".$msgstr["concept"]."</th><th>".$msgstr["comments"]."</th>";
		foreach ($nota as $linea) {
			if (trim($linea)!=""){
				$nnota=$nnota+1;
				$p=explode("|",$linea);
				$sanctions_output.= "<tr>";
				if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"]))
					if (!isset($Expr_b)) $sanctions_output.="<td bgcolor=white><input type=checkbox name=note value=".$p[8]."></td>";
				$sanctions_output.="<td bgcolor=white nowrap align=center>".$p[3]."</td><td bgcolor=white nowrap align=center>".$p[4]."</td><td bgcolor=white>".$p[7]."</td>";
	 		}
		}
		$sanctions_output.= "</table>";
		if (isset($arrHttp["vienede"]) and $arrHttp["vienede"]=="ecta_web"){

		}else{
			if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"]["CIRC_CIRCALL"])  or isset($_SESSION["permiso"]["CIRC_DELFINE"])){
        		if (!isset($Expr_b)) $sanctions_output.="<a href=javascript:DeleteNote('D')>".$msgstr["delete"]."</a><p>";
    		}
    	}
    	$sanctions_output.= "\n<script>nNota=".$nnota."</script>";
	}

	if (($nmulta!=0 or $nsusp!=0) and (isset($arrHttp["inventory"]) or isset($arrHttp["reserve"]))) $ec_output.="<font color=red><strong>".$msgstr["pending_sanctions"]."</strong></font>";
?>

<script type="text/javascript">

function PagarMultas(Accion){
	Mfn=""
	switch (nMultas){
		case 1:
			if (document.ecta.pay.checked){
            	Mfn=document.ecta.pay.value
			}
			break
		default:
			for (i=0;i<nMultas;i++){
				if (document.ecta.pay[i].checked){
					Mfn+=document.ecta.pay[i].value+"|"
				}
			}
			break
	}
	if (Mfn==""){
		alert("<?php echo $msgstr["selfine"]?>")
		return
	}
	document.multas.Mfn.value=Mfn
	document.multas.Accion.value=Accion
	document.multas.Tipo.value="M"
	document.multas.submit()
}	
</script>