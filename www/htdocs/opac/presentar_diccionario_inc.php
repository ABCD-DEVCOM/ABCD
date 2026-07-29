<?php
global $terBd,$db_path,$LastKey,$bd_list,$path,$xWxis,$meta_encoding, $actparfolder;
	$Prefijo=$_REQUEST["prefijo"];
	$LastKey="";
//  Se determinan las últimas claves presentadas para continuar con el recorrido del diccionario
	if (isset($_REQUEST["Navegacion"]) and $_REQUEST["Navegacion"]!=""){
		if ($_REQUEST["Navegacion"]=="mas terminos"){
			if (isset($_REQUEST["LastKey"]) and trim($_REQUEST["LastKey"])!=""){
				$LastKey=$_REQUEST["LastKey"];
			}
		}
		if ($_REQUEST["Navegacion"]=="ir a"){
			if (isset($_REQUEST["IR_A"])){
				$_REQUEST["LastKey"]=$_REQUEST["IR_A"];
				$LastKey=$_REQUEST["IR_A"];
			}
		}
	}
// Se hacen las búsquedas para cada base de datos
// Esta variable se usa para determinar que se está leyendo el primer índice y poder llevar la secuencia de la claves leídas de los otros diccionarios
	$ixbases=-1;
	if (isset($_REQUEST["LastKey"]))
		$LastKey=$_REQUEST["LastKey"];
	else
		$LastKey="";
	foreach ($bd_list as $bd=>$value){
		if (trim($bd)!=""){
			$bd=strtolower(trim($bd));
			$modo_atual = isset($_REQUEST["modo"]) ? $_REQUEST["modo"] : "";
			if (isset($_REQUEST["base"]) and $_REQUEST["base"] != "" and $_REQUEST["base"] != $bd and $modo_atual != "integrado") continue;			$cisis_ver="";
    		$diccio="";
    		if (isset($_REQUEST["LastKey"])and $_REQUEST["LastKey"]!="" ){
    			$hasta="LastKey=".$Prefijo.$LastKey;
    		}else{
    			$hasta="count=100";
    		}
			$query = "&base=$bd&cipar=$db_path".$actparfolder."/$bd".".par&Formato=opac.pft&Opcion=diccionario&prefijo=$Prefijo&campo=Palabras&Diccio=$diccio&$hasta";
			//echo "</select><p>$bd,$query,$xWxis"."opac/ifp.xis"."<br>";
			$contenido=wxisLlamar($bd,$query,$xWxis."opac/ifp.xis");
			foreach ($contenido as $t){
				//echo "$t<br>" ;  continue;
				$l=explode('|',$t);
				$linea=$l[0];
				$Prefijo = $_REQUEST["prefijo"] ?? "";
				$Opcion = $_REQUEST["Opcion"] ?? "";
				$pre=trim(substr($linea,0,strlen($_REQUEST["prefijo"])));
				if ($pre==$_REQUEST["prefijo"]){
					$ter=substr($linea,strlen($_REQUEST["prefijo"]));
					//if ($ter>=$LastKey ){
						//echo "<font color=red>$ter</font><br>";
						$terminos[]=$ter;
						$terBd[$bd]=$ter;
					//}else{
						//break;
					//}
				}
			}
			if ($ixbases==-1){
				$ixbases=0;
				$mayorclave=$terBd[$bd];
			}
		}
	}
	sort ($terminos);
	$terms=array_unique($terminos);

	if ($_REQUEST["Opcion"]=="libre")
		$delimitador="";
	else
		$delimitador='"';

?>