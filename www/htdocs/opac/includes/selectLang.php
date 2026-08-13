<?php
function selectLang()
{
    global $lang, $db_path;

    // Define $lang2 com fallback
    $lang2 = $_REQUEST['lang'] ?? $lang;

    // Ensures that $lang is one of the available folders
    if (!file_exists($db_path . "opac_conf/$lang2/lang.tab")) {
        $lang2 = "en";
    }
?>
    <style>
        #language-selector-wrapper ul#abcd-lang-menu>li>a {
            color: #333333 !important;
            background-color: #ffffff !important;
            transition: background-color 0.2s, color 0.2s;
        }

        #language-selector-wrapper ul#abcd-lang-menu>li>a:hover {
            color: #0d6efd !important;
            background-color: #f1f3f5 !important;
        }

        #language-selector-wrapper ul#abcd-lang-menu>li>a.is-selected {
            color: #0d6efd !important;
            background-color: #e2e6ea !important;
            font-weight: bold !important;
        }
    </style>

    <form name="changelanguage" id="changelanguage" method="get" style="display:none;">
        <?php
    // KEEP THE URL PARAMETERS: This is critical to maintain the user's context when changing languages.
    foreach ($_GET as $key => $value) {
        if ($key != 'lang' && $key != 'submit') {
            // Se o parâmetro for um array (ex: Busca Avançada enviando camp[] e oper[])
            if (is_array($value)) {
                foreach ($value as $item) {
                    // Garante que o item interno é uma string antes de converter
                    if (is_string($item)) {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($item) . '">';
                    }
                }
            } else {
                // Se for uma string normal (ex: Busca Livre)
                echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
            }
        }
    }
        ?>
        <input type="hidden" name="lang" id="lang_hidden" value="<?php echo htmlspecialchars($lang2); ?>">
    </form>

    <div class="dropdown" id="language-selector-wrapper">
        <button class="btn dropdown-toggle d-flex align-items-center gap-2 px-2" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit; border: none; background: transparent; box-shadow: none;">
            <i class="fas fa-globe"></i>
            <span class="text-uppercase fw-bold" style="font-size: 0.85rem;"><?php echo $lang2; ?></span>
        </button>

        <ul id="abcd-lang-menu" class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="languageDropdown" style="min-width: 150px; border-radius: 8px; overflow: hidden;">
            <?php
            $fp = file($db_path . "opac_conf/$lang2/lang.tab");
            foreach ($fp as $value) {
                if (trim($value) != "") {
                    $a = explode("=", $value);
                    $code = trim($a[0]);
                    $label = trim($a[1]);

                    $active_class = ($lang2 == $code) ? "is-selected" : "";

                    // JS call via form ID
                    echo "<li>";
                    echo "<a class=\"dropdown-item py-2 $active_class\" href=\"javascript:void(0);\" onclick=\"document.getElementById('lang_hidden').value='$code'; document.getElementById('changelanguage').submit();\">";
                    echo $label;
                    echo "</a>";
                    echo "</li>";
                }
            }
            ?>
        </ul>
    </div>
<?php
}
?>