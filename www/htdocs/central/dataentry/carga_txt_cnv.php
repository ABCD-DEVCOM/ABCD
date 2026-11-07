<?php
/* Modifications
2021-03-08 fho4abcd Replaced helper code fragment by included file
2021-03-08 fho4abcd Replaced obsolete each, correct line ends
2021-03-08 fho4abcd Add footer
2021-03-08 fho4abcd Error message if cnv folder does not exist and creation fails
2021-03-08 fho4abcd Pass selected records (conforms with iso export)
20211215 fho4abcd Backbutton by included file
20220106 fho4abcd new buttons, sanitized code, show table in poppoup
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
if (!isset($_SESSION['lang'])) $_SESSION["lang"]="en";
include("../common/get_post.php");
include ("../config.php");
$lang=$_SESSION["lang"];


include("../lang/admin.php");
include("../lang/soporte.php");
include("carga_txt_hdr.php");
$backtoscript="../dataentry/administrar.php"; // The default return script
if ( isset($arrHttp["backtoscript"])) $backtoscript=$arrHttp["backtoscript"];
if (!isset($arrHttp["accion"])) $arrHttp["accion"]="";
if (!isset($arrHttp["seleccionados"])) $arrHttp["seleccionados"]="";
// The name of the conversion folder
$cnvfoldername="cnv";

//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";

if (!isset($arrHttp["proceso"]) or $arrHttp["proceso"]!="eliminar"){
include("../common/header.php");
?>
<body>
<script language="JavaScript" type="text/javascript" src=js/lr_trim.js></script>
<script language=javascript>
function LeerTxt(Cnv){
	url="carga_txt_ver.php?base=<?php echo $arrHttp["base"]?>&cnv="+Cnv
	msgwin=window.open(url,"","menu=no,scrollbars=yes,status=yes,width=800,height=380,resizable")
	msgwin.focus()
}
function Eliminar(Archivo){
	if (confirm("<?php echo $msgstr["cnv_deltab"]?>"+" "+Archivo)==true){
		url="carga_txt_cnv.php?base=<?php echo $arrHttp["base"]?>&cnv="+Archivo+"&proceso=eliminar&lang=<?php echo $lang?>&accion=<?php echo $arrHttp["accion"]?>"
		self.location=url
	}
}
function check( x )  {
    x = x.replace(/\*/g, "")      // delete *
   	x = x.replace(/\[/g, "")      // delete [
   	x = x.replace(/\]/g, "")      // delete ]
   	x = x.replace(/\</g, "")      // delete <
   	x = x.replace(/\>/g, "")      // delete >
   	x = x.replace(/\=/g, "")      // delete =
   	x = x.replace(/\+/g, "")      // delete +
   	x = x.replace(/\'/g, "")      // delete '
   	x = x.replace(/\"/g, "")      // delete "
   	x = x.replace(/\\/g, "")      // delete \
   	x = x.replace(/\//g, "")      // delete /
   	x = x.replace(/\,/g, "")      // delete ,
   	x = x.replace(/\./g, "")      // delete .
   	x = x.replace(/\:/g, "")      // delete :
   	x = x.replace(/\;/g, "")      // delete ;
   	x = x.replace(/ /g, "_")         // delete spaces
	return x
}
function GuardarTabla(){
	Tabla=""
	linea=""
	for (i=0;i<document.explora.rotulo.length;i++){

		rot=Trim(document.explora.rotulo[i].value)
		if (!document.explora.delimited.checked && !document.explora.headermode.checked){
			if (rot!=""){
				if (rot.substr(0,2)!='$$'){
					alert("<?php echo $msgstr["cnv_inicior"]?>")
					return
				}
				if (rot.substr(rot.length-1,1)!=":"){
					alert("<?php echo $msgstr["cnv_finr"]?>")
					return
				}
			}
		}
		if (Trim(document.explora.rotulo[i].value)!=""){
			linea=document.explora.rotulo[i].value+"|"+document.explora.tag[i].value+"|"+document.explora.subc[i].value+"|"+document.explora.editsubc[i].value+"|"+document.explora.occ[i].value+"|"+document.explora.formato[i].value
			if (Tabla==""){
				Tabla=linea
			}else{
				Tabla+="!!"+linea
			}
		}
		if (Trim(document.explora.separador.value)=="" && !document.explora.delimited.checked && !document.explora.headermode.checked){
			alert ("<?php echo $msgstr["cnv_separador"]?>")
			return
		}

	}
	document.explora.tablacnv.value=Tabla

	archivo=Trim(document.explora.fn.value)
	archivo=check(archivo)
	if (archivo==""){
		alert("<?php echo $msgstr["cnv_faltantc"]?>")
		return
	}
	document.explora.action="carga_txt_guardar.php";
	document.explora.fn.value=archivo
	document.explora.submit()
}
</script>
<div class="sectionInfo">
	<div class="breadcrumb">
    <?php echo $msgstr["cnv_".$arrHttp["accion"]]." ".$msgstr["cnv_".$arrHttp["tipo"]]."&nbsp;&rarr;&nbsp;".$msgstr["cnv_sel"]?>
	</div>
	<div class="actions">
    <?php include "../common/inc_back.php";?>
	</div>
	<div class="spacer">&#160;</div>
</div>

<?php $ayuda="txt2isis.html";include "../common/inc_div-helper.php" ?>
<div class="middle form">
<div class="formContent">
<form name=explora action=eliminararchivo.php method=post>
<input type=hidden name=base value=<?php echo $arrHttp["base"]?>>
<input type=hidden name=tablacnv value="">
<input type=hidden name=tipo value="txt">
<input type=hidden name=accion value=<?php echo $arrHttp["accion"]?>>
<?php
}
$fullcnvdir=$db_path.$arrHttp["base"]."/".$cnvfoldername."/";
$the_array = Array();
// Create folder "../cnv" if it does not exist
if ( ! file_exists($fullcnvdir) ) {
    if ( !mkdir($fullcnvdir) ) {
        echo "<h2 style=\"color:red\">This function requires folder $fullcnvdir.</h2>";
        echo "<h2 style=\"color:red\">Automatic creation failed. Please create it manually</h2>";
        include("../common/footer.php");
        die;
    } else {
        echo "<div>Folder $fullcnvdir created.</div>";
    }
}
// List files in folder cnv 
$handle = opendir($fullcnvdir);
if (!isset($arrHttp["proceso"]) or $arrHttp["proceso"]!="eliminar"){
	while (false !== ($file = readdir($handle))) {
	   if ($file != "." && $file != "..") {
	   		if(is_file($fullcnvdir."/".$file))
	            $the_array[]=$file;
	        else
	            $dirs[]=$fullcnvdir."/".$file;
	   }
	}
	closedir($handle);
	if (count($the_array)>0){
		sort ($the_array);
		reset ($the_array);
		$Url="";
        $actscript="";
        if ($arrHttp["accion"]=="import") {
            $actscript="carga_txt.php";
        } else {
            $actscript="exporta_txt.php";
        }
		?>
        <span style='color:var(--blue);font-weight: bold'><?php echo $msgstr["cnv_sel"]?></span> &nbsp; &nbsp;
        <?php
        echo "<a href=../documentacion/ayuda.php?help=$lang/conversion_table.html target=_blank>".$msgstr["help"]."</a>&nbsp; &nbsp;";
        if (isset($_SESSION["permiso"]["CENTRAL_EDHLPSYS"]))
        	echo "<a href=../documentacion/edit.php?archivo=".$lang."/conversion_tabla.html target=_blank>".$msgstr["edhlp"]."</a>";
		?>
        <table border=0  cellspacing=1 cellpadding=3 bgcolor=#cccccc>
            <?php foreach( $the_array as $key=>$val){?>
            <tr>
                <td><a href="javascript:LeerTxt('<?php echo $val?>')" class="bt bt-gray" title='<?php echo $msgstr["cnv_avis"]?>' >
                        <i class="fas fa-tv"></i></a>
                </td>
                <td><a href="carga_txt_cnv.php?base=<?php echo $arrHttp["base"]."&cnv=$val&proceso=editar&lang=$lang&tipo=".$arrHttp["tipo"]."&accion=".$arrHttp["accion"]?>"
                        class="bt bt-gray" title='<?php echo $msgstr["cnv_aedit"]?>'>
                        <i class="fas fa-edit"></i></a>
                </td>
                <td><a href="javascript:Eliminar('<?php echo $val?>')" class="bt bt-red" title='<?php echo $msgstr["cnv_aelim"]?>'>
                    <i class="fas fa-trash-alt"></i></a>
                </td>
                <td bgcolor=white><font face=verdana size=2 color=darkred><b><?php echo $val?></b></font></td>
                <td><a href="<?php echo $actscript;?>?base=<?php echo $arrHttp["base"]."&cipar=".$arrHttp["base"].".par&cnv=".$val."&lang=".$lang."&accion=".$arrHttp["accion"]."&tipo=".$arrHttp["tipo"]."&seleccionados=".$arrHttp["seleccionados"]?>"
                        class="bt bt-green" title='<?php echo $msgstr["cnv_aselc"]?>'>
                        <i class="fas fa-check"></i>&nbsp;<?php echo $msgstr["seleccionar"]?></a>
                </td>
            <?php } ?>
        </table>
		<hr>
        <?php
	}
}
$tc=array();
$rep="";
$separador="";
$delimited="";
$headermode="";
$tit=$msgstr["nueva"];
if (isset($arrHttp["proceso"])){
	switch ($arrHttp["proceso"]){
		case "editar":
			$tit=$msgstr["editar"];
			$fp=file($fullcnvdir."/".$arrHttp["cnv"]);
			foreach ($fp as $value){
				$value=trim($value);
				if ($rep==""){
					if ($value=='[TABS]'){
						$delimited=$value;
						$separador="";
						$rep="S";
					}elseif ($value=='[HEADER]'){
						$headermode=$value;
						$separador="";
						$rep="S";
					}else{
						$rep="S";
						$separador=$value;
					}
				}else{
					$a=explode('|',$value);
					$tc[$a[1]][0]=$a[0];  //rotulo
					$tc[$a[1]][1]=$a[1];  //tag
					$tc[$a[1]][2]=$a[2];  //subcampos
					$tc[$a[1]][3]=$a[3];  //delimitador
					$tc[$a[1]][4]=$a[4];  //ocurrencias
					$tc[$a[1]][5]=$a[5];  //formato
				}
			}
			break;
		case "eliminar":
			$fp=$fullcnvdir."/".$arrHttp["cnv"];
			if (file_exists($fp)) {
				$r=unlink($fullcnvdir."/".$arrHttp["cnv"]);
			}
			header("Location: carga_txt_cnv.php?base=".$arrHttp["base"]."&tipo=txt&accion=".$arrHttp["accion"]);
			die;
			break;
		default:
	}
}

echo "<h4>$tit ".$msgstr["cnv_tab"];
if (count($the_array)<=0) {
	echo "&nbsp; <a href=../documentacion/ayuda.php?help=". $lang."/conversion_table.html target=_blank>".$msgstr["help"]."</a>&nbsp &nbsp";
	if (isset($_SESSION["permiso"]["CENTRAL_EDHLPSYS"]))
        	echo "<a href=../documentacion/edit.php?archivo=".$lang."/conversion_table.html target=_blank>".$msgstr["edhlp"]."</a>";
}
echo "</h4>";

$fpDb_fdt = $db_path.$arrHttp["base"]."/def/".$lang."/".$arrHttp["base"].".fdt";
if (!file_exists($fpDb_fdt)) {
	$fpDb_fdt = $db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["base"].".fdt";
	if (!file_exists($fpDb_fdt)){
  			echo $arrHttp["base"]."/def/".$lang."/".$arrHttp["base"].".fdt"." no existe";
		die;
	}
}
$fp=file($fpDb_fdt);

?>
<input type=checkbox name=delimited <?php if ($delimited=="[TABS]") echo "checked";?> onclick="if(this.checked) document.explora.headermode.checked=false">&nbsp;<?php echo $msgstr["delimited_tab"];?>
&nbsp; &nbsp;<input type=checkbox name=headermode <?php if ($headermode=="[HEADER]") echo "checked";?> onclick="if(this.checked) document.explora.delimited.checked=false">&nbsp;<?php echo $msgstr["txtimp_header"];?>
<div style="font-size:0.85em;color:#555;margin:4px 0 8px 0"><?php echo $msgstr["txtimp_header_hint"];?></div>
<table border=0 bgcolor=#cccccc cellpadding=3 cellspacing=1 >
    <tr><td><?php echo $msgstr["campo"]?></td>
        <td><?php echo $msgstr["tag"]?></td>
        <td><?php echo $msgstr["cnv_rotulo"]?></td>
        <td><?php echo $msgstr["tipo"]?></td>
        <td><?php echo $msgstr["subc"]?></td>
        <td><?php echo $msgstr["editsubc"]?></td>
        <td width=10><?php echo $msgstr["osep"]?></td>
        <td nowrap><?php echo $msgstr["pftex"]?></td>
    </tr>
<?php
$ix=-1;
foreach ($fp as $value){
	$t=explode('|',$value);
	if ($t[0]!='G'){
		if ($t[0]=="LDR"){
			PresentarLeader($db_path.$arrHttp["base"]."/def/".$lang."/leader.fdt",$tc);
			continue;
		}
		$ix=$ix+1;
		$tag=$t[1];
		if ($tag!=""){
			echo "\n<tr><td bgcolor=white class=td>";
			echo $t[2];
			echo "</td>";
			echo "<td bgcolor=white class=td>".$tag."<input type=hidden name=tag value=".$tag."></td>";
			echo "<td bgcolor=white><input type=text name=rotulo size=30 value=\"";
			if (isset($tc[$tag][0])) echo $tc[$tag][0];
			echo "\"></td>";
			echo "<td bgcolor=white class=td>";
			$tipo=$t[7];
			switch ($tipo){
				case "O":
					echo "Option";
					break;
				case "C":
					echo "Check (Repetible)";
					break;
				case "A":
					echo "HTML";
					break;
				case "R":
					echo "Text (Repetible)";
					break;
				default:
					echo "Text";
					break;
			}
			echo "</td><td bgcolor=white class=td><input type=text name=subc size=10 value='";
			if (isset($tc[$tag][2]))
				echo $tc[$tag][2];
			else
				if ($t[5]!="" and ($tipo=="R" or $tipo=="")) echo $t[5];
			echo "'>";
			echo "</td><td bgcolor=white class=td><input type=text name=editsubc size=10 value='";
			if (isset($tc[$tag][3]))
				echo $tc[$tag][3];
			else
				//if (trim(substr($value,59,2))!="" and ($tipo=="R" or $tipo=="")) echo rtrim(substr($value,74,10));
				echo rtrim($t[6]);
			echo "'>";
			echo "</td><td bgcolor=white width=5><input type=text name=occ size=5 value=\"";
			if (isset($tc[$tag][4])) {
				echo $tc[$tag][4];
			}else{
				if ($t[4]==1)echo "R";
			}
			echo "\"></td><td bgcolor=white><input type=text name=formato size=40 value=\"";
			if (isset($tc[$tag][5])) echo $tc[$tag][5];
			echo "\"></td>";
		}
	}
}
?>
    <tr><td colspan=8 bgcolor=linen><?php echo $msgstr["cnv_sep"]?>: <input type=text size=5 name=separador value="<?php echo $separador;?>">
        </td>
    </tr>
<?php
$arch="";
if (isset($arrHttp["cnv"])){
	$ixpos=strpos($arrHttp["cnv"],".");
	$arch=substr($arrHttp["cnv"],0,$ixpos);
}
?>
    <tr><td colspan=6 valign=top align=right><font color=darkred><?php echo $msgstr["cnv_ntab"];?>: <input type=text size=20 name=fn value=<?php echo $arch;?>> .cnv &nbsp;&nbsp;
         <a class='bt-lg bt-green' href="javascript:GuardarTabla()">
            <img src="../../assets/svg/catalog/ic_fluent_document_save_24_regular.svg" border=0 alt="Update"><?php echo $msgstr["save"];?></a>
        </td>
    </tr>
</table>
</form>

</div></div>
<?php
include("../common/footer.php");
///=================================================
function PresentarLeader($leader,$tc){
	$fp=file($leader);
	foreach ($fp as $value){
		$t=explode('|',$value);
		echo "<tr><td bgcolor=white>".$t[2]."</td>";
		echo "<input type=hidden name=tag value=".$t[1].">";
		echo "<td bgcolor=white>".$t[1]."</td>";
		$tag=$t[1];
		echo "<td bgcolor=white><input type=text name=rotulo size=30 value=\"";
		if (isset($tc[$tag][0]))
			echo $tc[$tag][0];

		echo "\"></td>";
		echo "<td bgcolor=white class=td>text</td>";
		echo "<td bgcolor=white class=td><input type=hidden name=subc></td>";
		echo "<td bgcolor=white class=td><input type=hidden name=editsubc></td>";
		echo "<td bgcolor=white class=td><input type=hidden name=occ></td>";
		echo "<td bgcolor=white class=td><input type=text name=formato size=40 value=\"";
		if (isset($tc[$tag][5])) echo $tc[$tag][5];
		echo "\"></td>";
	}
}

