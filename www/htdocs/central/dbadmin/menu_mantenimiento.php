<?php
/* Modifications
20210314 fho4abcd Replaced helper code fragment by included file
20210314 fho4abcd html move body and remove win.close + sanitize html
20210314 fho4abcd Replaced dbinfo code by included file
20210324 fho4abcd Catch error after database deletion, display also long name of the database
20210415 fho4abcd use charset from config.php
20210803 fho4abcd added language file
20211214 fho4abcd Backbutton by included file
20220203 fho4abcd better reaction if no database is given
20220613 fho4abcd Removed unused frame
*/
session_start();

$Permiso=$_SESSION["permiso"];
if (!isset($_SESSION["lang"]))  $_SESSION["lang"]="en";
include ("../common/get_post.php");

if (!isset($arrHttp["base"])) $arrHttp["base"]="";

if (strpos($arrHttp["base"],"|")===false){

}   else{
		$ix=explode('|',$arrHttp["base"]);
		$arrHttp["base"]=$ix[0];
}
$db=$arrHttp["base"];
include ("../config.php");
$lang=$_SESSION["lang"];
include("../lang/admin.php");
include("../lang/soporte.php");
include("../lang/dbadmin.php");
include("../lang/importdoc.php");

if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) and
    !isset($_SESSION["permiso"]["CENTRAL_DBUTILS"]) and
    !isset($_SESSION["permiso"][$db."_CENTRAL_ALL"]) and
    !isset($_SESSION["permiso"][$db."_CENTRAL_DBUTILS"])
    ){
	echo "<script>
	      alert('".$msgstr["invalidright"]."')
          history.back();
          </script>";
    die;
}


//SEE IF THE DATABASE IS LINKED TO COPIES
$copies="N";
$fp=file($db_path."bases.dat");
foreach ($fp as $value){
	$value=trim($value);
	$x=explode("|",$value);
	if ($x[0]==$arrHttp["base"]){
		if (isset($x[2]) and $x[2]=="Y"){
			$copies="Y";
		}
		break;
	}
}
//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");
?>
<body>
<script language="JavaScript" type="text/javascript" src=../dataentry/js/lr_trim.js></script>
<?php
include("../common/institutional_info.php");
$encabezado="&encabezado=s";
?>
<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr["maintenance"]. ": " . $arrHttp["base"]; ?>
    </div>
    <div class="actions">
    <?php include "../common/inc_back.php";?>
    </div>
   <div class="spacer">&#160;</div> 
</div>

<?php
include "../common/inc_div-helper.php";

// Display menu bar
include("menu_bar.php");
?>

<div class="middle form">
	<div class="formContent" style="min-height:300px;">
	<div class="log_base">
	<?php
	// Get info about the current database from the database(if there is a database)
	if ( isset($arrHttp["base"]) and $arrHttp["base"]!="" and file_exists($db_path.$arrHttp["base"])) {
	    include ("../common/inc_get-dbinfo.php");
	    include "../common/inc_get-dblongname.php";
	    // Display info about current database
	    echo "<span><b>".$msgstr["bd"]."</b>: ".$arrHttp["base"]." (".$arrHttp["dblongname"].")</span>";
	} else {
	    if ( isset($arrHttp["base"])){
	        echo "<span>".$arrHttp["base"]."</span>";
	    }
	    echo "<span>".$msgstr["dbnex"]."<br></span>";
	    $arrHttp["MAXMFN"]="?"."</span>";
	}
	echo "<span>".$charset."</span>";
	echo "<span><b>".$msgstr["maxmfn"]."</b>: ".$arrHttp["MAXMFN"]."</span>";

	if (isset($arrHttp["BD"]) and $arrHttp["BD"]=="N")
		echo "<span>".$msgstr["database"]." ".$msgstr["ne"]."</span>";

	if (isset($arrHttp["IF"]) and $arrHttp["IF"]=="N")
		echo "<span>".$msgstr["if"]." ".$msgstr["ne"]."</span>";

	if (isset($arrHttp["EXCLUSIVEWRITELOCK"]) and $arrHttp["EXCLUSIVEWRITELOCK"]!=0) {
		echo "<br><span style='color:red;'><strong>".$msgstr["database"]." ".$msgstr["exwritelock"]." =".$arrHttp["EXCLUSIVEWRITELOCK"]." ".$msgstr["contactdbadm"]."
		<script>top.lock_db='Y'</script></strong></span>";

	}

	if ($wxisUrl!=""){
		echo "<br><span><b>CISIS version:</b>".$wxisUrl."</span>";
	}else{
		$ix=strpos($Wxis,"cgi-bin");
		$wxs=substr($Wxis,$ix);
	    echo "<span><b>CISIS version: ".$wxs."</span>";
	}
	?>
	</div>


	</div> <!--./formContent-->
</div> <!--./middle form-->


</div>
<?php include("../common/footer.php");?>