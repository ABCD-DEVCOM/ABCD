<?php
/*
** Load odds "combos": information for dropdown lists
**
** 20221207 Created from lib/library.php-load_combos
*/

/* --------------------------------------------------------------------------------------------
Incluir el archivo de confirguracion, leer los archivos tabs y cargarlos en arrays para luego
dibujar los combos. 
Se buscan leer tabs para todos los combos, o sea, ... si algun archivo no se encuentra, 
se cargan arrays con valores por defecto
Read the .tab files and load them in arrays for later draw the combos.
Read tabs are searched for all combos, that is, ... if any file is not found, arrays with default values are loaded
-------------------------------------------------------------------------------------------- */
function load_combos() {
    global $lang, $db_path, $msgstr;
    $path_tab = $db_path."odds/def/".$lang."/";
	// cuales tabs debemos leer?, o sea, cuales combos se deben llenar
    // To determine the .tab files  to be read set the name into $combos
	$combos["categoria"] = array();
	$combos["nivelbiblio"] = array();

	//leer el directorio y los archivos
	if (is_dir($path_tab)) { 		
		if ($dh = opendir($path_tab)) { 
			while (($file = readdir($dh)) !== false) {
				if (!is_dir($path_tab . $file) && $file!="." && $file!=".." ) {
					$a = explode(".", $file);
					if ($a[(count($a)-1)] == "tab"){
						// borrar ultima celda del array
						array_pop($a);				 
						// implode para reconstruir el nombre del archivo sin el tab
						$name_tab = trim(implode(".", $a));
						// ver si el archivo está en $combos, porque eso significa que el archivo se usa en el form
						if (isset($combos[$name_tab])) {
							// leer el archivo tab							
							$file_contents = file_get_contents($path_tab.$name_tab.".tab");	
							// separar las lineas con "\n"
							$lineas = explode("\n", $file_contents);									
							foreach ($lineas as $linea) {
								$key_value = explode("|", $linea);															
								if(isset($key_value[1])) $combos[$name_tab][trim($key_value[0])] =  trim($key_value[1]);
							}
						}
					}
				}
			} //fin while para loopear los archivos .tab
		}
	}
	// valores por defecto (si querdaron vacíos) - ¡deshabilitados por ahora!
	if (empty($combos["categoria"])) {
        echo "<div style='color:red'> ".$msgstr["archivo"]."&nbsp;".$path_tab."categoria.tab ".$msgstr["odds_nocontent"]."</div>";
    }
    if (empty($combos["nivelbiblio"])) {
        echo "<div style='color:red'> ".$msgstr["archivo"]."&nbsp;".$path_tab."nivelbiblio.tab ".$msgstr["odds_nocontent"]."</div>";
	}
	return $combos;
}

?>
