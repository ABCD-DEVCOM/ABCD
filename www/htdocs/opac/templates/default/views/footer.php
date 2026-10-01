<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   footer.php
 *  Purpose:  Controls the footer of the pages in the OPAC
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2023-03-12 rogercgui Created
 *  2026-02-11 rogercgui Added support for social media links in the footer.
 *  2026-02-15 rogercgui Added support for a customizable HTML description in the footer.
 *  2026-09-29 rogercgui Added a hook for additional footer content if the function exists.
 * -------------------------------------------------------------------------
 */
?>

</div>
</div>
</div>
</main>
</div>

<?php

$footer_description = "";
$footer_copyright = "&copy; " . date("Y") . " ABCD System.";
$social_links = [];

$footer_file = $db_path . "opac_conf/" . $lang . "/footer.info";

if (file_exists($footer_file)) {
	$lines = file($footer_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$line = trim($line);

		// Tag [HTML] - Descrição da Coluna 1
		if (strpos($line, '[HTML]') === 0) {
			$footer_description = substr($line, 6); // Remove a tag [HTML]
		}

		// Tag [COPYRIGHT] - Linha final
		if (strpos($line, '[COPYRIGHT]') === 0) {
			$footer_copyright = substr($line, 11);
		}

		// Tag [NETWORK] - Formato: [NETWORK]Provider|URL
		if (strpos($line, '[NETWORK]') === 0) {
			$parts = explode('|', substr($line, 9));
			if (count($parts) >= 2) {
				$provider = strtolower(trim($parts[0]));
				$url = trim($parts[1]);

				// Mapa de Ícones (FontAwesome)
				$icon = "fas fa-link"; // Padrão
				if ($provider == 'facebook') $icon = "fab fa-facebook-f";
				if ($provider == 'instagram') $icon = "fab fa-instagram";
				if ($provider == 'twitter') $icon = "fab fa-twitter"; // ou fa-x-twitter
				if ($provider == 'linkedin') $icon = "fab fa-linkedin-in";
				if ($provider == 'youtube') $icon = "fab fa-youtube";
				if ($provider == 'whatsapp') $icon = "fab fa-whatsapp";

				$social_links[] = ['url' => $url, 'icon' => $icon, 'name' => ucfirst($provider)];
			}
		}
	}
}
?>

<footer class="py-5 mt-5 border-top bg-light custom-footer" id="footer">
	<div class="<?php echo "container" . (isset($container) ? $container : ""); ?>">

		<div class="row">

			<div class="col-md-3 mb-4">
				<div class="mb-3">
					<?php echo $footer_description; ?>
				</div>
			</div>

			<?php
			$sidebar_file = $db_path . "opac_conf/" . $lang . "/side_bar.info";
			if (file_exists($sidebar_file)) {
				$lines = file($sidebar_file);
				$in_section = false;
				foreach ($lines as $line) {
					$line = trim($line);
					if (empty($line)) continue;
					if (strpos($line, '[SECCION]') === 0) {
						if ($in_section) echo '</ul></div>';
						$title = substr($line, 9);
						echo '<div class="col-md-3 mb-4">';
						echo '<h5 class="fw-bold text-uppercase">' . $title . '</h5>';
						echo '<ul class="nav flex-column">';
						$in_section = true;
					} elseif ($in_section) {
						$parts = explode('|', $line);
						if (count($parts) >= 2) {
							$target = (isset($parts[2]) && trim($parts[2]) == "Y") ? 'target="_blank"' : '';
							echo '<li class="nav-item mb-2"><a href="' . $parts[1] . '" class="nav-link p-0 text-muted" ' . $target . '><i class="fas fa-angle-right me-2 small"></i>' . $parts[0] . '</a></li>';
						}
					}
				}
				if ($in_section) echo '</ul></div>';
			}
			?>
		</div>

		<div class="d-flex flex-column flex-sm-row justify-content-between py-4 my-4 border-top align-items-center">

			<div class="small text-muted mb-2 mb-sm-0 custom-footer">
				<?php echo $footer_copyright; ?>
			</div>

			<?php if (!empty($social_links)) : ?>
				<ul class="list-unstyled d-flex mb-0">
					<?php foreach ($social_links as $net) : ?>
						<li class="ms-3">
							<a class="link-dark fs-5" href="<?php echo $net['url']; ?>" target="_blank" title="<?php echo $net['name']; ?>">
								<i class="<?php echo $net['icon']; ?>"></i>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php toTop(); ?>
<?php include($Web_Dir . "forms.php"); ?>

<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-fullscreen">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="recordDetailModalLabel"><?php echo $msgstr["front_detalhes_registro"]; ?></h3>
				<div id="modalFormatSelectorContainer" class="ms-auto me-3" style="min-width: 200px;"></div>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="modalLoadingIndicator" class="text-center my-5" style="display: none;">
					<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
					<p class="mt-2"><?php echo $msgstr["loading"]; ?></p>
				</div>
				<div id="modalRecordContent"></div>
			</div>
			<div class="modal-footer d-flex justify-content-between">
				<div id="modalActionButtons" class="d-flex flex-wrap"></div>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $msgstr["close"]; ?></button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="reserveConfirmModal" tabindex="-1" aria-labelledby="reserveConfirmModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="reserveConfirmModalLabel"><?php echo $msgstr["reserve_confirm_title"]; ?></h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="reserveModalBodyContent">
					<p><?php echo $msgstr["reserve_confirm_query"]; ?></p>
				</div>
				<div id="reserveModalLoading" style="display: none;" class="text-center">
					<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
					<p>Processing...</p>
				</div>
				<div id="reserveModalFeedback" style="display: none;"></div>
			</div>
			<div class="modal-footer" id="reserveModalFooter">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $msgstr["front_cancelar"]; ?></button>
				<button type="button" class="btn btn-primary" id="reserveConfirmButton" onclick="executarReserva(this);"><?php echo $msgstr["reserve_confirm_button"]; ?></button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="abcdModal" tabindex="-1" aria-labelledby="abcdModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="abcdModalLabel">Processing...</h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="modal-loading-spinner text-center" style="display: none;">
					<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
					<p class="mt-2">Please wait...</p>
				</div>
				<div class="modal-feedback-area" style="display: none;"></div>
			</div>
			<div class="modal-footer" style="display: none;"></div>
		</div>
	</div>
</div>

<div class="modal fade" id="permalinkModal" tabindex="-1" aria-labelledby="permalinkModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="permalinkModalLabel"><?php echo $msgstr["share_link"]; ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p><?php echo $msgstr["copy_share_link"]; ?></p>
				<div class="input-group">
					<input type="text" id="permalinkInput" class="form-control" value="" readonly>
					<button class="btn btn-primary" type="button" id="copyPermalinkButton" onclick="copyPermalink()">Copy</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
// MODAL LOGIN
if (
	(!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) &&
	(isset($OnlineStatment) && $OnlineStatment == 'Y' ||
		isset($WebRenovation) && $WebRenovation == 'Y' ||
		isset($WebReservation) && $WebReservation == 'Y')
) :
?>
	<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="loginModalLabel"><?php echo $msgstr["login_form_title"]; ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form name="loginModalForm" method="post" action="dologin.php">
						<?php if (isset($actual_context) && $actual_context != "") { ?>
							<input type="hidden" name="ctx" value="<?php echo htmlspecialchars($actual_context); ?>">
						<?php } ?>
						<?php $current_url = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>
						<input type="hidden" name="RedirectUrl" value="<?php echo $current_url; ?>">
						<input type="hidden" name="Opcion" value="login">
						<input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">

						<?php if (isset($_GET['login_error'])) {
							echo '<div class="alert alert-danger" role="alert">' . $msgstr["err_login_form"] . '</div>';
						} ?>

						<div class="mb-3">
							<label for="modalLogin" class="form-label"><?php echo $msgstr["login_form_slogin"]; ?></label>
							<input type="text" class="form-control" name="login" id="modalLogin" required>
						</div>
						<div class="mb-3">
							<label for="modalPassword" class="form-label"><?php echo $msgstr["login_form_spass"]; ?></label>
							<input type="password" class="form-control" name="password" id="modalPassword" required>
						</div>
						<button type="submit" class="w-100 btn btn-primary">
							<i class="fas fa-sign-in-alt"></i> <?php echo $msgstr["front_entrar"] ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php if (isset($_GET['login_error'])) : ?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
				loginModal.show();
			});
		</script>
	<?php endif; ?>
<?php endif; ?>

<script>
	var OpacMsgstr = {
		"reserve_confirm_query": "<?php echo addslashes($msgstr["reserve_confirm_query"]); ?>",
		"front_cancelar": "<?php echo addslashes($msgstr["front_cancelar"]); ?>",
		"reserve_confirm_button": "<?php echo addslashes($msgstr["reserve_confirm_button"]); ?>",
		"front_fechar": "<?php echo addslashes($msgstr["front_fechar"]); ?>",
		"err_ajax_communication": "<?php echo addslashes($msgstr["err_ajax_communication"]); ?>",
		"err_ajax_response": "<?php echo addslashes($msgstr["err_ajax_response"]); ?>",
		reserveTitle: "<?php echo $msgstr["reserve"] ?? 'Reservar Item'; ?>",
		cancelTitle: "<?php echo $msgstr["cancel"] ?? 'Cancelar Reserva'; ?>",
		ajaxError: "<?php echo $msgstr["err_ajax_communication"] ?? 'Erro de comunicação com o servidor.'; ?>",
		jsonError: "<?php echo $msgstr["err_ajax_response"] ?? 'O servidor enviou uma resposta inválida.'; ?>",
		closeBtn: "<?php echo $msgstr["front_fechar"] ?? 'Fechar'; ?>",
		reloading: "<?php echo $msgstr["reloading"] ?? 'Atualizando a página...'; ?>",
		reloadingLogin: "<?php echo $msgstr["reloading_login"] ?? 'Redirecionando para o login...'; ?>",
	};
</script>

<script src="<?php echo $OpacHttp; ?>assets/js/jquery-ui.min.js?<?php echo time(); ?>"></script>
<script src="<?php echo $OpacHttp; ?>assets/js/switch.js?<?php echo time(); ?>"></script>
<script src="<?php echo $OpacHttp; ?>assets/js/slick.min.js?<?php echo time(); ?>"></script>
<script src="<?php echo $OpacHttp; ?>assets/js/script_f.js?<?php echo time(); ?>"></script>

<?php
// Add hook for additional footer content if the function exists
if (function_exists('abcd_run_hook')) {
	echo abcd_run_hook('opac_footer_end', '');
}
?>
</body>

</html>