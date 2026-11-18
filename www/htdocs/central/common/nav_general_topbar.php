<?php
/*
 * Script: nav_general_topbar.php
 * Author: Roger Craveiro Guilherme
 * Description: This script generates the top navigation bar for the ABCD Central interface, displaying buttons for Cataloging, Circulation, Acquisitions, OPAC access, and user information based on permissions and configurations.
 * Date: 2026-03-05
 * 
 * changelog:
 * 2026-03-05 fho4abcd: Initial creation of the topbar navigation
 * 20260906 rogercgui - Added support for user dropdown with profile and logout options
 * 20260907 rogercgui - Added Priority+ overflow handling for module buttons
 * 20261117 fho4abcd  - Correct html for lists + target=_top for logout button (does not work with href=#)
 */


global $arrHttp, $def, $msgstr, $db_path, $charset, $meta_encoding, $name, $profile, $login, $verify_selbase, $module_odds, $link_logo;

$sys_charset = !empty($charset) ? $charset : (!empty($meta_encoding) ? $meta_encoding : 'UTF-8');

$central = false;
$circulation = false;
$acquisitions = false;
if (isset($_SESSION["permiso"])) {
    foreach ($_SESSION["permiso"] as $key => $value) {
        $p = explode("_", $key);
        if ((isset($p[1]) && $p[1] === "CENTRAL") || str_starts_with($key, "CENTRAL_") || str_starts_with($key, "ADM_")) $central = true;
        if (str_starts_with($key, "CIRC_")) $circulation = true;
        if (str_starts_with($key, "ACQ_")) $acquisitions = true;
    }
}

$current_base = $arrHttp["base"] ?? $_REQUEST["base"] ?? "";
$active_mod   = $_SESSION["MODULO"] ?? "";
$current_lang = $_SESSION["lang"] ?? $_REQUEST["lang"] ?? "en";

$module_registry = [
    'cat' => [
        'title'  => $msgstr["catalogacion"] ?? 'Cataloging',
        'icon'   => 'fas fa-book',
        'action' => '/central/common/inicio.php',
        'params' => ['base' => $current_base, 'modulo' => 'catalog', 'lang' => $current_lang],
        'perm'   => $central,
        'active' => ($active_mod === "catalog" || $active_mod === "adm")
    ],
    'circ' => [
        'title'  => $msgstr["prestamo"] ?? 'Circulation',
        'icon'   => 'fas fa-exchange-alt',
        'action' => '/central/common/inicio.php',
        'params' => ['base' => $current_base, 'modulo' => 'loan', 'lang' => $current_lang],
        'perm'   => ($circulation && ($def["SHOW_CIRCULATION"] ?? "Y") === "Y"),
        'active' => ($active_mod === "loan")
    ],
    'acq' => [
        'title'  => $msgstr["acquisitions"] ?? 'Acquisitions',
        'icon'   => 'fas fa-shopping-cart',
        'action' => '/central/common/inicio.php',
        'params' => ['base' => $current_base, 'modulo' => 'acquisitions', 'lang' => $current_lang],
        'perm'   => ($acquisitions && ($def["SHOW_ACQUISITION"] ?? "Y") === "Y"),
        'active' => ($active_mod === "acquisitions")
    ]
];

if (function_exists('abcd_run_hook')) {
    $hook_result = abcd_run_hook('abcd_topbar_modules', $module_registry);
    if (is_array($hook_result)) {
        $module_registry = $hook_result;
    }
}

$nav_mods_str = $def['NAV_MODS'] ?? 'cat, circ, acq';
$nav_order = array_map('trim', explode(',', $nav_mods_str));

foreach (array_keys($module_registry) as $registered_slug) {
    if (!in_array($registered_slug, $nav_order)) {
        $nav_order[] = $registered_slug;
    }
}
?>

<nav class="heading-nav" id="heading-nav">
    <!-- 1. CONTAINER DOS MÓDULOS (Flexível) -->
    <div class="nav-modules-wrapper" id="nav-modules-wrapper">
        <ul class="nav-modules" id="nav-modules-list">
            <?php
            foreach ($nav_order as $slug) {
                if (isset($module_registry[$slug]) && $module_registry[$slug]['perm']) {
                    $mod = $module_registry[$slug];
                    $active_class = $mod['active'] ? 'active' : '';
            ?>
                    <li class="nav-module-item">
                        <form action="<?php echo htmlspecialchars($mod['action'], ENT_QUOTES, $sys_charset) ?>" method="post" target="_top" class="nav-module-form">
                            <?php foreach ($mod['params'] as $p_key => $p_val): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars((string)$p_key, ENT_QUOTES, $sys_charset) ?>" value="<?php echo htmlspecialchars((string)$p_val, ENT_QUOTES, $sys_charset) ?>">
                            <?php endforeach; ?>
                            <button class="bt-mod <?php echo $active_class ?>" type="submit" title="<?php echo htmlspecialchars((string)$mod['title'], ENT_QUOTES, $sys_charset) ?>">
                                <i class="<?php echo htmlspecialchars($mod['icon'], ENT_QUOTES, $sys_charset) ?> mod-icon"></i>
                                <span class="mod-title"><?php echo htmlspecialchars((string)$mod['title'], ENT_QUOTES, $sys_charset) ?></span>
                            </button>
                        </form>
                    </li>
            <?php
                }
            }
            ?>
        </ul>
    </div>

    <!-- 2. BOTÃO "MODS" E DROPDOWN (Overflow) -->
    <div class="dropdown-container" id="nav-mods-dropdown" style="visibility: hidden; width: 0;">
        <button class="bt-mod bt-mods-toggle" id="bt-mods-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="mods-dropdown-list">
            <i class="fas fa-th"></i>
            <span class="mod-title" style="margin-left: 6px;">Mods</span>
            <i class="fas fa-chevron-down bt-mods-chevron" style="font-size: 0.7em; margin-left: 6px;"></i>
        </button>
        <ul class="mods-dropdown-menu" id="mods-dropdown-list" role="menu" aria-label="Mods">
            <!-- Itens excedentes serão movidos para cá pelo JS -->
        </ul>
    </div>

    <!-- 3. UTILITÁRIOS (Fixos à direita) -->
    <ul class="nav-utilities">
        <?php
        $show_opac_btn = $def["SHOW_OPAC_BUTTON"] ?? "Y";
        if (($central || $circulation || $acquisitions || (isset($verify_selbase) && $verify_selbase === "Y") || isset($module_odds)) && $show_opac_btn === "Y" && isset($link_logo)) {
            echo '<li><a href="' . htmlspecialchars($link_logo, ENT_QUOTES, $sys_charset) . '" target="_blank" class="bt-util" title="OPAC"><i class="fas fa-globe-americas"></i> <span class="mod-title" style="margin-left: 6px;">OPAC</span></a></li>';
        }
        if (($def["SHOW_CHARSET"] ?? "Y") === "Y") {
            echo '<li><span class="bt-util charset-badge" title="' . htmlspecialchars($msgstr["page_encoding"] ?? 'Encoding', ENT_QUOTES, $sys_charset) . '">' . htmlspecialchars($sys_charset, ENT_QUOTES, $sys_charset) . '</span></li>';
        }
        ?>
        <li class="dropdown-container" id="user-dropdown-container">
            <button class="bt-util user-badge bt-user-toggle" id="bt-user-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="user-dropdown-list" title="<?php echo htmlspecialchars(($name ?? '') . ' (' . ($profile ?? '') . ')', ENT_QUOTES, $sys_charset); ?>">
                <i class="fas fa-user-circle"></i>
                <span class="mod-title" style="margin-left: 6px;"><?php echo htmlspecialchars($login ?? '', ENT_QUOTES, $sys_charset); ?></span>
                <i class="fas fa-chevron-down bt-user-chevron" style="font-size: 0.7em; margin-left: 6px;"></i>
            </button>
            <ul class="user-dropdown-menu" id="user-dropdown-list" role="menu" aria-label="User Options">
                <li>
                    <a href="#" style="text-decoration: none;" class="bt-dropdown-item"><i class="fas fa-user dropdown-icon"></i>
                        <?php echo htmlspecialchars($name ?? '', ENT_QUOTES, $sys_charset); ?>
                        <small>&nbsp;(<?php echo htmlspecialchars($profile ?? '', ENT_QUOTES, $sys_charset); ?>)</small>
                    </a>
                </li>
                <li><a href="/central/common/change_password.php" class="bt-dropdown-item"><i class="fas fa-key dropdown-icon"></i> <?php echo htmlspecialchars($msgstr["change_password"] ?? 'Change Password', ENT_QUOTES, $sys_charset); ?></a></li>
                <li class="dropdown-divider"></li>
                <a href="/central/common/logout.php" target=_top class="bt-dropdown-item" title="<?php echo htmlspecialchars($msgstr["logout"] ?? 'Logout', ENT_QUOTES, $sys_charset); ?>">
                    <i class="fas fa-sign-out-alt"></i>&nbsp;<?php echo htmlspecialchars($msgstr["logout"] ?? 'Logout', ENT_QUOTES, $sys_charset); ?>
                </a>
           </ul>
        </li>
    </ul>
</nav>

<!-- NOVO SCRIPT COMPLETO NO FINAL DO ARQUIVO -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const wrapper = document.getElementById("nav-modules-wrapper");
        const primaryList = document.getElementById("nav-modules-list");
        const overflowList = document.getElementById("mods-dropdown-list");
        const modsDropdown = document.getElementById("nav-mods-dropdown");
        const toggleBtn = document.getElementById("bt-mods-toggle");

        // Elementos do Dropdown de Usuário
        const userToggleBtn = document.getElementById("bt-user-toggle");
        const userDropdownList = document.getElementById("user-dropdown-list");
        const userDropdownContainer = document.getElementById("user-dropdown-container");

        // ------------------------------------------------------------------
        // This topbar usually runs INSIDE a <iframe> (e.g., the iframe
        // “header” from inicio_main.php). An iframe crops its content to the
        // height of the box itself: no z-index can fix this, because it’s not
        // a stacking conflict—it’s the physical boundary of the iframe. Since the
        // iframe is same-origin, we increase the height of the <iframe> ITSELF (via
        // window.frameElement, viewed from the outside) only while the dropdown
        // is open, and we return it to normal when it closes. No changes to
        // inicio_main.php; if the page is not inside an iframe (or
        // is cross-origin), window.frameElement is null/inaccessible and the
        // function simply does nothing.
        function syncHostIframeForDropdown(openMenuEl) {
            if (!window.frameElement) return;
            try {
                if (openMenuEl) {
                    const neededHeight = Math.ceil(openMenuEl.getBoundingClientRect().bottom) + 8;
                    window.frameElement.style.position = 'absolute';
                    window.frameElement.style.top = '0';
                    window.frameElement.style.left = '0';
                    window.frameElement.style.height = neededHeight + 'px';
                } else {
                    window.frameElement.style.position = '';
                    window.frameElement.style.top = '';
                    window.frameElement.style.left = '';
                    window.frameElement.style.height = '';
                }
            } catch (e) {
                // iframe cross-origin ou outra restrição do navegador - ignora
            }
        }

        // Abre/fecha dropdown do Mods
        function setModsDropdownOpen(isOpen) {
            if (isOpen) setUserDropdownOpen(false); // Fecha o do usuário se abrir o Mods
            overflowList.classList.toggle('show-dropdown', isOpen);
            toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggleBtn.classList.toggle('dropdown-open', isOpen);
            syncHostIframeForDropdown(isOpen ? overflowList : null);
        }

        // Abre/fecha dropdown do Usuário
        function setUserDropdownOpen(isOpen) {
            if (isOpen) setModsDropdownOpen(false); // Fecha o Mods se abrir o usuário
            userDropdownList.classList.toggle('show-dropdown', isOpen);
            userToggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            userToggleBtn.classList.toggle('dropdown-open', isOpen);
            syncHostIframeForDropdown(isOpen ? userDropdownList : null);
        }

        // Listeners de Clique
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            setModsDropdownOpen(!overflowList.classList.contains('show-dropdown'));
        });

        userToggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            setUserDropdownOpen(!userDropdownList.classList.contains('show-dropdown'));
        });

        // Fecha os dropdowns ao clicar fora
        document.addEventListener('click', function(e) {
            if (!modsDropdown.contains(e.target)) setModsDropdownOpen(false);
            if (!userDropdownContainer.contains(e.target)) setUserDropdownOpen(false);
        });

        // Fecha com Esc
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (overflowList.classList.contains('show-dropdown')) {
                    setModsDropdownOpen(false);
                    toggleBtn.focus();
                }
                if (userDropdownList.classList.contains('show-dropdown')) {
                    setUserDropdownOpen(false);
                    userToggleBtn.focus();
                }
            }
        });

        // Lógica Matemática do Priority+
        function adjustNav() {
            if (!wrapper || !primaryList || !overflowList || !modsDropdown) return;

            while (overflowList.firstElementChild) {
                primaryList.appendChild(overflowList.firstElementChild);
            }

            modsDropdown.style.visibility = 'hidden';
            modsDropdown.style.width = '0';
            setModsDropdownOpen(false);

            const getChildrenWidth = () => {
                let total = 0;
                for (let i = 0; i < primaryList.children.length; i++) {
                    total += primaryList.children[i].offsetWidth;
                }
                return total;
            };

            if (getChildrenWidth() > wrapper.clientWidth) {
                modsDropdown.style.visibility = 'visible';
                modsDropdown.style.width = 'auto';

                while (getChildrenWidth() > wrapper.clientWidth && primaryList.children.length > 0) {
                    overflowList.insertBefore(primaryList.lastElementChild, overflowList.firstElementChild);
                }
            }
        }

        if (window.ResizeObserver) {
            const observer = new ResizeObserver(adjustNav);
            observer.observe(wrapper);
        } else {
            window.addEventListener('resize', adjustNav);
        }

        setTimeout(adjustNav, 50);
    });
</script>