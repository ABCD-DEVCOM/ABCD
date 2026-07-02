<?php
/* Modifications
2021-01-04 fho4abcd Removed login encryption
2021-01-04 fh04abcd Corrected "languaje" --> language
2021-02-07 fho4abcd Configured Logo url now used without prefix and strip. Works now according to wiki
2021-02-27 fho4abcd png favicon works better in bookmarks.
2021-08-10 fho4abcd Do not crash if first language file (from the browser) is missing. Visible message if no file found
2022-01-19 rogercgui Include css_settings.php
2022-01-19 fho4abcd Configured language is preset in the language selection
20220122 rogercgui Default logo is displayed if institution image is absent
20230127 fho4abcd Removed unused function, login fail can be caused by expiration: improve message content
                  Removed inline fixed size (clashed often with footer position). Removed unused div sectioninfo
20230223 fho4abcd Check for existence of config.php
20230602 fho4abcd Add captcha
20250305 fho4abcd Use current username: makes filling login form more easy
20250801 fho4abcd Do not rely on existence HTTP_ACCEPT_LANGUAGE: some browsers do not send it
20251124 fho4abcd Add button To OPAC
20260701 rogercgui Added support for cascading translation system using LanguageManager.

*/
session_start();
$_SESSION = array();
unset($_SESSION["db_path"]);
include("central/config_inc_check.php");
include("central/config.php");
include("$app_path/common/get_post.php");
$new_window = time();
//foreach ($arrHttp as $var=>$value) echo "$var = $value<br>";

$lang_config = $lang; // save the configured language to preset it later
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	//echo "HTTP_ACCEPT_LANGUAGE=$lang<br>";
}

if (isset($_SESSION["lang"])) {
	$arrHttp["lang"] = $_SESSION["lang"];
	$lang = $lang;
} else {
	$arrHttp["lang"] = $lang;
	$_SESSION["lang"] = $lang;
}
$user = "";
if (isset($arrHttp["user"])) $user = $arrHttp["user"];
include("$app_path/lang/admin.php");
include("$app_path/lang/lang.php");
?>
<!DOCTYPE html>

<html lang="<?php echo $lang; ?>" xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>">

<head profile="http://www.w3.org/2005/10/profile">
	<meta http-equiv="Expires" content="-1" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $meta_encoding; ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<meta http-equiv="Content-Language" content="<?php echo $lang; ?>" />
	<meta name="robots" content="all" />
	<meta http-equiv="keywords" content="" />
	<meta http-equiv="description" content="" />

	<!-- Favicons -->

	<link rel="mask-icon" href="/assets/images/favicons/favicon.svg">
	<link rel="icon" type="image/svg+xml" href="/assets/images/favicons/favicon.svg" color="#fff">

	<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicons/favicon-16x16.png">

	<link rel="apple-touch-icon" sizes="60x60" href="/assets/images/favicons/favicon-60x60.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/assets/images/favicons/favicon-76x76.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/assets/images/favicons/favicon-120x120.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/assets/images/favicons/favicon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicons/favicon-180x180.png">

	<title>ABCD</title>
	<!-- Stylesheets -->
	<link rel="stylesheet" rev="stylesheet" href="/assets/css/template.css?<?php echo time(); ?>" type="text/css" media="screen" />

	<!--FontAwesome-->
	<link href="/assets/css/all.min.css" rel="stylesheet">
	<!--[if IE]>
			<link rel="stylesheet" rev="stylesheet" href="<?php echo $app_path ?>/css/bugfixes_ie.css" type="text/css" media="screen"/>
		<![endif]-->
	<!--[if IE 6]>
			<link rel="stylesheet" rev="stylesheet" href="<?php echo $app_path ?>/css/bugfixes_ie6.css" type="text/css" media="screen"/>
		<![endif]-->
	<script src=/<?php echo $app_path ?>/dataentry/js/lr_trim.js></script>

	<script language=javascript>
		document.onkeypress =
			function(evt) {
				var c = document.layers ? evt.which :
					document.all ? event.keyCode :
					evt.keyCode;
				if (c == 13) Enviar()
				return true;
			}


		function CambiarClave() {
			login = Trim(document.administra.login.value)
			password = Trim(document.administra.password.value)
			captcha = Trim(document.administra.captcha.value)
			if (login == "" || password == "") {
				alert("<?php echo $msgstr["datosidentificacion"] ?>")
				return
			} else if (captcha == "") {
				alert("<?php echo $msgstr["login_nocapt"] ?>")
				return
			}
			document.cambiarPass.login.value = login
			document.cambiarPass.password.value = password
			ix = document.administra.lang.selectedIndex
			document.cambiarPass.lang.value = document.administra.lang.options[ix].value
			ix = document.administra.db_path.value
			document.cambiarPass.db_path.value = document.administra.db_path.value
			document.cambiarPass.submit()
		}

		function Enviar() {
			login = Trim(document.administra.login.value)
			password = Trim(document.administra.password.value)
			captcha = "none"
			var captchaelement = document.getElementById('captcha');
			if (typeof(captchaelement) != 'undefined' && captchaelement != null) {
				captcha = Trim(document.administra.captcha.value)
			}
			if (login == "" || password == "") {
				alert("<?php echo $msgstr["datosidentificacion"] ?>")
				return
			} else if (captcha == "") {
				alert("<?php echo $msgstr["login_nocapt"] ?>")
				return
			} else {
				if (document.administra.newindow.checked) {
					new_window = new Date()
					document.administra.target = new_window;
					ancho = screen.availWidth - 15
					alto = (screen.availHeight || screen.height) - 50
					msgwin = window.open("", new_window, "menubar=no, toolbar=no, location=no, scrollbars=yes, status=yes, resizable=yes, top=0, left=0, width=" + ancho + ", height=" + alto)
					msgwin.focus()
				} else {
					document.administra.target = ""
				}
				document.administra.submit()
			}
		}

		function ToOPAC() {
			const baseUrl = window.location.origin;
			window.location.href = baseUrl;
		}
	</script>
	<?php
	include("$app_path/common/css_settings.php");
	?>
</head>

<body>
	<header class="heading">
		<div class="institutionalInfo">
			<?php

			if (isset($def['LOGO_DEFAULT'])) {
				echo "<img src='/assets/images/logoabcd.png?" . time() . "' title='$institution_name'>";
			} elseif ((isset($def["LOGO"])) && (!empty($def["LOGO"]))) {
				echo "<img src='" . $folder_logo . $def["LOGO"] . "?" . time() . "' title='";
				if (isset($institution_name)) echo $institution_name;
				echo "'>";
			} else {
				echo "<img src='/assets/images/logoabcd.png?" . time() . "' title='ABCD'>";
			}

			?>
		</div>
		<div class="userInfo" style="margin-left: 80%;"><?php echo $meta_encoding ?></div>

		<div class="spacer">&#160;</div>
	</header>
	<form name="administra" onsubmit="javascript:return false" method="post" action="/<?php echo $app_path ?>/common/inicio.php">
		<input type="hidden" name="Opcion" value="admin">
		<input type="hidden" name="cipar" value="acces.par">
		<div class="middle login">
			<div class="loginForm">
				<div class="boxContent">
					<?php
					if (isset($arrHttp["login"]) and $arrHttp["login"] == "N") {
						echo "<div class=\"helper alert\">" . $msgstr["menu_ex_noau"] . "</div>";
					}
					if (isset($arrHttp["login"]) and $arrHttp["login"] == "P") {
						echo "<div class=\"helper success\">" . $msgstr["pswchanged"] . "</div>";
					}
					if (isset($arrHttp["login"]) and $arrHttp["login"] == "C") {
						echo "<div class=\"helper alert\">" . $msgstr["login_invcapt"] . "</div>";
					}
					?>
					<table>
						<tr>
							<td>
								<label for="user"><?php echo $msgstr["userid"] ?></label>
							</td>
							<td>
								<?php
								if (isset($arrHttp["login"]) and $arrHttp["login"] == "N") {
									echo "<input type='text' name='login' id='user' value='" . $user . "' class='textEntry superTextEntry inputAlert' onfocus=\"this.className='textEntry superTextEntry inputAlert textEntryFocus';\" onblur=\"this.className='textEntry superTextEntry inputAlert';\" />\n";
								} else {
									echo "<input type='text' name='login' id='user' value='" . $user . "' class='textEntry superTextEntry' onfocus=\"this.className='textEntry superTextEntry textEntryFocus';\" onblur=\"this.className='textEntry superTextEntry';\" />\n";
								}
								?>
							</td>
						</tr>
						<tr>
							<td>
								<label for="pwd"><?php echo $msgstr["password"] ?></label>
							</td>
							<td>
								<input type="password" name="password" id="pwd" value="" class="textEntry superTextEntry" onfocus="this.className = 'textEntry superTextEntry textEntryFocus';" onblur="this.className = 'textEntry superTextEntry';" />
							</td>
						</tr>
						<?php
						// A checagem antiga do diretório do banco de dados foi removida daqui,
						// pois agora o LanguageManager faz a busca nas pastas central e content.
						?>
						<tr>
							<td>
								<label><?php echo $msgstr["lang"] ?></label>
							</td>
							<td>
								<select name="lang" class="textEntry singleTextEntry" onchange="this.submit()">
									<option value=''></option>
									<?php
									// 1. Chama o nosso gerenciador global
									global $langManager;

									// 2. Carrega a lista de idiomas a partir do 'lang.tab' base em inglês
									$availableLanguages = [];
									if (isset($langManager)) {
										$availableLanguages = $langManager->loadTranslations('lang.tab', 'en');
									}

									// 3. Monta as opções do Select
									if (empty($availableLanguages)) {
										// Prevenção de erro caso falhe a leitura
										echo "<option value=''>Error: lang.tab missing</option>";
									} else {
										foreach ($availableLanguages as $langCode => $langName) {
											// O script antigo original do login ignorava a chave "lang" se existisse
											if ($langCode === "lang") continue;

											// Verifica qual é o idioma atual da variável $lang para selecioná-lo
											$selected = ($langCode === $lang) ? "selected" : "";

											// Busca o nome do idioma em $msgstr se existir, caso contrário usa o nome do arquivo lang.tab
											$displayName = $msgstr[$langCode] ?? $langName;

											echo "<option value=\"" . htmlspecialchars($langCode) . "\" {$selected}>" . $displayName . "</option>\n";
										}
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<?php
							if (file_exists("dbpath.dat")) {
								global $db_path;
								$fp = file("dbpath.dat");
								echo '<td>' . $msgstr["database_dir"] . '</td>';
								echo '<td><select class="textEntry singleTextEntry" name=db_path>\n';
								foreach ($fp as $value) {
									if (trim($value) != "") {
										$v = explode('|', $value);
										$v[0] = trim($v[0]);
										echo "<option value=" . trim($v[0]) . ">" . $v[1] . "\n";
									}
								}
								echo "</select>";
							} else {
								echo '<td><input type="hidden" name="db_path" value="' . $db_path . '"></td>';
							}
							?>
						<tr>
							<td>
								<label for="newindow" class="inline"><?php echo $msgstr["openwindow"] ?></label>
							</td>
							<td>
								<?php $newwindow = "";
								if (isset($open_new_window) and $open_new_window == "Y") $newwindow = "checked";
								?>
								<input type="checkbox" name="newindow" id="newindow" <?php echo $newwindow ?>>
							</td>
							<?php if (!isset($setcaptcha) || $setcaptcha != "N") { ?>
						<tr style="background-color:var(--abcd-gray-200);">
							<td colspan=2 style="text-align:center"><?php echo $msgstr["login_capt"]; ?></td>
						</tr>
						<tr>
							<td><?php
								include("central/common/captcha_get_font.php");
								$font = captcha_font(); //dies with error if font not found
								?>
								<img src="./central/common/captcha_code_file.php" id='captchaimg'>
							</td>
							<td><input type="text" name="captcha" id="captcha" class="textEntry superTextEntry" onfocus="this.className = 'textEntry superTextEntry textEntryFocus';" onblur="this.className = 'textEntry superTextEntry';" />
							<?php } ?>
						<tr>
							<td>
								<?php if (isset($change_password) and $change_password == "Y") { ?>
									<a href="javascript:CambiarClave()" class="bt bt-gray">
										<i class="fas fa-key"></i> &nbsp;<?php echo $msgstr["chgpass"] ?></a>
								<?php } ?>
							</td>
							<td style="text-align:right">
								<a href="javascript:ToOPAC()" class="bt bt-default">
									<i class="fas fa-globe"></i> &nbsp; <?php echo "OPAC"; ?></a>
								<a href="javascript:Enviar()" class="bt bt-blue">
									<i class="fas fa-sign-in-alt"></i> &nbsp; <?php echo $msgstr["login"] ?> &nbsp; </a>
							</td>
					</table>
				</div>
			</div>
		</div>
	</form>
	<form name="cambiarPass" action="<?php echo $app_path ?>/dataentry/change_password.php" method="post">
		<input type="hidden" name="login">
		<input type="hidden" name="password">
		<input type="hidden" name="lang">
		<input type="hidden" name="db_path">
		<input type="hidden" name="Opcion" value="chgpsw">
	</form>
	<?php include("$app_path/common/footer.php"); ?>