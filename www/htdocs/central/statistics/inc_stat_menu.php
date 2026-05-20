<?php
if (isset($_SESSION["permiso"]["CENTRAL_STATCONF"]) or isset($_SESSION["permiso"]["CENTRAL_ALL"])) {
?>
    <div class="config-panel-wrapper" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
        <h4 style="margin-top: 0; color: #333; font-size: 15px; margin-bottom: 15px;">
            <i class="fas fa-cog"></i> <strong><?php echo $msgstr["stats_conf"]; ?></strong>
        </h4>

        <div class="config-grid">
            <a class="config-card" href="javascript:MenuConfigure('stats_var')">
                <i class="fas fa-sliders-h"></i><span><?php echo $msgstr["stat_cfg_vars"]; ?></span>
            </a>
            <a class="config-card" href="javascript:MenuConfigure('stats_tab')">
                <i class="fas fa-table"></i><span><?php echo $msgstr["stat_cfg_tabs"]; ?></span>
            </a>
            <a class="config-card" href="javascript:MenuConfigure('stats_proc')">
                <i class="fas fa-project-diagram"></i><span><?php echo $msgstr["stat_cfg_procs"]; ?></span>
            </a>
        </div>
    </div>

    <form name="configure_menu" method="post" action="">
        <input type="hidden" name="base" value="<?php echo htmlspecialchars($arrHttp["base"]); ?>">
        <input type="hidden" name="Opcion" value="update">
        <input type="hidden" name="from" value="statistics">
        <?php if (isset($arrHttp["encabezado"])) echo "<input type='hidden' name='encabezado' value='s'>"; ?>
    </form>

    <script>
        function MenuConfigure(Option) {
            var form = document.forms['configure_menu'];
            if (!form) return;
            var act = "";
            switch (Option) {
                case "stats_var": act = "config_vars.php"; break;
                case "stats_tab": act = "tables_cfg.php"; break;
                case "stats_proc": act = "proc_cfg.php"; break;
            }
            form.action = act;
            form.submit();
        }
    </script>
<?php
}
?>