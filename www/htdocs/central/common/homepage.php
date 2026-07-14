<?php
/* Modifications
2021-02-25 fho4abcd Conformance: Moved <body>,</body>,<html>, deleted <div>. Non-functional readability of html
2021-03-02 fho4abcd Replaced helper code fragment by included file
2021-04-15 fho4abcd show charsets like institutional_info.php + refresh after change of database
2021-04-30 fho4abcd Language selection menu equal to menubases.php+ moved language selection to included file
2021-05-04 fho4abcd Language selection: Enable selection of first menu item
2022-01-23 rogercgui Option "CABCD" points to settings
2022-06-13 fho4abcd Removed unused Modulo + html in correct order + clean code
2022-06-30 fho4abcd Add backtoscript to explore databases folder
*/
//PARA ELIMINAR LAS VARIABLES DE SESSION DEL DIRTREE
unset($_SESSION["root_base"]);
unset($_SESSION["dir_base"]);
unset($_SESSION["Folder_Name"]);
unset($_SESSION["Folder_Type"]);
unset($_SESSION["Opened_Folder"]);
unset($_SESSION["Father"]);
unset($_SESSION["Numfile"]);
unset($_SESSION["File_Date"]);
unset($_SESSION["Last_Node"]);
unset($_SESSION["Level_Tree"]);
unset($_SESSION["Levels_Fixed_Path"]);
unset($_SESSION["Numbytes"]);
unset($_SESSION["Children_Files"]);
unset($_SESSION["Children_Subdirs"]);
unset($_SESSION["Maxfoldersize"]);
unset($_SESSION["Last_Level_Node"]);
unset($_SESSION["Total_Time"]);
unset($_SESSION["Server_Path"]);
$Permiso=$_SESSION["permiso"];
$modulo_anterior="";
if (isset($_SESSION["MODULO"]))
	$modulo_anterior=$_SESSION["MODULO"];

// next statement may switch the current module
if (isset($arrHttp["modulo"])) {
	$_SESSION["MODULO"]=$arrHttp["modulo"];
}
$central="";
$circulation="";
$acquisitions="";
$ixcentral=0;
foreach ($_SESSION["permiso"] as $key=>$value){
	$p=explode("_",$key);
	if (isset($p[1]) and $p[1]=="CENTRAL"){
		$central="Y";
		$ixcentral=$ixcentral+1;
	}
	if (substr($key,0,8)=="CENTRAL_")  	{
		$central="Y";
		$ixcentral=$ixcentral+1;
	}
	if (substr($key,0,4)=="ADM_"){
		$central="Y";
		$ixcentral=$ixcentral+1;
	}
	if (substr($key,0,5)=="CIRC_")  	$circulation="Y";
	if (substr($key,0,4)=="ACQ_")  		$acquisitions="Y";
}
// Se determina el nombre de la página de ayuda a mostrar
if (!isset($_SESSION["MODULO"])) {
	if ($central=="Y" and $ixcentral>0) {
		$arrHttp["modulo"]="catalog";
	}else{
		if ($circulation=="Y"){
			$arrHttp["modulo"]="loan";
		}else{
			$arrHttp["modulo"]="acquisitions";
		}
	}
}else{
	$arrHttp["modulo"]=$_SESSION["MODULO"];
}
switch ($arrHttp["modulo"]){
	case "catalog":
		$ayuda="homepage.html";
		$module_name=$msgstr["catalogacion"];
		$_SESSION["MODULO"]="catalog";
		break;
	case "acquisitions":
		$ayuda="acquisitions/homepage.html";
		$module_name=$msgstr["acquisitions"];
		$_SESSION["MODULO"]="acquisitions";
		break;
	case "loan":
		$ayuda="circulation/homepage.html";
		$module_name=$msgstr["loantit"];
		$_SESSION["MODULO"]="loan";
}
if (file_exists($db_path."logtrans/data/logtrans.mst")){
	if ($_SESSION["MODULO"]!="loan" and $modulo_anterior=="loan"){
		include("../circulation/grabar_log.php");
		$datos_trans["operador"]=$_SESSION["login"];
		GrabarLog("Q",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path);
	}else{
		if ($_SESSION["MODULO"]=="loan" and $modulo_anterior!="loan"){
			include("../circulation/grabar_log.php");
			$datos_trans["operador"]=$_SESSION["login"];
			GrabarLog("P",$datos_trans,$Wxis,$xWxis,$wxisUrl,$db_path);
		}
	}
}
include("header.php");
?>
<body>
<script>
function doReload(selectvalue){
    /* note that selectvalue has format <base>|adm|<base_prompt>*/
    valuearr=selectvalue.split('|')
    base=valuearr[0]
    base="?base="+base;
	document.location = '../common/inicio.php' + base;
}
function ActivarModulo(Url,base){
	if (base=="Y"){
		ix=document.admin.base.selectedIndex
		if (ix<1){
		  	alert("<?php echo $msgstr["seldb"]?>")
		   	return
		}
		base=document.admin.base.options[ix].value
		b=base.split('|')
		base=b[0]
		base="?base="+base;

	}else{
		base="";
	}
	Url="../"+Url+base
	top.location.href=Url
}
function CambiarBaseAdministrador(Modulo){
    db=""
    if (Modulo!="traducir"){
        ix=document.admin.base.selectedIndex
        if (ix<1){
            alert("<?php echo $msgstr["seldb"]?>")
            return
        }
        db=document.admin.base.options[ix].value
        b=db.split('|')
        db=b[0]
    }
    switch(Modulo){
        case 'table':
            document.admin.action="../dataentry/browse.php"
            break
        case "resetautoinc":
            if (db+"_CENTRAL_RESETLCN" in perms || "CENTRAL_RESETLCN" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms){
                document.admin.action="../dbadmin/resetautoinc.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "toolbar":
            document.admin.action="../dataentry/inicio_main.php";
            break;
        case "utilitarios":

            if (db+"_CENTRAL_DBUTILS" in perms || "CENTRAL_DBUTILS" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms ){
                document.admin.action="../dbadmin/menu_mantenimiento.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "estructuras":
            if (db+"_CENTRAL_MODIFYDEF" in perms || "CENTRAL_MODIFYDEF" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms){
                document.admin.action="../dbadmin/menu_modificardb.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "reportes":
            if (db+"_CENTRAL_PREC" in perms || "CENTRAL_PREC" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms){
                document.admin.action="../dbadmin/pft.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "traducir":
            if (db+"_CENTRAL_TRANSLATE" in perms || "CENTRAL_TRANSLATE" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms){
                document.admin.action="../dbadmin/menu_traducir.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "stats":
            if (db+"_CENTRAL_STATGEN" in perms || "CENTRAL_STATGEN" in perms || "CENTRAL_STATGEN" in perms || "CENTRAL_ALL" in perms || db+"_CENTRAL_ALL" in perms){
                document.admin.action="../statistics/tables_generate.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
        case "z3950":
            if (db+"_CENTRAL_Z3950CONF" in perms || "CENTRAL_Z3950CONF" in perms || "CENTRAL_ALL" in perms || db+"CENTRAL_ALL" in perms){
                document.admin.action="../dbadmin/z3950_conf.php";
            }else{
                alert("<?php echo $msgstr["invalidright"];?>")
                return;
            }
            break;
    }
    document.admin.submit();
}
function FuncionesAdministracion(Accion){
    switch (Accion){
        case "CBD":
            document.admFrm.action="../dbadmin/menu_creardb.php"
            document.admFrm.encabezado.value="s"
            break;
        case "AUSR":
            document.admFrm.action="../dbadmin/users_adm.php"
            document.admFrm.encabezado.value="s"
            document.admFrm.base.value="acces"
            document.admFrm.cipar.value="acces.par"
            break;
        case "RNU":
            document.admFrm.action="../dbadmin/reset_inventory_number.php"
            document.admFrm.encabezado.value="s"
            break;
        case "CABCD":
            document.admFrm.action="../settings/conf_abcd.php"
            document.admFrm.Opcion.value="abcd_def"
            break;
        case "DIRTREE":
            document.admFrm.action="../dbadmin/dirtree.php?backtoscript=/central/common/inicio.php"
            document.admFrm.encabezado.value="s"
            document.admFrm.retorno.value="inicio"
            break;
    }
    document.admFrm.submit()
}
var perms= new Array();
<?php
foreach ($_SESSION["permiso"] as $key=>$value){
	echo "perms['$key']='$value';\n";
}
?>
</script>
<?php
require_once ('institutional_info.php');
?>
<div class="sectionInfo">
	<div class="breadcrumb">
		<strong><?php echo $msgstr["inicio"]." - $module_name"?></strong>
	</div>
	<div class="actions">
      &nbsp;
	</div>
	<div class="spacer">&#160;</div>
</div>
<?php $wiki_help="";include "inc_div-helper.php"?>
<div class="middle homepage">
    <form name=admFrm method=post accept-charset=utf-8>
        <input type=hidden name=Opcion>
        <input type=hidden name=encabezado>
        <input type=hidden name=base>
        <input type=hidden name=retorno>
        <input type=hidden name=cipar>
    </form>
<?php
$Permiso=$_SESSION["permiso"];
switch ($_SESSION["MODULO"]){
	case "catalog":
		AdministratorMenu();
		break;
	case "loan":
		MenuLoanAdministrator();
		break;
	case "acquisitions":
		MenuAcquisitionsAdministrator();
		break;
}
echo "</div>";
include("footer.php");

///---------------------------------------------------------------

function AdministratorMenu(){
global $msgstr,$db_path,$arrHttp,$Permiso,$dirtree,$def;
	$_SESSION["MODULO"]="catalog";

if (isset($arrHttp['base'])) {
	$baseSelect = $arrHttp['base'];
} else {
	$baseSelect = "";
}
?>
    <div class="mainBox" >
        <div class="boxContent catalogSection">
            <div class="sectionTitle">
            <img src="../../assets/svg/catalog/ic_fluent_database_24_regular.svg">
                <h1><?php echo $msgstr["database"]?>  <?php echo $baseSelect;?></h1>
            </div>
            <div class="sectionButtons">	
<?php
if (isset($def["MODULOS"])){
	if (isset($def["MODULOS"]["SELBASE"])  ){
		$base_sel=$def["MODULOS"]["SELBASE"];
	}else{
		$base_sel="";
	}
    ?>
	<a href="javascript:ActivarModulo('<?php echo $def["MODULOS"]["SCRIPT"]."','$base_sel";?>')" class="menuButton <?php echo $def["MODULOS"]["BUTTON"]?>">
        <span><strong><?php echo $def["MODULOS"]["TITLE"]?></strong></span>
	</a>
<?php
}
?>
				<a href="javascript:CambiarBaseAdministrador('toolbar')" class="menuButton DataEntry ">
					<span><strong><?php echo $msgstr["dataentry"]?></strong></span>
				</a>
				<a href="javascript:CambiarBaseAdministrador('stats')" class="menuButton CatstatButton">
					<span><strong><?php echo $msgstr["statistics"]?></strong></span>
				</a>
				<a href="javascript:CambiarBaseAdministrador('reportes')" class="menuButton reportButton">
					<span><strong><?php echo $msgstr["reports"]?></strong></span>
				</a>
				<a href="javascript:CambiarBaseAdministrador('estructuras')" class="menuButton update_databaseButton">
					<span><strong><?php echo $msgstr["updbdef"]?></strong></span>
				</a>
				<a href="javascript:CambiarBaseAdministrador('utilitarios')" class="menuButton utilsButton">
					<span><strong><?php echo $msgstr["maintenance"]?></strong></span>
				</a>
				<a href="javascript:CambiarBaseAdministrador('z3950')"  class="menuButton z3950Button">
					<span><strong><?php echo $msgstr["z3950"]?></strong></span>
				</a>
			</div>
			<div class="spacer">&#160;</div>
        </div>
	</div>
<?php

if (isset($Permiso["CENTRAL_ALL"])  or isset($Permiso["CENTRAL_CRDB"])  or isset($Permiso["CENTRAL_USRADM"])
  or isset($Permiso["CENTRAL_RESETLIN"])  or isset($Permiso["CENTRAL_TRANSLATE"])  or isset($Permiso["CENTRAL_EXDBDIR"]))
{
?>
    <div class="mainBox" >
        <div class="boxContent catalogSection">
            <div class="sectionTitle">
            <img src="../../assets/svg/catalog/ic_fluent_settings_24_regular.svg">
                <h1><?php echo $msgstr["admtit"]?></h1>
            </div>
            <div class="sectionButtons">
<?php
if (isset($Permiso["CENTRAL_ALL"])  or isset($Permiso["CENTRAL_CRDB"]) or isset($Permiso["ADM_CRDB"])){
?>
                <a href="javascript:FuncionesAdministracion('CBD')" class="menuButton databaseButton">
                <span><strong><?php echo $msgstr["createdb"]?></strong></span></a>
<?php
}
if (isset($Permiso["CENTRAL_ALL"])  or isset($Permiso["CENTRAL_USRADM"]) or isset($Permiso["ADM_USRADM"])){
?>
				<a href="../dataentry/browse.php?showdeleted=yes&encabezado=s&base=acces&cipar=acces.par" class="menuButton CataluserButton">
					<span><strong><?php echo $msgstr["usuarios"]?></strong></span>
				</a>
<?php
}
if (isset($Permiso["CENTRAL_ALL"])  or isset($Permiso["CENTRAL_RESETLIN"])){
?>
				<a href="javascript:FuncionesAdministracion('RNU')" class="menuButton resetButton">
					<span><strong><?php echo $msgstr["resetinv"]?></strong></span>
				</a>
<?php
}
if (isset($Permiso["CENTRAL_ALL"])  or isset($Permiso["CENTRAL_TRANSLATE"])){
?>
				<a href="javascript:CambiarBaseAdministrador('traducir')" class="menuButton translateButton">
					<span><strong><?php echo $msgstr["translate"]?></strong></span>
				</a>
<?php
}
if ($_SESSION["profile"]=="adm"){
?>
				<a href="javascript:FuncionesAdministracion('CABCD')" class="menuButton utilsButton">
					<span><strong><?php echo $msgstr["configure"]. " ABCD"?></strong></span>
				</a>
<?php
}

if ($dirtree==1 or $dirtree=="Y"){
	if ($_SESSION["profile"]=="adm"){
?>
				<a href="javascript:FuncionesAdministracion('DIRTREE')" class="menuButton CatexploreButton">
					<span><strong><?php echo $msgstr["expbases"]?></strong></span>
				</a>
<?php }

            // HOOK: Plugins can inject central_menu items here
            echo abcd_run_hook('central_menu', '');
}?>
            </div>
            <div class="spacer">&#160;</div>
        </div>
    </div>
<?php
	}
}
// end function Administrador



function MenuAcquisitionsAdministrator(){
	include("menuacquisitions.php");
}

function MenuLoanAdministrator(){
   include("menucirculation.php");
}
?>
