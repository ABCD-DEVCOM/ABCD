<?php
/*
20220207 fho4abcd improve html, remove old style buttons for default settings
20240129 fho4abcd improve html: end tags
20240403 fho4abcd New button for copy from other database
20251211 fho4abcd Improve html, new look expand/collapse button
20251223 fho4abcd Indented code + add some comments + HTML5
*/
include("scripts_dataentry.php");
include("toolbar_record.php");
echo "<table style='width:100%'><tr><td style='width:1%'> </td><td style='width:99%'>" ;
if (isset($arrHttp["error"])) echo $arrHttp["error"];
$xnr="";
$xtl="";
$Rtl="";
$Rnr="";
if (isset($default_values)){
    echo "<span class=title><h2>".$msgstr["valdef"]."</h2></span>";

}else{
    if (isset($arrHttp["Formato"])) echo "<input type=hidden name=Formato value='".$arrHttp["Formato"]."'>\n";
}
if ($arrHttp["Opcion"]!="valdef"){
    if (!isset($fmt_test)){
	echo "<b>".$arrHttp["Mfn"];
	if ($arrHttp["Mfn"]!="New") {
	    echo "/$maxmfn";
	}
	echo "</b>";
    }
}
    $xtt="";

if (isset($arrHttp["wks_a"])){
    $w=explode('|',$arrHttp["wks_a"]);
    echo "&nbsp; &nbsp; ".$msgstr["fmt"].":  (".$w[0].")";
}else{
    if (isset($arrHttp["wks"])){
	echo "&nbsp; &nbsp; ".$msgstr["fmt"].": ".$arrHttp['wks'];
    }
}
echo "&nbsp; <a class='bt bt-gray' id='expand_colapse_btn' href=JavaScript:OpenAll()>".$msgstr["expand_colapse"]."</a>";
// Se construye el Indice de acceso a la hoja de entrada
$ixIndice="S";
if ($ixIndice=="S"){
    $cuenta=0;
    $i=-1;
    for ($ix1=0;$ix1<count($vars);$ix1++){
	$linea=$vars[$ix1];
	$t=explode('|',$linea);
    }
    if ($i!=-1){
	echo "</td></table>";
	echo "</div><br>";
    }
}
echo "<input type=hidden name=tag$Rtl value='".$xtl."'>";
echo "<input type=hidden name=tag$Rnr value='".$xnr."'>\n";

// Se inicializa el arreglo con los tags a leer de la base de datos
PrepararFormato(); // Closes elements opened in this function
echo "<script>
is_marc='$is_marc'
</script>
";
if (!isset($fmt_test) and !isset($default_values)) {  //Para indicar que se esta haciendo el test de la hoja de entrada o creando valores por defecto
    $db=$arrHttp["base"];
    if (!$ver or isset($arrHttp["capturar"])){
	?>
	<div class="my-5">
	<table class=center>
	    <tr>
	    <?php
		if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]=="S"){
		?>
		<td align=center bgcolor=white >
		    <a class='bt-lg bt-green' href="javascript:CapturarRegistro()">
			<img src="../../assets/svg/catalog/ic_fluent_document_copy_24_regular.svg"
			title="<?php echo $msgstr["m_capturar_cur"]?>">
			<?php echo $msgstr["m_capturar_cur"]?>
		    </a>
		</td>
		<?php
		}else{
		    if (!isset($arrHttp["encabezado"])){
			echo "<td><a class='bt-lg bt-green' href=\"javascript:EnviarForma()\"><img src=\"../../assets/svg/catalog/ic_fluent_document_save_24_regular.svg\" alt=\"".$msgstr["actualizar"]."\">".$msgstr["actualizar"]."</a></td>\n";
			echo "<td><a class='bt-lg bt-gray' href=javascript:CancelarActualizacion()><img src=../../assets/svg/catalog/ic_fluent_pane_close_24_regular.svg alt=\"".$msgstr["cancelar"]."\">".$msgstr["cancelar"]."</a></td>\n";
			if ( isset($_SESSION["permiso"]["CENTRAL_DELREC"]) or isset($_SESSION["permiso"][$db."_CENTRAL_DELREC"]) or
			     isset($_SESSION["permiso"]["CENTRAL_ALL"]) or isset($_SESSION["permiso"][$db."_CENTRAL_ALL"])){
			    echo "<td><a class='bt-lg bt-red' href=javascript:EliminarRegistro()><img src=../../assets/svg/catalog/ic_fluent_delete_dismiss_24_regular.svg alt=\"".$msgstr["eliminar"]."\">".$msgstr["eliminar"]."</a></td>\n";
			}
		    }
		}
		?>
		</tr>
	</table>
	</div>
	<?php
    }
}
echo "<form method=post name=forma2 action=fmt.php >\n";
if (isset($arrHttp["encabezado"])) {
	 echo "<input type=hidden name=encabezado value=s>\n";
	 echo "<input type=hidden name=Formato value=".$arrHttp["base"].">\n";
}
echo "<input type=hidden name=IsisScript value=ingreso.xis>\n";
echo "<input type=hidden name=base value=".$arrHttp["base"].">\n";
echo "<input type=hidden name=cipar value=".$arrHttp["cipar"].">\n";
if ($arrHttp["Opcion"]=="capturar" || $arrHttp["Opcion"]=="nuevo" || $arrHttp["Opcion"]=="captura_bd") {
	$a="crear" ;
}else{
	$a=$arrHttp["Opcion"];
}
echo "<input type=hidden name=Opcion value=$a>\n";
if (isset($arrHttp["ventana"])) echo "<input type=hidden name=ventana value=".$arrHttp["ventana"].">\n";
echo "<input type=hidden name=ValorCapturado value=\"\">\n";
echo "<input type=hidden name=NoVar>\n";
echo "<input type=hidden name=Indice value=\"\">\n";
if (isset($arrHttp["Mfn"])){
	echo "<input type=hidden name=Mfn value=".$arrHttp["Mfn"].">\n";
	echo "<input type=hidden name=from value=".$arrHttp["Mfn"].">\n";
}
echo "<input type=hidden name=ver value=S>\n";

echo "</form>";
?>