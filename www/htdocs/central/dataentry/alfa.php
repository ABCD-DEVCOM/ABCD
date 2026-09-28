<?php
/*
20211209 fho4abcd Rewrite, echos-> script. improve layout & html
20211212 fho4abcd Add TOP button + keep pref if db is switched + add breadcrumb to help the db manager+resolve script errors
20220114 fho4abcd Reshuffle code to enable visible output. Replace ifp.xis by ifp_slashm.xis:output if indexed with slashm
                  Don't make entries unique(they are unique)+Use correct prefix in case of copy
20220711 fho4abcd Use $actparfolder as location for .par files
20240403 fho4abcd Removed onblur:replaced by close button in standard mode
20240403 fho4abcd Improved help& layout in copy from other database mode
20250925 fho4abcd Show error if wrong extraction format is present in @autoridades.pft. Prevents wxis error on click.
20250925 fho4abcd Escape < and > if present in value.
20260928 fho4abcd Restore making the results unique + better handling of extraction format
*/
/*
** Functionality:
** - Search function: Show a list of search terms, sorted form A-Z
** - Copy from another database: Show a list of databases and show a list of search terms, sorted from A-Z
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
session_start();
if (!isset($_SESSION["permiso"])){
    header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
include ("../lang/admin.php");
include ("../lang/dbadmin.php");
include ("leerregistroisispft.php");

// ------------------------------------------------------
// INICIO DEL PROGRAMA
// ------------------------------------------------------
$lang=$_SESSION["lang"];
include("../common/header.php");

if (!isset($arrHttp["tagfst"])) $arrHttp["tagfst"]="";
if (!isset($arrHttp["delimitador"]))$arrHttp["delimitador"]="";
if (!isset($arrHttp["Tag"]))$arrHttp["Tag"]="";
if (!isset($arrHttp["prefijo"]))$arrHttp["prefijo"]="";
if (!isset($arrHttp["capturar"]))$arrHttp["capturar"]="";
if (!isset($arrHttp["pref"]))$arrHttp["pref"]=$arrHttp["prefijo"];

// some processing as we need the results in the breadcrumb
/*
** Some calls do not supply correct parameters: check first
*/
if (!isset($arrHttp["base"]) OR $arrHttp["base"]=="" ) {
    echo "<div style='color:red'>ERROR: base is not set. Dump of all url parameters:<br>";
    var_dump($arrHttp);
    echo "<br>-</div>";die;
}
$base=$arrHttp["base"];
$prefijo=urldecode($arrHttp["prefijo"]);
$pref=urldecode($arrHttp["pref"]);
/*
** Open the FDT or the default fdt
*/
$fdtfull=$db_path.$base."/def/".$lang."/".$base.".fdt";
$fdtfulldef=$db_path.$base."/def/".$lang_db."/".$base.".fdt";
if (file_exists($fdtfull))
    $fp=file($fdtfull);
else
    $fp=file($fdtfulldef);
/*
** Read the fdt file and determine if there is "principal entry" (field 3 = 1)
*/
$formato_e="";
$fdtfieldname="";
foreach($fp as $value) {
    $f=explode('|',$value);
    if ($f[3]==1){
        $fdtfieldname=$f[2];
        $fdttag=$f[1];
        if (substr($f[13],0,1)!="@"){
            // if the format is not specified to extract the principal input, the tag of the field is taken
            if (trim($f[13])!="")   $formato_e=$f[13]."'$$$'f(mfn,1,0)";
            else                    $formato_e="mhl,v".$f[1]."'$$$'f(mfn,1,0)";
        }
        elseif (strlen(trim($f[14]))>0) {
            // If there is a file (e.g. @autoridades.pft) still take the fdt extract value
            // This is a plaster as a good value in the pft seems to corrupt the picklist output
            $formato_e=trim($f[14]);
        }
        else {
            $formato_e=$f[13];
        }
        // If not specified (first time or switched after copy)
        if ($pref=="") $pref=$f[12];
        if ($prefijo=="") $prefijo=$f[12];
        break;
    }
}

?>
<body>
<div class="sectionInfo">
    <div class="breadcrumb" style="width:auto">
		<?php
        echo $msgstr["indicede"].": ".$fdtfieldname." &nbsp;(".urldecode($pref).")";
		?>
    </div>
    <div class="actions" style="width:auto">
		<?php
		if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]=="S"){
			$inframe=true;
			$smallbutton=true;
			include "../common/inc_close.php";
		} else {
			$smallbutton=true;
			include "../common/inc_close.php";
		}
		?>
    </div>
<div class="spacer">&#160;</div>
</div>
<?php
if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]=="S"){
	$ayuda="ayuda_captura.html";
}
include "../common/inc_div-helper.php";
?>
<div class="middle form">
<div class="formContent">
<?php
//foreach ($arrHttp as $var => $value)     echo "$var = $value<br>";

$formato_e=stripslashes($formato_e);
if ($formato_e=="") echo "<div style='color:red'>".$msgstr["azreuiresfdt"]."</div>";

$IsisScript=$xWxis."ifp_slashm.xis";
if (substr($formato_e,0,1)=="@"){
    $Formato=$db_path.$base."/pfts/".$lang."/".substr($formato_e,1);
    if (!file_exists($Formato)) $Formato=$db_path.$base."/pfts/".$lang_db."/".substr($formato_e,1);
    $Formato="@".$Formato;
}else{
    $Formato=$formato_e;
}
$query ="&base=".$base ."&cipar=$db_path".$actparfolder.$arrHttp["cipar"]."&Opcion=autoridades"."&tagfst=".$arrHttp["tagfst"];
$query.="&prefijo=".urlencode($prefijo)."&pref=".$pref."&formato_e=".urlencode($Formato)."&bymfn=S";
//echo $query;
include("../common/wxis_llamar.php");
//foreach ($contenido as $var=>$value) echo "$var=$value<br>";
// Unknown why to make unique: Should be an exception. But it is not. No clue why wxis_llamar does this
$contenido=array_unique($contenido);

?>
<script language=Javascript>
document.onkeypress =
    function (evt) {
        var c = document.layers ? evt.which
            : document.all ? event.keyCode
            : evt.keyCode;
        return true;
}
var nav4 = window.Event ? true : false;

function codes(e) {
    if (nav4) // Navigator 4.0x
        var whichCode = e.which

    else // Internet Explorer 4.0x
        if (e.type == 'keypress') // the user entered a character
             var whichCode = e.keyCode
        else
            var whichCode = e.button;
    if (e.type == 'keypress' && whichCode==13)
        IrA()
    else
        if (whichCode==13) IrA()
}
Separa="<?php echo $arrHttp["delimitador"]?>"
Tag="<?php echo $arrHttp["Tag"]?>"
Prefijo="<?php echo $prefijo?>"
cnv="N"
bsel="N"
<?php
if (isset($arrHttp["cnvtab"]))
    echo "cnvtabsel=\"&cnvtabsel".$prefijo."\"\n";
else
    echo "cnvtabsel=\"\"\n";
?>
function CambiarBase(){
    //Change Base
    tl=""
    nr=""
    i=document.Lista.baseSel.selectedIndex
    if (i==-1) i=0
    abd=document.Lista.baseSel.options[i].value
    if (i==-1 || i==0){
        return
    }
    a=abd.split("|")
    basecap=a[0]
    ciparcap=basecap+".par"
    if (top.frames.length>0){
        parent.indice.location.href="alfa.php?base="+basecap+"&cipar="+ciparcap+"&Opcion=autoridades&capturar=S&bymfn=S"
    }else{
        parent.indice.location.href="alfa.php?base="+basecap+"&ciparcap="+cipar+"&Opcion=autoridades&capturar=S&bymfn=S"

    }
}
function ObtenerTerminos(){
    //conversion table
    if (cnv=="S"){
        if (document.Lista.cnvtab.selectedIndex>0){
            cnvtabsel="&cnvtabsel="+document.Lista.cnvtab.options[document.Lista.cnvtab.selectedIndex].value
        }else{
            cnvtabsel=""
        }
    }else{
        cnvtabsel=""
    }
    basecap=""
    if (bsel=="S"){
        if (document.Lista.baseSel.selectedIndex>0){
            basecap=document.Lista.baseSel.options[document.Lista.baseSel.selectedIndex].value
        }
        top.basecap=basecap
        top.ciparcap=basecap+".par"
        top.cnvtabsel=cnvtabsel
    }else{
        basecap="<?php echo $base?>"
    }
    Seleccion=""
    icuenta=0
    i=document.Lista.autoridades.selectedIndex
    for (i=0;i<document.Lista.autoridades.options.length; i++){
        if (document.Lista.autoridades.options[i].selected){
            icuenta=icuenta+1
            if (Seleccion=="")
                Seleccion=document.Lista.autoridades.options[i].value
            else
                Seleccion=Seleccion+Separa+document.Lista.autoridades.options[i].value
        }

    }
    db="<?php echo $base?>"

    if (Seleccion!=""){
        if (bsel=="S"){
            if (top.NombreBaseCopiara=="")
                db="<?php echo $base?>"
            else
                db=top.NombreBaseCopiara
        }
        pref="<?php echo $pref?>"
        cipar="<?php echo $arrHttp["cipar"]?>"
        <?php
        if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]!=""){
            echo "top.xeditar=\"S\"\n";
            echo 'parent.main.location="fmt.php?xx=xx&base="+db+"&cipar="+db+".par&basecap="+basecap+"&ciparcap="+basecap+".par&Mfn="+Seleccion+"&Opcion=captura_bd&ver=S&capturar=S"+cnvtabsel+"&Formato="+basecap'."\n";
        }else{
            if (isset($arrHttp["Formato"]))
                echo "Formato='&Formato=".$arrHttp["Formato"]."'\n";
            else
                echo "Formato=''\n";
            echo 'window.opener.top.main.location.href="fmt.php?cc=xx&base="+db+"&cipar="+cipar+"&Mfn="+Seleccion+"&Opcion=ver&ver=S&Formato="+Formato+cnvtabsel
            window.opener.focus()'."\n";
        }
        ?>
    }
}

function Continuar(){
    i=document.Lista.autoridades.length
    a=' '
    if (i>1) {
        i--
        a=document.Lista.autoridades[i].text
    }
    AbrirIndice(a)
}

function IrA(ixj){
    a=document.Lista.ira.value
    AbrirIndice(a)
}

function AbrirIndice(Termino){
    <?php
    if (isset($arrHttp["Formato"])) {
        ?>
        Formato='<?php echo urlencode($arrHttp["Formato"])?>'
        <?php } else { ?>
        Formato=''
    <?php } ?>
    db='<?php echo $base?>'
    cipar='<?php echo $arrHttp["cipar"]?>'
    Pref='<?php echo $pref?>'
    Prefijo=Pref+Termino
    capturar='<?php echo $arrHttp["capturar"]?>'

    if (capturar!='') capturar='&capturar='+capturar
    if (cnv=="S"){
        if (document.Lista.cnvtab.selectedIndex>0){
            cnvtabsel="&cnvtabsel="+document.Lista.cnvtab.options[document.Lista.cnvtab.selectedIndex].value
        }else{
            cnvtabsel=""
        }
    }
    URL='alfa.php?&new=new&opcion=autoridades&base='+db+'&cipar='+cipar+'&pref='+Pref+'&prefijo='+Prefijo+capturar+'&formato_e=<?php echo urlencode($formato_e)?>'+cnvtabsel
    if (Formato!=""){
        URL+='&Formato='+Formato
    }
    self.location.href=URL
}
</script>
<form method=post name=Lista onSubmit="javascript:return false">
<?php
/*
** If the script is called  the option to capture from another database, the list of available databases is displayed
** si viene de la opción de capturar de otra base de datos se presenta la lista de bases de datos disponibles
*/
if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]=="S"){
	?>
	<span class="bt-disabled"><i class="fas fa-info-circle"></i> <?php echo $msgstr['m_capturar']?></span>
	<?php
    $key_bd="";
    if (isset($base)) $key_bd=$base;
    $prefijo="";
    if (!isset($arrHttp["Mfn"])) $arrHttp["Mfn"]="";
    $fp = file($db_path."bases.dat");
    foreach ($fp as $linea){
        if (trim($linea)!="") {
            $ix=strpos($linea,"|");
            $llave=trim(substr($linea,0,$ix));
            if ($llave!="acces")
                $lista_bases[$llave]=$linea;
        }
    }
    ?>
    <script>bsel="S"</script>
    &nbsp;
    <table  cellspacing=0 cellpadding=0 border=0  >
    <tr>
        <td><?php echo $msgstr["database"]?></td>
        <td>
            <select name=baseSel onChange=CambiarBase() style=width:150px>
            <option value="">
            <?php
            $i=-1;
            foreach ($lista_bases as $key => $value) {
                $i=$i+1;
                $v=explode('|',$value);
                if (isset($_SESSION["permiso"]["db_".$key]) or isset($_SESSION["permiso"]["db_ALL"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])  or isset($_SESSION["permiso"][$key."_CENTRAL_ALL"])){
                    if ($v[0]==$base)
                        $sel="selected";
                    else
                        $sel="";
                    echo "<option value=\"".$key."\" $sel >".trim($v[1]);
                }
            }
            ?>
            </select>
        </td>
    </tr>
    <?php
    // read conversion tables
    $archivo=$db_path."/cnv/z3950.cnv";
    if (file_exists($archivo)){
        $fp=file($archivo);
        ?>
        <tr>
            <td>
                <script>cnv="S"</script>
                <?php echo $msgstr["z3950_tab"]?>
            </td><td>
                <select name=cnvtab style=width:150px>
                <option></option>
                <?php
                foreach ($fp as $value){
                    $v=explode('|',$value);
                    $xsel="";
                    if (isset($arrHttp["cnvtabsel"])){
                        if ($v[0]==$arrHttp["cnvtabsel"]) $xsel=" selected";
                    }
                    echo "<option value='".$v[0]."' $xsel title='".$v[1]."' alt='".$v[1]."'>".$v[1]."\n";
                }
                ?>
            </select>
            </td>
        </tr>
    <?php } ?>
    </table>
<?php
}
if (isset($arrHttp["capturar"]) and $arrHttp["capturar"]=="S")
    $xwidth="330";
else
    $xwidth="570";
?>
<table cellpadding=0 cellspacing=0 border=0  height=80%>
<tr>
<td  width=5% align=center style='font-size:10px'>
    <?php for ($i=65;$i<91;$i++ ){?>
    <a href="javascript:AbrirIndice('<?php echo chr($i)?>')"><?php echo chr($i)?></a><br>
    <?php } ?>
</td>
<td width=95% valign=top>
<select name=autoridades size=28 style="width:<?php echo $xwidth?>px; height:400px" onchange=ObtenerTerminos()>
<?php
foreach ($contenido as $linea){
    $linea=trim($linea);
    if ($linea!=""){
	$f=explode('$$$',$linea);
	// Check for invalid extraction format
	if (!isset($f[1]) ) {
		echo "</select>";
		echo "<div style='color:red'>".$msgstr["fatal"].": ";
		echo "Extraction format for ".$fdtfieldname." (".$fdttag.") in FDT<br> must contain <b>v".$fdttag,"'$$$'f(mfn,1,0)</b><br>";
		echo "Update Extraction format in FDT (safe) or ".$formato_e." (mind interaction with picklist!)</div";
		die;
	}
	$opttitle=str_replace("'","&apos;",$f[0]);
	$opttext=str_replace("<","&lt;",$opttitle);
	$opttext=str_replace(">","&gt;",$opttext);
	echo "<option value=".$f[1]." title='".$opttitle."'>".$opttext."</option>\n";
    }
}
?>
</select></td>

</tr>
<tr>
    <td colspan=2><!--full width required to avoid wrap in all languages-->
        <a href="javascript:AbrirIndice(' ')" class="bt bt-gray" title='<?php echo $msgstr["src_top"]?>'>
             <i class="fas fa-chevron-circle-up"></i> 
        </a>
        <a href="javascript:Continuar()" class="bt bt-gray" title='<?php echo $msgstr["masterms"]?>'>
             <i class="fas fa-chevron-circle-down"></i>
        </a>
        &nbsp;
        <?php echo $msgstr["avanzara"]?>
        <input type=text name=ira size=5 value="" onKeyPress="codes(event)" title="<?php echo $msgstr["src_advance"];?>">
        <a href=Javascript:IrA() class="bt bt-gray" title='<?php echo $msgstr["src_enter"]?>'>
            <i class="fas fa-angle-right"></i></a>
    </td>
</tr>
</table>
</form>
</div>
</div>
</body>
</html>
