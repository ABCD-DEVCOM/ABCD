<?php

/**
 * -------------------------------------------------------------------------
 *  ABCD - Automação de Bibliotecas e Centros de Documentação
 *  https://github.com/ABCD-DEVCOM/ABCD
 * -------------------------------------------------------------------------
 *  Script:   topbar.php
 *  Purpose:  Displays the top navigation bar in the OPAC
 *  Author:   Roger C. Guilherme
 *
 *  Changelog:
 *  -----------------------------------------------------------------------
 *  2025-10-22 rogercgui Created
 * -------------------------------------------------------------------------
 */



if ($restricted_opac == "Y") {
  // Pega o nome do script atual (ex: "login.php")
  $current_page = basename($_SERVER['PHP_SELF']);

  // SÓ executa a verificação se a página atual NÃO for login.php
  if ($current_page != "login.php" && (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))) {

    $RedirectUrl = $_SERVER['REQUEST_URI'];

    // Use $Web_Dir (do config_opac.php), não $link_logo, para caminhos de arquivos
    $login_page_url = $link_logo . "/login.php?RedirectUrl=" . urlencode($RedirectUrl);

    header("Location: " . $login_page_url);
    exit;
  }
}
?>

<header id="header" class="navbar navbar-primary custom-top-link <?php if ($topbar == "sticky-top") echo "sticky-top"; ?> p-1 mb-3 d-flex shadow bg-white">
  <div class="container<?php echo $container; ?>">
    <a id="logo" href="<?php echo MontarUrlOpac($link_logo); ?>" class="navbar-brand p-0 me-0 me-lg-2">
      <?php if (isset($logo)) { ?>
        <img class="p-2" style="max-height:70px;" src="<?php echo $link_logo . "/" . $logo ?>" title="<?php echo $TituloEncabezado; ?>">
      <?php } else { ?>
        <span class="fs-4"><?php echo $TituloEncabezado; ?></span>
      <?php } ?></a>

    <?php
    if (!isset($mostrar_menu) or (isset($mostrar_menu) and $mostrar_menu == "S")) {
    ?>
      <ul id="menu-wrapper" class="nav nav-pills">
        <?php
        // Monta a URL nativa do OPAC para o botão Início, salvaguardando o modo estrito
        $url_inicio = "";
        $params_inicio = [];


        if (isset($actual_context) && $actual_context != "") {
          $params_inicio['ctx'] = $actual_context;
        } elseif (isset($_REQUEST['ctx']) && trim($_REQUEST['ctx']) !== '') {
          $params_inicio['ctx'] = trim($_REQUEST['ctx']);
        }
        
        if (isset($_REQUEST['base']) && trim($_REQUEST['base']) !== '') {
          $params_inicio['base'] = trim($_REQUEST['base']);
        }

        if (!empty($params_inicio)) {
          $url_inicio .= "?" . http_build_query($params_inicio);
        }
        ?>

        <li class="nav-item">
          <a href="javascript:clearAndRedirect('<?php echo $url_inicio; ?>')" class="nav-link text-dark custom-top-link" aria-current="page">
            <?php echo $msgstr["front_inicio"] ?>
          </a>
        </li>

        <?php
        if (file_exists($db_path . "opac_conf/" . $lang . "/menu.info")) {
          $fp = file($db_path . "opac_conf/" . $lang . "/menu.info");
          foreach ($fp as $value) {
            $value = trim($value);
            if ($value != "") {
              $x = explode('|', $value);
              echo '<li class="nav-item">';
              echo '<a class="nav-link text-dark custom-top-link" href="' . htmlspecialchars($x[1]) . '"';
              if (isset($x[2]) and $x[2] == "Y") {
                echo ' target="_blank"';
              }
              echo '>' . htmlspecialchars($x[0]) . '</a>';
              echo '</li>';
            }
          }
        }
        ?>

        <?php
        /* O BLOCO DE AUTENTICAÇÃO FOI REMOVIDO DAQUI
        */
        ?>

      </ul> <?php darkMode(); ?>
      <?php fontSize() ?>
      <?php selectLang() ?>

      <?php
      // --- INÍCIO DA LÓGICA DE AUTENTICAÇÃO (MOVIDA PARA CÁ) ---

      $servicos_online_ativos = false;
      if (
        (isset($OnlineStatment) && $OnlineStatment == 'Y') ||
        (isset($WebRenovation) && $WebRenovation == 'Y') ||
        (isset($WebReservation) && $WebReservation == 'Y')
      ) {
        $servicos_online_ativos = true;
      }

      if ($servicos_online_ativos) {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
          // --- USUÁRIO LOGADO ---
          $nome_completo = explode(",", $_SESSION['user_name']);
          $primeiro_nome = isset($nome_completo[1]) ? trim($nome_completo[1]) : $_SESSION['user_name'];
      ?>
          <a class="nav-link text-dark custom-top-link mx-2" href="<?php echo $link_logo; ?>/myabcd/index.php?lang=<?php echo $lang; ?><?php echo isset($ctx_path) ? $ctx_path : ''; ?>" title="<?php echo $msgstr['my_account']; ?>">
            <i class="fas fa-user"></i> <?php echo $msgstr['front_hello']; ?>, <?php echo htmlspecialchars($primeiro_nome); ?>
          </a>
          <a class="nav-link text-dark custom-top-link " href="<?php echo $link_logo; ?>/logout.php?lang=<?php echo $lang; ?>" title="<?php echo $msgstr['front_logout']; ?>">
            <i class="fas fa-sign-out-alt"></i> <?php echo $msgstr['front_logout']; ?>
          </a>
        <?php
        } else {
          // --- USUÁRIO DESLOGADO (CHAMA O MODAL) ---
        ?>
          <a class="nav-link text-dark custom-top-link  mx-2" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="fas fa-sign-in-alt"></i> <?php echo $msgstr['front_login']; ?>
          </a>
    <?php
        }
      }
      // --- FIM DA LÓGICA DE AUTENTICAÇÃO ---
    } ?>
  </div>
</header>