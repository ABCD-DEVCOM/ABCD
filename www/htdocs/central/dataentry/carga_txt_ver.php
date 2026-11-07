<?php
/* Modifications
2021-03-08 fho4abcd Replaced helper code fragment by included file
2021-03-08 fho4abcd Improved html
2022-01-07 fho4abcd Close button, footer
*/
session_start();
if (!isset($_SESSION["permiso"])){
	header("Location: ../common/error_page.php") ;
}
include("../common/get_post.php");
include("../config.php");
$lang=$_SESSION["lang"];


include("../lang/admin.php");
include("../lang/soporte.php");
include("carga_txt_hdr.php");



//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";
include("../common/header.php");

function PresentarLeader($leader,$tc){
	$fp=file($leader);
	foreach ($fp as $value){
		$t=explode('|',$value);
		echo "<tr><td bgcolor=white>".$t[2]."</td>";
		echo "<td bgcolor=white>".$t[1]."</td>";
		$tag=$t[1];
		echo "<td bgcolor=white>";
		if (isset($tc[$tag][0]))
			echo $tc[$tag][0];

		echo "</td>";
		echo "<td bgcolor=white class=td>text</td>";
		echo "<td bgcolor=white class=td><input type=hidden name=subc></td>";
		echo "<td bgcolor=white class=td><input type=hidden name=editsubc></td>";
		echo "<td bgcolor=white class=td><input type=hidden name=occ></td>";
		echo "<td bgcolor=white class=td>";
		if (isset($tc[$tag][5])) echo $tc[$tag][5];
		echo "</td>";
	}
}
?>
<body>
<div class="sectionInfo">
    <div class="breadcrumb">
    <?php echo $msgstr["cnv_import"]." ".$msgstr["cnv_avis"]?>
    </div>
    <div class="actions">
    <?php include "../common/inc_close.php";?>
    </div>
    <div class="spacer">&#160;</div>
</div>
<?php include "../common/inc_div-helper.php" ?>
<div class="middle form">
<div class="formContent">

<?php
$rep="";
$separador="";
$fp=file($db_path.$arrHttp["base"]."/cnv/".$arrHttp["cnv"]);
foreach ($fp as $value){
	if ($rep==""){
		$separador=trim($value);
		$rep="S";
	}else{
		$a=explode('|',$value);
		$tc[$a[1]][0]=$a[0];
		$tc[$a[1]][1]=$a[1];
		$tc[$a[1]][2]=$a[2];
		$tc[$a[1]][3]=$a[3];
		$tc[$a[1]][4]=$a[4];
		$tc[$a[1]][5]=$a[5];
	}
}

echo "<p><font face=arial><b>".$msgstr["cnv_tab"].": </b>".$arrHttp["cnv"]."</font></p>\n";

$archivo=$db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/".$arrHttp["base"].".fdt";
if (!file_exists($archivo)) $archivo=$db_path.$arrHttp["base"]."/def/".$lang_db."/".$arrHttp["base"].".fdt";
$fp=file($archivo);

echo "<table border=0 bgcolor=#cccccc cellpadding=3 cellspacing=1 class=td>";
echo "<tr><td>".$msgstr["campo"]."</td><td>".$msgstr["tag"]."</td><td>".$msgstr["cnv_rotulo"]."</td><td>".$msgstr["tipo"]."</td><td>".$msgstr["subc"]."</td><td>".$msgstr["editsubc"]."</td><td>".$msgstr["osep"]."</td><td nowrap>".$msgstr["pftex"]."</td>";
$ix=-1;

foreach ($fp as $value){
	$t=explode('|',$value);
	if ($t[0]!='G'){
		if ($t[0]=="LDR"){
			PresentarLeader($db_path.$arrHttp["base"]."/def/".$_SESSION["lang"]."/leader.fdt",$tc);
			continue;
		}
		$ix=$ix+1;
		$tag=$t[1];
		if (isset($tc[$tag][0])){
			echo "<tr><td bgcolor=white>".$t[2]."</td>";
			echo "<td bgcolor=white>$tag</td>";
			echo "<td bgcolor=white nowrap>". $tc[$tag][0]."</td>";
			echo "<td bgcolor=white nowrap>";
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

			echo "</td><td bgcolor=white>";
			echo $tc[$tag][2];
			echo "</td><td bgcolor=white>";
			echo $tc[$tag][3];
			echo "</td><td bgcolor=white>";
			echo $tc[$tag][4];
			echo "</td><td bgcolor=white nowrap>";
			echo $tc[$tag][5];
			echo "</td>\n";
		}
	}
}
echo "<tr><td colspan=8 bgcolor=linen>";
if ($separador=='[HEADER]')
	echo "<input type=checkbox checked>".$msgstr["txtimp_header"]."</td></tr>";
elseif ($separador!='[TABS]')
	 echo $msgstr["cnv_sep"].": $separador</td></tr>";
else
	echo "<input type=checkbox checked>".$msgstr["delimited_tab"]."</td></tr>";
$arch="";
if (isset($arrHttp["cnv"])){
	$ixpos=strpos($arrHttp["cnv"],".");
	$arch=substr($arrHttp["cnv"],0,$ixpos);
}
?>
</table>
</div>
</div>
<?php
include "../common/footer.php";