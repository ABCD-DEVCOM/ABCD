<form name="admin" method="post">
	<input type="hidden" name=encabezado value=s>
	<input type="hidden" name=retorno value="../common/inicio.php">
	<input type="hidden" name=modulo value=catalog>
	<input type="hidden" name=screen_width>
	<input type="hidden" name=base value="<?php echo $arrHttp["base"] ?>">
</form>

<script type="text/javascript">
	function CambiarBaseAdministrador(Modulo) {
		switch (Modulo) {
			case "toolbar":
				document.admin.action = "../dataentry/inicio_main.php";
				break;
			case "utilitarios":
				document.admin.action = "menu_mantenimiento.php";
				break;
			case "estructuras":
				document.admin.action = "menu_modificardb.php";
				break;
			case "reportes":
				document.admin.action = "pft.php";
				break;
			case "stats":
				document.admin.action = "../statistics/tables_generate.php";
				break;
		}
		document.admin.submit();
	}
</script>

<div class="toolbar-dataentry">

	<a class="bt-tool" href="javascript:CambiarBaseAdministrador('toolbar')" title="<?php echo $msgstr["dataentry"] ?>">
		<i class="fas fa-edit"></i>
	</a>

	<a class="bt-tool" href="javascript:CambiarBaseAdministrador('reportes')" title="<?php echo $msgstr["r_reportes"] ?>">
		<i class="fas fa-print"></i>
	</a>

	<a class="bt-tool" href="javascript:CambiarBaseAdministrador('utilitarios')" title="<?php echo $msgstr["maintenance"] ?>">
		<i class="fas fa-tools"></i>
	</a>

	<a class="bt-tool" href="javascript:CambiarBaseAdministrador('estructuras')" title="<?php echo $msgstr["updbdef"] ?>">
		<i class="fas fa-cogs"></i>
	</a>

	<a class="bt-tool" href="../common/inicio.php?base=<?php echo $arrHttp["base"]; ?>&modulo=catalog">
		<i class="fas fa-home"></i>
	</a>
</div>