<?php
/*
20230305 rogercgui Adds the variable $actparfolder;

*/

include("tope_config.php");
$wiki_help="OPAC-ABCD_configuraci%C3%B3n#Bases_de_datos_disponibles";
include "../../common/inc_div-helper.php";
?>


<div class="middle form">
	<h3><?php echo $msgstr["databases"]?></h3>
	<div class="formContent">

<div id="page">
    <?php

//foreach ($_REQUEST as $var=>$value) echo "$var=>$value<br>";  die;
$def_base=array();
$eliminar=array();
if (isset($_REQUEST["Opcion"]) and $_REQUEST["Opcion"]=="Actualizar"){
	foreach ($_REQUEST as $var=>$value){
		if (trim($value)!=""){
			$code=explode("_",$var);
			switch ($code[0]){
				case ("conf"):
					switch ($code[1]){
						case "lc":
							if (!isset($cod_base[$code[2]])){
								$cod_base[$code[2]]=$value;
							}
							break;
						case "ln":
							if (!isset($nom_base[$code[2]])){
								$nom_base[$code[2]]=$value;
							}
							break;
						case "def":
							if (!isset($def_base[$code[2]])){
								$def_base[$code[2]]=$value;
							}
							break;

					}
					break;
				case "langdb":
					if (!isset($lang_base[$code[2]])){
						$lang_base[$code[2]]=$value;
					}else{
						$lang_base[$code[2]].='|'.$value;
					}
					break;
			}
		}
	}
    foreach ($cod_base as $key=>$value){
    	if (!is_dir($db_path.$value)){
	 		echo "Database:$value<br><font color=red size=3><strong>".$msgstr["missing_folder"]." $value ".$msgstr["in"]." $db_path</strong></font><br>";
	 	    $eliminar[$key]="S";
	 	}
	 	if (!file_exists($db_path.$actparfolder."/$value.par")){
	 		echo "Database:$value<br><font color=red size=3><strong>".$msgstr["missing"]." $value.par</strong></font><br>";
            $eliminar[$key]="S";
	 	}
    	if (isset($eliminar[$key]) and $eliminar[$key]=="S")  echo "<font color=red size=3><strong>$value ".$msgstr["discarded"]."</strong></font><br>";
    }
	if (isset($cod_base)){
	    foreach ($cod_base as $key=>$value){
            if (isset($eliminar[$key]) and $eliminar[$key]=="S") continue;
			$file_db=$value.".def";
			$folfer_db=$db_path.$value."/opac/".$_REQUEST["lang"]."/";

			if (!file_exists($folfer_db)) {
    			mkdir($folfer_db, 0777, true);
			}

			$fout=fopen($folfer_db.$file_db,"w");
			
			if (!isset($def_base["$key"]))  $def_base["$key"]="";
				fwrite($fout, $def_base["$key"]);
				echo "<h3>".$folfer_db." <span class='color-green'>".$msgstr["updated"]."</span></h3><br>";
			fclose($fout);
		}
	}
	if (isset($lang_base)){
	    foreach ($cod_base as $key=>$value){
	    	if (isset($eliminar[$key]) and $eliminar[$key]=="S") continue;
	    	if (isset($lang_base[$key])){
				$fout=fopen($db_path."opac_conf/".$_REQUEST["lang"]."/".$value.".lang","w");
				fwrite($fout, $lang_base["$key"]);
				fclose($fout);
				echo "<h3>".$_REQUEST["lang"]."/$value.lang"." ".$msgstr["updated"]."</h3><br>";
			}
		}
	}
	$folder_dat=$db_path."opac_conf/".$_REQUEST["lang"]."/bases.dat";
    $fout=fopen($folder_dat,"w");
	foreach ($cod_base as $key=>$value){
		if (isset($eliminar[$key]) and $eliminar[$key]=="S") continue;
		fwrite($fout,$value."|".$nom_base[$key]."\n");
	}
	fclose($fout);
	echo "<h3>".$folder_dat." <span class='color-green'>".$msgstr["updated"]."</span></h3>";
	die;
}
?>

<form name=actualizar method=post>
<?php
$alpha=array();
if (is_dir($db_path."opac_conf/alpha/$charset")){
	$handle=opendir($db_path."opac_conf/alpha/$charset");
	while (false !== ($entry = readdir($handle))) {
		if (!is_file($db_path."opac_conf/alpha/$charset/$entry")) continue;
		$alpha[$entry]=$entry;
	}
}


$ix=0;

echo "<table>";
echo "<tr><th>".$msgstr["db_name"]."</th><th>".$msgstr["db"]."</th><th>".$msgstr["db_desc"]."</th>";
if (isset($_REQUEST["conf_level"]) and $_REQUEST["conf_level"]=="advanced" and count($alpha)>0){
	echo "<th>".$msgstr["avail_db_lang"]."</th>";
}
echo "</tr>";
if (file_exists($db_path."opac_conf/".$_REQUEST["lang"]."/bases.dat")){
	$fp=file($db_path."opac_conf/".$_REQUEST["lang"]."/bases.dat");
	foreach ($fp as $value){
		if (trim($value)!=""){
			$l=explode('|',$value);
			$ix=$ix+1;
			$base_def="";
			if (file_exists($db_path."opac_conf/".$_REQUEST["lang"]."/".$l[0].".def")){
				$fp=file($db_path."opac_conf/".$_REQUEST["lang"]."/".$l[0].".def");
				$base_def=implode(" ",$fp);
			}

			echo "<tr><td><input type=text name=conf_lc_".$ix." size=5 value=\"".trim($l[0])."\"></td>";
			echo "<td><input type=text name=conf_ln_".$ix." size=20 value=\"".trim($l[1])."\"></td>";
			echo "<td><input type=text name=conf_def_".$ix." size=80 value=\"".$base_def."\"></td>";
			if (isset($_REQUEST["base"]) and $_REQUEST["base"]=="1"){
				echo "<td>";
				$ix_lang=0;
				echo "<table>";
				echo "<tr>";
				$langdb=array();
				if (file_exists($db_path."opac_conf/".$_REQUEST["lang"]."/".$l[0].".lang")){
					$fp_lang=file($db_path."opac_conf/".$_REQUEST["lang"]."/".$l[0].".lang");
					foreach ($fp_lang as $value_lang){
						if (trim($value_lang)!=""){
							$fll=explode('|',$value_lang);
							foreach ($fll as $xfll){
								$xfll=trim($xfll);
								$langdb[$xfll]=$xfll;
							}
						}
					}

				}
				foreach ($alpha as $value){
					$ix_lang=$ix_lang+1;
					echo "<td>";
					$ix_00=strrpos($value,".");
					$value=substr($value,0,$ix_00);
					echo "<input type=checkbox name=langdb_".$value."_$ix value=\"$value\"";
					if (isset($langdb[$value])) echo " checked";
					echo ">$value";
					echo "</td>";
					if ($ix_lang>3){
						$ix_lang=0;
						echo "</tr><tr>";
					}
				}
				echo "</table></td>";
				echo "</td>";
			}
			echo "</tr>";
		}
	}
}
if ($ix==0)
	$tope=5;
else
	$tope=$ix+4;
$ix=$ix+1;
for ($i=$ix;$i<$tope;$i++){

	echo "<tr><td><input type=text name=conf_lc_".$i." size=5 value=\"\"></td>";
	echo "<td><input type=text name=conf_ln_".$i." size=20 value=\"\"></td>";
	echo "<td><input type=text name=conf_def_".$i." size=80 value=\"\"></td>";
	if (isset($_REQUEST["conf_level"]) and $_REQUEST["conf_level"]=="advanced"){
		echo "<td>";
		$ix_lang=0;
		echo "<table>";
		echo "<tr>";
		foreach ($alpha as $value){
			$ix_lang=$ix_lang+1;
			echo "<tD>";
			$ix_00=strrpos($value,".");
			$value=substr($value,0,$ix_00);
			echo "<input type=checkbox name=langdb_".$value."_$ix value=\"$value\">$value";
			echo "</td>";
			if ($ix_lang>3){
				$ix_lang=0;
				echo "</tr><tr>";
			}
		}
		echo "</table></td>";
	}
	echo "</tr>";
}
?>

</table>

<input type="submit" class="bt-green" value="<?php echo $msgstr["save"];?>" >

<input type="hidden" name="lang" value="<?php echo $lang;?>" >
<input type="hidden" name="Opcion" value="Actualizar" >

<?php
if (isset($_REQUEST["conf_level"])){
	echo "<input type=hidden name=conf_level value=".$_REQUEST["conf_level"].">\n";
}
?>
</form>
</div>
</div>
</div>
</div>
<?php
include ("../../common/footer.php");
?>
