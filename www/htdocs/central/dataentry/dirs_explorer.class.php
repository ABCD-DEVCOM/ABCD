<?php
/* Modification
20210315 fho4abcd The destination form no longer fixed to "upload" but specified by variable $targetForm.
20210912 fho4abcd Improved handling of targetForm, replace help button by div-helper, sanitize html
20220213 fho4abcd Don't show new folder while exploring
*/
//*****************************************************************
//** Author: Marius Buivydas                                     **
//** E-mail: mairo@takas.lt                                      **
//** Created: 2003-09-07                                         **
//** Last update: 2004-01-30                                     **
//**                                                             **
//**                                                             **
//**  This class is meant to browse a given directory.           **
//**  It allows you to view or download the directory files      **
//**  or other files from sub-directories                        **
//**                                                             **
//*****************************************************************



class php_dirs_explorer {

var $types;
//array, which keys are the name of file (icon)
//and value - extensions which suit this icon

var $un_icon;
//this variable saves name of unknown file type icon
var $dir_icon;
//this variable saves name of directory icon
var $root_dir;
//this variable saves the path of directory which will be seen as root directory.
var $icons_dir;
//this variable saves the path of icons directory (where icons will be kept).


Function Set($varname,$value) {
        $this->$varname=$value;
}


function get_file_type($filename) {
    $filename=strtolower($filename);// file name is changed to lowercase
   // ereg( ".*\.([a-zA-z0-9]{0,5})$", $filename, $regs ); //gets extension of file
    $f_ext = pathinfo($filename, PATHINFO_EXTENSION);
    $cont_type="";
	switch ($f_ext) //known file types
	 {
	   case "jpg": $cont_type="image/jpeg"; break;
	   case "jpeg": $cont_type="image/jpeg"; break;
	   case "gif": $cont_type="image/gif"; break;
	   case "png": $cont_type="image/png"; break;
	   case "txt": $cont_type="text/plain"; break;
       case "html": $cont_type="text/html"; break;
       case "htm": $cont_type="text/html"; break;
       case "doc": $cont_type="application/msword";break;
       case "exe": $cont_type="application/octet-stream";break;
       case "pdf": $cont_type="application/pdf";break;
       case "ai": $cont_type="application/postscript";break;
       case "eps": $cont_type="application/postscript";break;
       case "ps": $cont_type="application/postscript";break;
       case "xls": $cont_type="application/vnd.ms-excel";break;
       case "ppt": $cont_type="application/vnd.ms-powerpoint";break;
       case "zip": $cont_type="application/zip";break;
       case "mid": $cont_type="audio/midi";break;
       case "kar": $cont_type="audio/midi";break;
       case "mp3": $cont_type="audio/mpeg";break;
       case "wav": $cont_type="audio/x-wav";break;
       case "bmp": $cont_type="image/bmp";break;
       case "tiff": $cont_type="image/tiff";break;
       case "tif": $cont_type="image/tiff";break;
       case "asc": $cont_type="text/plain";break;
       case "rtf": $cont_type="text/rtf";break;
       case "mpeg": $cont_type="video/mpeg";break;
       case "mpg": $cont_type="video/mpeg";break;
       case "mpe": $cont_type="video/mpeg";break;
       case "avi": $cont_type="video/x-msvideo";break;
       case "mst": $cont_type="text/plain";break;

	 }

    foreach ($this->types as $k => $v) {
        if (in_array($f_ext, $v)) {
            return array($k,$cont_type); //returns the type and icon of recognized file
            break;
        }
    }
    return array($this->un_icon,$cont_type); //returns the type and icon of unrecognized file
}


function show_dirs($Opcion,$db_path,$tag) {
global $arrHttp,$img_path,$msgstr,$targetForm;
	$root=false;
	if (isset($_GET['source']))
		$source=$_GET['source']; // it can be directory or file on which is clicked
	else
		$source="";
	if (isset($_GET["type"]))
		$type=$_GET['type']; //this is the type of object on which was clicked (it can be file or dir (directory)
	else
		$type ="";
	if (isset($_GET['path']))
		$path=$_GET['path']; //this is the virtual path of directory which we have left.
	else
		$path="";
	if (isset($_GET['kryptis']))
		$kryptis=$_GET['kryptis']; //this is direction of action. It can be pirmyn (forward) if we open directory and other value this variable can be atgal (back) if we press on .. (we will go back from directory).
	else
		$kryptis="";
	if (isset($_GET['cont_type']))
		$cont_type=$_GET['cont_type']; //type of the file
	else
		$cont_type="";
	$source=stripslashes($source);
	$path=stripslashes($path);
	if ($arrHttp["Opcion"]!="mostrar" && $arrHttp["Opcion"]!="seleccionar"){
		if ($source!="..")
			echo "\n<strong>".$path.$source."</strong>\n";
        echo "<br>";
		echo "<a href=javascript:CrearCarpeta()>".$msgstr["new_folder"]."</a>";
	}
	if ($type=="file"){
	   if ($cont_type=="")
	     {
	       header("Content-type: application/force-download");
	       header("Content-Disposition: attachment; filename=$source");
		 }
	   else
	     {
	       header("Content-type: $cont_type");
	      // @readfile($this->root_dir.$path.$source);
	       $fp = fopen($this->root_dir.$path.$source, 'r');
	       fpassthru($fp);
		   die();
	     }
	}
	if (preg_match("/\/\.\.\//",$path)) // protection, that it would be impossible to explore other (not allowed) directories
	 {
	   $path="/";
	 }
	if ($source=="")
	  {
	    $path="/";
	    $root=true;
	  }
	else
	  {

	    if ($type=="dir") //(type==directory)
		  {
	        if ($kryptis=="pirmyn")//(direction==forward) clicked on directory
			  {
	            $path=$path.$source."/";
	          }
	        elseif($kryptis=="atgal") //(direction == "back") clicked on ..
			  {
	            preg_match_all("/^\/(.+)/", $path, $out);// strips first char "/"
	            if (isset($out[1][0])) $path=$out[1][0]; else {$path="/";$root=true;}
	            preg_match_all("/(.+)\/.+\/$/", $path, $out);// strips from the path the last directory
	            
	            if (isset($out[1][0])) {
				if ($out[1][0]!="")
	              {$path="/".$out[1][0]."/";}
	            else
	              {
				   $path="/".$out[1][0];
	  			   $root=true;
				  }
				 }
			  }
	      }
	  }
	  if (isset($arrHttp["root"])) $path=$arrHttp["root"];
  	$this->Set("root_dir",$this->root_dir.$path);
	  if (is_dir($this->root_dir))
	     {
	       if ($dh = opendir($this->root_dir))
		     {
	           while (($file = readdir($dh)) !== false)
			     {
				    if (filetype($this->root_dir .$file)=="dir")
					  {$turinys["tipas"][]="dir";}//array["type"][]="directory"
				    else
					  {$turinys["tipas"][]="file";}//array["type"][]="file"
				    $turinys["pavadinimas"][]=$file;//array["name"][]=$file
	             }
	           closedir($dh);
	       	   array_multisort($turinys["tipas"], SORT_ASC,$turinys["pavadinimas"]);
			   if (!$root)
			     {$i=1;
			     Encabezamiento();}
			   else
			     {$i=2;
			     Encabezamiento();
			   }
				// CORREÇÃO: Avisa visualmente quando o diretório estiver vazio
				if (count($turinys["tipas"]) <= 2 && $root) {
					echo "<div style='text-align:center; padding: 40px; color:#999;'>";
					echo "<i class='far fa-folder-open fa-3x'></i><br><br>";
					echo "O diretório selecionado está vazio.<br><small>Faça o upload de arquivos primeiro.</small></div>";
				}

	           for ($i;$i<count($turinys["tipas"]);$i++)
			     {
			       if ($turinys["tipas"][$i]=="dir")
				     { 
					   if ($Opcion=="explorar" and $turinys["pavadinimas"][$i]!=".."){
					   		$sel_dir=$this->root_dir;
					   		$sel_dir=str_replace($img_path,"",$sel_dir);
					   		$sel_dir.=$turinys["pavadinimas"][$i];
					   		$sel_dir=str_replace("//","/",$sel_dir);
					   		echo "\n<input type=radio name=sel value='".$turinys["pavadinimas"][$i]."' onclick=\"window.opener.document.".$targetForm.".storein.value='".$sel_dir."';window.opener.focus();self.close()\">";
						}
					   	echo "\n&nbsp;<img style='position:relative;top:4px' src=\"".$this->icons_dir.$this->dir_icon."\">";
                        if ($i==1 and $turinys["pavadinimas"][$i]=="..") {
                            $newkrypt="atgal";
                        } else {
                            $newkrypt="pirmyn";
                        }
                        ?>
&nbsp;
<a href="?source=<?php echo $turinys["pavadinimas"][$i]?>&amp;Opcion=<?php echo $Opcion;?>&amp;tag=<?php echo $tag;?>&amp;base=<?php echo $arrHttp["base"]?>&amp;desde=<?php echo $arrHttp["desde"];?>&amp;targetForm=<?php echo $targetForm;?>&amp;type=dir&amp;kryptis=<?php echo $newkrypt;?>&amp;path=<?php echo $path;?>" >
<?php echo $turinys["pavadinimas"][$i];?></a><br>
                        <?php
					 }
				   else
				     {
						$file_type = $this->get_file_type($turinys["pavadinimas"][$i]);

					   $icon=$file_type[0];
					   $cont_type=$file_type[1];
					   if ($Opcion=="seleccionar"){
					   		$sel_dir=$this->root_dir;
					   		$sel_dir=str_replace($db_path,"",$sel_dir);
					   		$sel_dir.=$turinys["pavadinimas"][$i];
					   		$sel_dir=str_replace("//","/",$sel_dir);
					   		if (substr($sel_dir,0,1)=="/") $sel_dir=substr($sel_dir,1);
					   		echo "<input type=checkbox name=sel value='".$turinys["pavadinimas"][$i]."' onclick=\"CopiarImagen('".$sel_dir."')\">";
					   }
					   echo "<img src=\"".$this->icons_dir."$icon\">";
	                   if ($icon!="unident.gif")
							echo "&nbsp; <a href=\"javascript:MostrarImagen('" . $turinys["pavadinimas"][$i] . "','" . $arrHttp["base"] . "')\">" . $turinys["pavadinimas"][$i] . "</a> <br>";
						else
						    echo "&nbsp; ".$turinys["pavadinimas"][$i]."<br>";
				    }
			     }
		     }
	     }

	}


}


?>