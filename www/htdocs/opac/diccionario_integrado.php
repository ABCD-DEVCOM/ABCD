<?php

/**
 * -------------------------------------------------------------------------
 * ABCD - Dicionário Integrado (Popup)
 * Corrigido para Multi-Contexto e Layout Limpo
 * -------------------------------------------------------------------------
 */

if (isset($_REQUEST["db_path"]) && trim($_REQUEST["db_path"]) == "") {
	unset($_REQUEST["db_path"]);
}

// 1. Carrega a configuração
include realpath(__DIR__ . '/../central/config_opac.php');

// 2. Validação final
if (!isset($db_path) || $db_path == "") {
	die("Erro Crítico: Não foi possível determinar o caminho da base de dados (\$db_path). Verifique o config_opac.php.");
}

// 3. Captura o Contexto
$actual_context = isset($_REQUEST['ctx']) ? $_REQUEST['ctx'] : '';

// 4. Configuração de Layout (Para esconder menus)
$startpage = "N";
$sidebar = "N";       // Desativa sidebar
$mostrar_menu = "N";  // Desativa menu superior
$mostrar_libre = "N"; // Desativa busca livre

// Carrega o cabeçalho (scripts e estilos)
include("head.php");

include("Mobile_Detect.php");
$detect = new Mobile_Detect();

// Define retorno para o JavaScript
if (isset($_REQUEST["criterios"])) {
	$retorno = "D";
} else {
	if (isset($_REQUEST["lista_bases"])) {
		if ($_REQUEST["Opcion"] == "libre")
			$retorno = "../index.php";
		else
			$retorno = "A";
	} else {
		if ($_REQUEST["Opcion"] == "libre")
			$retorno = "B";
		else
			$retorno = "C";
	}
}
?>
<style>
	/* Esconde elementos estruturais do OPAC principal */
	#header,
	/* Topo com logo */
	#searchBox,
	/* Caixa de busca "Em todos os catálogos" */
	#sidebar,
	/* Barra lateral de facetas */
	.custom-sidebar,
	footer,
	/* Rodapé */
	.navbar,
	.breadcrumb,
	#cookie_div,
	.d-md-none,
	/* Botão "Show filters" */
	button[data-bs-target="#sidebar"] {
		display: none !important;
	}

	/* Ajusta o container para ocupar a tela do popup */
	body {
		background-color: #fff !important;
		padding: 10px !important;
		overflow-x: hidden;
	}

	/* Reseta margens que o layout principal possa ter colocado */
	#page {
		margin: 0 !important;
		padding: 0 !important;
		width: 100% !important;
		max-width: 100% !important;
	}

	/* Estilo do container do dicionário */
	#diccionario_content {
		background: #fff;
	}
</style>

<script>
	document.onkeypress = function(evt) {
		var c = document.layers ? evt.which :
			document.all ? event.keyCode :
			evt.keyCode;
		if (c == 13) NavegarDiccionario(this, 3)
		return true;
	};
</script>

<div id="page" class="container-fluid">
	<div class="row">
		<div class="col-12">
			<h4>
				<?php if (isset($_REQUEST["campo"])) echo $_REQUEST["campo"]; ?>
			</h4>
		</div>
	</div>

	<form name="diccionario" method="post" action="diccionario_integrado.php">
		<?php if ($actual_context != "") { ?>
			<input type="hidden" name="ctx" value="<?php echo htmlspecialchars($actual_context); ?>">
		<?php } ?>

		<input type="hidden" name="page" value="startsearch">

		<?php
		if (isset($_REQUEST["Opcion"]) && $_REQUEST["Opcion"] == "libre") {
		?>
			<label><?php echo $msgstr["front_unir_con"] ?></label>
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="radio" value="and" name="alcance" id="and" <?php if (isset($_REQUEST["alcance"]) && $_REQUEST["alcance"] == "and") echo "checked" ?>>
				<label class="form-check-label" for="and"><?php echo $msgstr["front_and"] ?></label>
			</div>
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="radio" value="or" name="alcance" id="or" <?php if (isset($_REQUEST["alcance"]) && $_REQUEST["alcance"] == "or") echo "checked" ?>>
				<label class="form-check-label" for="or"><?php echo $msgstr["front_or"] ?></label>
			</div>
		<?php
		} else {
			echo "<input type=hidden name=alcance>\n";
		}

		if (isset($_REQUEST["Sub_Expresion"])) {
			$SE = explode('~~~', $_REQUEST["Sub_Expresion"]);
			if (!isset($_REQUEST["Seleccionados"]))
				if (isset($_REQUEST["Diccio"]) && isset($SE[$_REQUEST["Diccio"]]))
					$_REQUEST["Seleccionados"] = trim($SE[$_REQUEST["Diccio"]]);
		}

		if ($detect->isMobile()) {
			echo "<div class='alert alert-info py-1'><small>" . $msgstr["front_clic_sobre"] . " <input type=checkbox> " . $msgstr["front_para_sel"] . " | <i class='fas fa-times'></i> " . $msgstr["front_remover_sel"] . "</small></div>";
			include("presentar_diccionario_movil.php");
		} else {
			echo "<div class='alert alert-info py-1'><small>" . $msgstr["front_dbl_clic"] . "</small></div>";
			include("presentar_diccionario_nomovil.php");
		}
		?>

		<?php
		$_REQUEST["resaltar"] = "S";
		$campos_a_preservar = array(
			"lista_bases",
			"base",
			"indice_base",
			"cipar",
			"lista",
			"Diccio",
			"Sub_Expresion",
			"Sub_Expresiones",
			"Expresion",
			"Campos",
			"Operadores",
			"coleccion",
			"criterios",
			"modo",
			"llamado_desde",
			"Formato",
			"db_path",
			"lang",
			"resaltar",
			"prefijo",
			"Opcion"
		);

		foreach ($campos_a_preservar as $campo) {
			if (isset($_REQUEST[$campo])) {
				$valor = $_REQUEST[$campo];
				echo "<input type='hidden' name='$campo' value=\"" . htmlspecialchars($valor) . "\">\n";
			}
		}
		?>

		<input type="hidden" name="Navegacion" value="">
		<input type="hidden" name="LastKey" value="<?php echo isset($_REQUEST['LastKey']) ? htmlspecialchars($_REQUEST['LastKey']) : ''; ?>">
		<input type="hidden" name="Seleccionados" value="<?php echo isset($_REQUEST['Seleccionados']) ? htmlspecialchars($_REQUEST['Seleccionados']) : ''; ?>">

	</form>

	<div class="row m-3">
		<div class="col text-center">
			<button class="btn btn-secondary" type="button" onclick="if(window.parent && window.frameElement){ var m=window.parent.bootstrap.Modal.getInstance(window.parent.document.getElementById('diccionarioModal')); if(m) m.hide(); } else { window.close(); }">
				<i class="fas fa-times"></i> <?php echo $msgstr["close"] ?? "Fechar"; ?>
			</button>
		</div>
	</div>
	
</div>

<script>
	Opcion = '<?php if (isset($_REQUEST["Opcion"])) echo $_REQUEST["Opcion"]; ?>';
</script>

</body>

</html>