<?php

/**
 * @program:   ABCD - ABCD-Central - https://abcd-community.org/
 * @file:      sanctions_inc.php
 * @desc:      Calculate suspenctions and fines
 * @author:    Guilda Ascencio
 * @contributor: Roger Craveiro Guilherme
 * @since:     20091203
 * @version:   2.2
 * 
 * CHANGE LOG:
 * 2023-04-14 rogercgui Field 50 of the suspml database now stores the information available in field 85 of the Trans database and no longer from the Lending Policies as in previous versions.
 * 2025-08-23 rogercgui Fix TypeError and Undefined array key errors.
 */

include("functions.php");
require_once("fecha_de_devolucion.php");


function AcumularSuspensiones()
{
	global $Wxis, $xWxis, $wxisUrl, $db_path, $locales, $arrHttp, $msgstr;
	$formato_obj = "v1'|',v10'|',v20'|',v30'|',v40'|',v50'|',v60'|',mhl,v100'|',f(mfn,1,0)/";
	$Expresion = "TR_S_" . $arrHttp["usuario"] . " and ST_0";
	$query = "&Expresion=$Expresion" . "&base=suspml&cipar=$db_path" . "par/suspml.par&Pft=" . $formato_obj;
	include("../common/wxis_llamar.php");
	return $contenido;
}


function Sanciones($fecha_d, $atraso, $cod_usuario, $inventario, $ppres, $ncontrol, $bd, $tipo_usuario, $tipo_objeto, $politica_str, $referencia, $Mfn_reserva = "", $reservado = "")
{
	global $Wxis, $xWxis, $wxisUrl, $db_path, $locales, $arrHttp, $msgstr;

	$p = explode('|', $ppres);
	$u_multa = 0;
	$u_suspension = 0;
	$u_multa_r = 0;
	$u_suspension_r = 0;

	if (isset($p[7])) $u_multa = (int)$p[7];
	if (isset($p[8])) $u_multa_r = (int)$p[8];
	if (isset($p[9])) $u_suspension = (int)$p[9];
	if (isset($p[10])) $u_suspension_r = (int)$p[10];
	$fine_value = 0;
	if ($Mfn_reserva != "" or $reservado != "") {
		if ($u_suspension_r > 0) $u_suspension = $u_suspension_r;
		if ($u_multa_r > 0)      $u_multa = $u_multa_r;
	}
	if (isset($locales['FINE_VAL']))
		$fine_value = trim($locales['FINE_VAL']);
	else
		$fine_value = 0;
	$ValorCapturado = "";
	$total_fine = 0;
	$total_susp = 0;
	$ixdiv = 1;
	if (isset($locales["decimals"])) $ixdiv = $locales["decimals"];
	if ($u_multa > 0 and $fine_value > 0) {
		$total_fine = $atraso * $fine_value;
		$total_fine = $total_fine / $ixdiv;
	}

	$concepto = $politica_str;
	if ($u_suspension > 0)
		$total_susp = $atraso * $u_suspension;
	$fecha_v = "";
	$fecha_inicio = date("Ymd");
	$unidad = 'D';
	$fecha_v = FechaDevolucion($total_susp, $unidad, date("Ymd"), $tipo_usuario);
	if (strlen($fecha_v) > 8) $fecha_v = substr($fecha_v, 0, 8);
	$Mfn = "";

	// CORREÇÃO: Verifica se a chave 'APPLY_SANCTION' existe antes de usá-la.
	if (isset($locales["APPLY_SANCTION"])) {
		switch ($locales["APPLY_SANCTION"]) {
			case "A":  //se aplican multas y suspensiones
				if ($total_fine > 0) {
					$ValorCapturado = "<1 0>M</1><10 0>TR_M_" . $cod_usuario . "</10><20 0>" . $cod_usuario . "</20><30 0>" . $fecha_inicio . "</30><40 0>" . $concepto . "</40><50 0>" . $total_fine . "</50>";
				}
				if ($total_susp > 0) {
					if ($ValorCapturado != "") $ValorCapturado .= "\n";
					$ValorCapturado .= "<1 0>S</1><10 0>TR_S_" . $cod_usuario . "</10><20 0>" . $cod_usuario . "</20><30 0>" . $fecha_inicio . "</30><40 0>" . $concepto . "</40><60 0>" . $fecha_v . "</60>";
				}
				break;
			case "F":  //solo multas
				if ($total_fine > 0) {
					$ValorCapturado = "<1 0>M</1><10 0>TR_M_" . $cod_usuario . "</10><20 0>" . $cod_usuario . "</20><30 0>" . $fecha_inicio . "</30><40 0>" . $concepto . "</40><50 0>" . $total_fine . "</50>";
				}
				break;
			case "S":  //solo suspensiones
				if ($total_susp > 0) {
					$ValorCapturado = "<1 0>S</1><10 0>TR_S_" . $cod_usuario . "</10><20 0>" . $cod_usuario . "</20><30 0>" . $fecha_inicio . "</30><40 0>" . $concepto . "</40><60 0>" . $fecha_v . "</60>";
				}
				break;
			default:
				// Se a chave existe mas tem um valor inesperado, não faz nada.
				break;
		}
	}

	if ($ValorCapturado != "") {
		$ValorCapturado .= "<120>" . $_SESSION["login"] . "^d" . date("Ymd h:s:i") . "</120>";
		$ValorCapturado = urlencode($ValorCapturado);
		$IsisScript = $xWxis . "actualizar_registro.xis";
		$query = "&base=suspml&cipar=$db_path" . "par/suspml.par&lang=" . $_SESSION['lang'] . "&login=" . $_SESSION["login"] . "&Mfn=New&Opcion=crear&ValorCapturado=" . $ValorCapturado;
	}

	if (file_exists($db_path . "logtrans/data/logtrans.mst")) {
		require_once("../circulation/grabar_log.php");
		$datos_trans["CODIGO_USUARIO"] = $cod_usuario;
		$datos_trans["BD"] = $bd;
		$datos_trans["NUM_CONTROL"] = $ncontrol;
		$datos_trans["NUM_INVENTARIO"] = trim($inventario);
		$datos_trans["TIPO_OBJETO"] = $tipo_objeto;
		$datos_trans["FECHA_PROGRAMADA"] = $fecha_d;
		$datos_trans["DIAS_ATRASO"] = $atraso;
		$datos_trans["MULTA"] = $total_fine;
		$datos_trans["DIAS_SUSPENSION"] = $total_susp;
		GrabarLog("S", $datos_trans, $Wxis, $xWxis, $wxisUrl, $db_path, "RETORNAR");
	}
	if (isset($query)) {
		include("../common/wxis_llamar.php");

		// HOOK (event): a fine and/or suspension was recorded in suspml
		if (function_exists('abcd_do_action')) {
			abcd_do_action('circulation_sanction_applied', [
				'user_code'       => $cod_usuario,
				'user_type'       => $tipo_usuario,
				'inventory'       => trim($inventario),
				'control_number'  => $ncontrol,
				'database'        => $bd,
				'object_type'     => $tipo_objeto,
				'due_date'        => $fecha_d,
				'overdue_days'    => $atraso,
				'fine'            => $total_fine,
				'suspension_days' => $total_susp,
				'operator'        => $_SESSION["login"] ?? '',
			]);
		}
	}
}
