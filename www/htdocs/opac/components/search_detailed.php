<?php

/**
 * -------------------------------------------------------------------------
 * ABCD - Componente de Busca Detalhada
 * Correção: HTML malformado e suporte a arrays
 * -------------------------------------------------------------------------
 */

$startpage = "N";

// Lógica de Modo Integrado
if (isset($_REQUEST["modo"]) and $_REQUEST["modo"] == "integrado") {
	if (isset($_REQUEST["base"])) unset($_REQUEST["base"]);
}

// Inicia container do título
echo "<div class='titulo-base-container mb-3'>";

// Exibe o título da base ou "Todos os catálogos"
if (!isset($_REQUEST["base"]) or $_REQUEST["base"] == "") {
	$base = "";
	echo "<span class='tituloBase h5'>" . $msgstr["front_todos_c"] . "</span>";
} else {
	// Verifica se a base existe na lista para evitar erros
	$titulo_base = isset($bd_list[$_REQUEST["base"]]["titulo"]) ? $bd_list[$_REQUEST["base"]]["titulo"] : $_REQUEST["base"];
	echo "<span class='tituloBase h5'>" . $titulo_base . "</span>";
	$base = $_REQUEST["base"];
}

// Exibe coleção se houver (Corrigido o <i></i>)
if (isset($_REQUEST["coleccion"]) and $_REQUEST["coleccion"] != "") {
	$_REQUEST["coleccion"] = urldecode($_REQUEST["coleccion"]);
	$col = explode('|', $_REQUEST["coleccion"]);
	if (isset($col[1])) {
		echo " <small class='text-muted'>(<strong><i>" . htmlspecialchars($col[1], ENT_QUOTES, 'UTF-8') . "</i></strong>)</small>";
	}
}

echo "</div>"; // Fecha container do título
?>

<h4 class="mb-3"><?php echo $msgstr["front_buscar_a"]; ?></h4>

<?php
// Desenha o formulário principal de busca detalhada
$Diccio = -1;
// Verifica se a função existe para evitar erro fatal se o include falhar
if (function_exists('DibujarFormaBusqueda')) {
	DibujarFormaBusqueda($Diccio);
}
?>

<form name="back" method="post" action="buscar_integrada.php">
	<input type="hidden" name="page" value="startsearch">
	<?php
	// Loop que suporta Arrays e Strings (Correção do erro de trim)
	foreach ($_REQUEST as $var => $value) {
		if (is_array($value)) {
			// Se for array (ex: camp[]), cria um input para cada item
			foreach ($value as $item) {
				if (is_string($item)) {
					echo "<input type='hidden' name='" . htmlspecialchars($var) . "[]' value=\"" . urlencode(trim($item)) . "\">\n";
				}
			}
		} else {
			// Se for string, processa normalmente
			// Verifica se é string para evitar erro em outros tipos
			if (is_string($value)) {
				echo "<input type='hidden' name='" . htmlspecialchars($var) . "' value=\"";
				if (trim($value) != '""') echo urlencode($value);
				echo "\">\n";
			}
		}
	}
	?>
</form>

</div>
</div>