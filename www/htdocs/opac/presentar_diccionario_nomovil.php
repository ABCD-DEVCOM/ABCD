<?php
function PresentarDiccionario(){
include("presentar_diccionario_inc.php");
	if (count($terms)>0) {
		foreach ($terms as $linea) 	{
			//echo "$linea<br>";
			echo "<option value=$delimitador".$linea."$delimitador>".$linea."\n";
			$LastKey=$linea;
		}


	}else{
		//echo "<font size=2>No hay más términos en este diccionario<p>

		//	</html>";
	}

}
?>
		<div class="row g-3 py-3">
			<div class="col-md-10">
  				<input class="form-control" type="text" name="IR_A" size="15" placeholder="<?php echo $msgstr["front_search"]?> ..." >
			</div>
			<div class="col-md-2">
				<input class="btn btn-success" type=button value="<?php echo $msgstr["front_ira"]?>" onclick="javascript:NavegarDiccionario(this,3)" id=search-submit>
				<input type="hidden" name="campo" value="<?php if (isset($_REQUEST["campo"])) echo urldecode($_REQUEST["campo"])?>">
			</div>
		</div>
			
			
			

		<div class="row g-3">

		<div class="col-md-6">
			<?php if (isset($_REQUEST["campo"])) echo "<h6 class=\"text-dark\">".$_REQUEST["campo"]."</h6>";?>
   			<select class="form-select"  name="terminos"  multiple="multiple"  size="10"  onDblClick="moveSelectedOptions(document.diccionario.terminos,document.diccionario.TerminosSeleccionados,false)">
				<?php PresentarDiccionario(); ?>
			</select>


			<input class="btn btn-primary my-2" type="button" value="<?php echo $msgstr["front_mas_terminos"]?>" id=search-submit onclick="javascript:NavegarDiccionario(this,4)">
			
			<div id="indice">

				<a class="btn btn-light m-1" href=javascript:AbrirIndice('')>0-9</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('A')>A</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('B')>B</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('C')>C</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('D')>D</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('E')>E</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('F')>F</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('G')>G</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('H')>H</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('I')>I</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('J')>J</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('K')>K</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('L')>L</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('M')>M</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('N')>N</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('O')>O</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('P')>P</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('Q')>Q</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('R')>R</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('S')>S</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('T')>T</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('U')>U</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('V')>V</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('W')>W</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('X')>X</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('Y')>Y</a>&nbsp;
				<a  class="btn btn-light m-1" href=javascript:AbrirIndice('Z')>Z</a>
	  		</div>

   			<input type=hidden value="<?php echo $LastKey?>" NAME="LastKey">
		</div>


 		<div class="col-md-1 pt-5">
 			<input class="btn btn-primary my-2" type="button" value="&gt;" id="search-submit" onClick="moveSelectedOptions(document.diccionario.terminos,document.diccionario.TerminosSeleccionados,false)">
 			<input  class="btn btn-primary" type="button" value="&lt;" id="search-submit" onclick="moveSelectedOptions(document.diccionario.TerminosSeleccionados,document.diccionario.terminos,false)">
		</div>

 		<div class="col-md-4">
  	

 		 <h6><?php echo $msgstr["terminos_b"]?></h6>
 		  <select class="form-select"  name="TerminosSeleccionados" multiple="multiple" size="10" onDblClick="moveSelectedOptions(document.diccionario.TerminosSeleccionados,document.diccionario.terminos,false)">
			<?php
			if (isset($_REQUEST["Seleccionados"])){
				$s=Explode('"',$_REQUEST["Seleccionados"]);
				foreach ($s as $value){
					if (trim($value)!=""){
						echo "<option value=\"".$value."\">$value</option>\n";
					}
				}
			}
			?>
 		  </select>
		</div>

</div>

<?php if ($_REQUEST["Opcion"]!="libre"){
    // Correção: Define vazio se não existir
    $llamado_desde = isset($_REQUEST["llamado_desde"]) ? $_REQUEST["llamado_desde"] : "";
    
    switch ($llamado_desde){
        case "buscar_integrada.php":
            $accion=1;
            break;
        default:
            $accion=2;
            break;
    }
?>


	<input class="btn btn-success" type="button" id="search-submit" value="<?php echo $msgstr["front_enviar_fb"]?>" onclick="javascript:EjecutarBusquedaDiccionario(<?php echo $accion?>)"></a>
   <?php }
     if (!isset($_REQUEST["criterios"]) or $_REQUEST["criterios"]!="S"){
   ?>
	<input  class="btn btn-success" type=button id=search-submit value=" <?php echo $msgstr["front_search"]?> " onclick="javascript:EjecutarBusquedaDiccionario(0,'')">
   <?php } ?>