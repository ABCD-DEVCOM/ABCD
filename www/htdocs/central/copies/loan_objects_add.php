<?php
/*
20220209 fho4abcd div-helper, backButton
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}

include("../common/get_post.php");
include("../config.php");
$lang = $_SESSION["lang"]; /* <-- Variável adicionada aqui */
include("../lang/acquisitions.php");
include("../lang/admin.php");

//foreach ($arrHttp as $var=>$value) echo "*$var=$value<br>";

// SE LEE LA TABLA DE STATUS DE LAS COPIAS
$tab_st=$db_path."copies/def/".$_SESSION["lang"]."/status_copy.tab";
if (!file_exists($tab_st))
	$tab_st=$db_path."copies/def/".$lang_db."/status_copy.tab";
if (!file_exists($tab_st)){
	echo " Missing status_copy.tab";
	die;
}
$fp=file($tab_st);
foreach ($fp as $value){
	$value=trim($value);
	if ($value!=""){
		$v=explode("|",$value);
		$status["^a".$v[0]."^b".$v[1]]="(".$v[0].") ".$v[1];
	}

}


// GET THE CONTROL NUMBER FOR THE BIBLIOGRAPHIC RECORD
if (!isset($arrHttp["cn"])){
	LeerFst($arrHttp["base"]);
	if ($tag_ctl!=""){
		$Formato="v".$tag_ctl;

//Se lee el registro bibliográfico para capturar el número del objeto

		$query = "&base=".$arrHttp["base"]."&cipar=$db_path"."par/".$arrHttp["base"].".par"."&from=".$arrHttp["Mfn"]."&to=".$arrHttp["Mfn"]."&Formato=$Formato&Opcion=rango";
		$IsisScript=$xWxis."imprime.xis";
		include("../common/wxis_llamar.php");
		$valortag[1]=implode("",$contenido);
		if ($valortag[1]==""){     //CHECK IF THE RECORD HAS CONTROL NUMBER
			$err_copies="Y";
		}else{
			$err_copies="N";
			$arrHttp["cn"]="CN_".$arrHttp["base"]."_".$valortag[1];
		}
	}else{
		Print_page();
		die;
	}
}

//GET THE RECORD FROM LOANOBJECTS USING THE CONTROL NUMBER
$Formato=$db_path."loanobjects/pfts/".$_SESSION["lang"]."/loanobjects_add.pft" ;
if (!file_exists($Formato)) $Formato=$db_path."loanobjects/pfts/".$lang_db."/loanobjects_add.pft"  ;
$Expresion=$arrHttp["cn"];
$query = "&base=loanobjects&cipar=$db_path"."par/loanobjects.par"."&from=1&Formato=@$Formato&Opcion=buscar&Expresion=".urlencode($Expresion)."&cttype=s";
$IsisScript=$xWxis."imprime.xis";
include("../common/wxis_llamar.php");
$old_c=$contenido;
//CREATE LIST OF INVENTORY NUMBERS FOR CHECKING DUPLICATES IN THE RECORD
$ixc=0;
foreach ($contenido as $value){
	if (trim($value)!=""){
	    if ($ixc==0){
	    	$ixc=1;
	    	$v=explode('$$$',$value);
	    	if (isset($v[1]))
	    		$value=$v[1];
	    	else
	    		$value="";
	    }
		$inv=explode('|',$value);
		if (isset($inv[2]))
			$dup[$inv[2]]="Y";
	}
}
//GET THE COPIES TO BE ADDED TO THE LOANOBJECTS DATASBASE
$Formato=$db_path."copies/pfts/".$_SESSION["lang"]."/copies_add.pft" ;
if (!file_exists($Formato)) $Formato=$db_path."copies/pfts/".$lang_db."/copies_add.pft"  ;
$Expresion=$arrHttp["cn"]."* STATUS_1";
$query = "&base=copies&cipar=$db_path"."par/copies.par"."&from=1&Formato=@$Formato&Opcion=buscar&Expresion=".$Expresion;
$IsisScript=$xWxis."imprime.xis";
$contenido=array();
include("../common/wxis_llamar.php");
//foreach ($contenido as $value) echo "$value<br>";
$c=implode("",$contenido);

//GET THE TYPE OF OBJECT IN RESPECT TO THE LOAN POLICY
$file=$db_path."circulation/def/".$_SESSION["lang"]."/items.tab";
if (!file_exists($file)) $file=$db_path."circulation/def/".$lang_db."/items.tab";
$fp_items=file($file);


Print_page();
if (trim($c)=="") {
	echo "<p><br><br><dd><strong>".$msgstr["nocopiestoadd"]."</strong></dd></div></div>";
    	include("../common/footer.php");
    	die;
}
$ix=0;
$subc="ilbotv";
echo "<script>
		copies=new Array()
		";
$ix=-1;
$cn="";
$db="";
foreach ($contenido as $value){
	if (trim($value)!=""){
		$t=explode('|',$value);
		$ix=$ix+1;
		if ($ix==0){
			$cn=$t[0];
			$db=$t[1];
		}
		$vc="^".$subc[0].$t[3];
		if (trim($t[4])!="") $vc.="^".$subc[1].$t[4];
		if (trim($t[5])!="") $vc.="^".$subc[2].$t[5];
		if (trim($t[6])!="") $vc.="^".$subc[4].$t[6];
		if (trim($t[7])!="") $vc.="^".$subc[5].$t[7];
		echo "copies[$ix]=\"".$vc."\"\n";
	}
}
echo "</script>\n";

$archivo=$db_path."copies/pfts/".$_SESSION["lang"]."/copies_add.tit";
if (!file_exists($archivo))
	$archivo=$db_path."copies/pfts/".$lang_db."/copies_add.tit";
$fp=file($archivo);
$key=explode('_',$arrHttp["cn"]);
$key="CN_".$key[2];
echo "<strong><a href=javascript:Show('$key','$db')>".$msgstr["cn"].": $cn ($db)</a></strong><br>";
echo $msgstr["copy_type"]."<br>\n";
echo "<font color=darkred><strong>".$msgstr["copy_status"]."</strong>: </font>";
echo "<select name=copy_status>\n<option></option>";
foreach ($status as $var=>$value){
	echo "<option value=\"$var\">$value</option>\n";
}
echo "</select>";
echo "<table class=\"statTable\" cellspacing=5 cellpadding=5 width=100%>
		<tr>";
foreach ($fp as $linea) {
	$l=explode('|',$linea);
	foreach ($l as $lx)
		if (trim($lx)!="")echo "<th>$lx</th>";
	break;
}
echo "<tr><td colspan=6 bgcolor=#eeeeee><strong>".$msgstr["newitems"]."</strong></td>
<tr>";
reset($contenido);
foreach ($contenido as $value){
	if (trim($value)!=""){
		$t=explode("|",$value);
		$duplicate="";
		if (isset($t[3])){
			if (isset($dup[$t[3]])) {
				$duplicate="<strong><font color=red>".$msgstr["exitems"]."</font></strong>";
				echo "<input type=hidden name=duplicated value=Y>\n";
			}else{
				echo "<input type=hidden name=duplicated>\n";
			}
		}else{
			echo "<input type=hidden name=duplicated>\n";
		}

		echo "<tr><td>";
		if (isset($t[3]))
			echo $t[3]." $duplicate";
			$x=strpos($t[2],'^b');
			if ($x>0) echo "(".substr($t[2],$x+2).")";
		echo "</td><td>";
		if (isset($t[4]))
			echo $t[4];
		echo "</td><td>";
		if (isset($t[5]))
			echo $t[5];
		echo "</td><td align=center>";
		if (isset($t[6]))
			echo $t[6];
		echo "</td><td align=center>";
		if (isset($t[7]))
			echo $t[7];
		echo "</td>";
		echo "<td align=center><Select name=status>";

		$vc="";
		foreach ($fp_items as $value){
			$v=explode('|',$value);
			echo "<option value=".$v[0].">".$v[1]."</option>\n";
		}
		echo "</select></td>";
	}
}
echo "</table>
<input type=hidden name=status>
<input type=hidden name=duplicated>
<input type=hidden name=ValorCapturado>
<input type=hidden name=base value=\"".$arrHttp["base"]."\">
<input type=hidden name=cn value=$cn>
</div>
<div>
<div>
</form>";
include("../common/footer.php");
//=====================================

function Print_page(){
    Global $arrHttp,$msgstr,$error,$meta_encoding, $lang;
        $encabezado="";
        include("../common/header.php");
    ?>
    <body>
    <script>
    function Show(CN,db){
        msgwin=window.open("../dataentry/show.php?base="+db+"&Expresion="+CN,"show","width=600, height=600, resizable, scrollbars")
        msgwin.focus()
    }

    function Send(){
        ValorCapturado=""
        for (i=0;i<document.forma1.status.length-1;i++){
            if (document.forma1.duplicated[i].value==""){
                ind=document.forma1.status[i].selectedIndex
                tobj=document.forma1.status[i].options[ind].value
                copies[i]+="^o"+tobj
                ValorCapturado+=copies[i]+"\n"
            }

        }
        if (ValorCapturado==""){
            alert("<?php echo $msgstr["exitems"]?>")
            return
        }
        document.forma1.ValorCapturado.value=ValorCapturado
        document.forma1.submit()
    }
    </script>
    <?php
        echo "<form name=forma1 method=post action=loan_objects_update.php onsubmit='return false'>\n";
        if (isset($arrHttp["encabezado"])){
            include("../common/institutional_info.php");
            $encabezado="&encabezado=s";
        }
    ?>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["loanobjects"]?>
        </div>
        <div class="actions">
            <?php
            if (!isset($arrHttp["return"])){
                $ret="../common/inicio.php?reinicio=s$encabezado";
            }else{
                $ret=str_replace("|","?",$arrHttp["return"])."&encabezado=".$arrHttp["encabezado"];
            }
            ?>
<a href="javascript:top.toolbarEnabled=top.Menu('same')" class="button_browse" title='<?php echo $msgstr["cancel"]?>'>
    <i class="far fa-window-close bt-red"></i>&nbsp;<?php echo $msgstr["cancel"]?>
</a>
         
            <?php
            if ($error==""){

            $savescript="javascript:Send()";
            include "../common/inc_save.php"; 

            }
            ?>

        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php
    include "../common/inc_div-helper.php";
    ?>
    <div class="middle form">
        <div class="formContent">
    <?php
    if ($error!=""){
        echo "<h4>".$msgstr[$error]."</h4>";
    }
}

// ==================================================================================

function LeerFst($base){
global $tag_ctl,$pref_ctl,$arrHttp,$db_path,$AI,$lang_db,$msgstr,$error;
// GET THE FST TO FIND THE CONTROL NUMBER OF A BIBLIOGRAPHIC DATABASE
	$archivo=$db_path.$base."/data/".$base.".fst";
	if (!file_exists($archivo)){
		echo "missing file ".$base."/data/".$base.".fst";
		die;
	}
	$fp=file($archivo);
	$tag_ctl="";
	$pref_ctl="CN_";
	foreach ($fp as $linea){
		$linea=trim($linea);
		$ix=strpos($linea,"\"CN_\"");
		if ($ix===false){
			$ix=strpos($linea,'|CN_|');
		}
		if ($ix===false){
		}else{
			$ix=strpos($linea," ");
			$tag_ctl=trim(substr($linea,0,$ix));
			break;
		}
	}
	// Si no se ha definido el tag para el número de control en la fdt, se produce un error
	if ($tag_ctl==""){
		$error="missingctl";
	}
}


function LeerFdt($base){
global $tag_ctl,$pref_ctl,$arrHttp,$db_path,$lang_db,$msgstr;
// se lee la FDT para conseguir la etiqueta del campo donde se coloca la numeración automática y el prefijo con el cual se indiza el número de control

	$archivo=$db_path.$base."/def/".$_SESSION["lang"]."/".$base.".fdt";
	if (file_exists($archivo)){
		$fp=file($archivo);
	}else{
		$archivo=$db_path.$base."/def/".$lang_db."/".$base.".fdt";
		if (file_exists($archivo)){
			$fp=file($archivo);
		}else{
			echo "missing file ".$archivo;
		    die;
		 }
	}
	$tag_ctl="";
	$pref_ctl="";
	foreach ($fp as $linea){
		$l=explode('|',$linea);
		if ($l[0]=="AI" || $l[7]=="AI"){
			$tag_ctl=$l[1];
			$pref_ctl=$l[12];
		}
	}
	// Si no se ha definido el tag para el número de control en la fdt, se produce un error
	if ($tag_ctl=="" or $pref_ctl==""){
		echo "<h2>".$msgstr["missingctl"]."</h2>";
		die;
	}
}

?>
