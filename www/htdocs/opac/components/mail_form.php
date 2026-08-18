<form name="correo" action="controllers/control_mail.php" method="post" onSubmit='EnviarCorreo();return false'>
<?php
foreach ($_REQUEST as $var => $value) {
	if (is_string($value)) {
		echo "<input type='hidden' name='" . htmlspecialchars($var, ENT_QUOTES, 'UTF-8') . "' value='" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "'>\n";
	}
}
?>

	<div class="g-3 py-2">
		<label><?php echo $msgstr["front_name"]?></label>
		<input class="form-control" type="text" name="nombre" size="55">
	</div>

	<div class="g-3 py-2">
		<label><?php echo $msgstr["front_to_mail"]?></label>
		<input class="form-control" type="email" name="email" size="55">
	</div>

	<div class="g-3 py-2">	
		<label><?php echo $msgstr["front_comments"]?></label>
		<textarea  class="form-control" rows="3" cols="60" name="comentario"></textarea>
	</div>

	<div class="g-2 py-2">
		<div class="col-auto">
			<input class="btn btn-success" type="submit" value="<?php echo $msgstr["front_send"]?> ">
		</div>

	</div>
</form>