<?php
/*
* @file        tables_generate.php
* @author      Roger Craveiro Guilherme
* @date        2026-05-20
* @description Main Orchestrator for ABCD Statistics: Generates tables based on user selection, processes data, and renders output. Handles charset conversion, multi-loop execution, and output formatting (HTML/Excel/Word). Integrates with existing configuration files and provides a unified interface for statistics generation.
* @changelog   2026-05-20: Refactored by Roger C. Guilherme - Initial version.
*/

session_start();
include("../common/get_post.php");
include("../config.php");
include("../lang/admin.php");
include("../lang/dbadmin.php");
include("../lang/statistics.php");

$sys_charset = isset($charset) && trim($charset) != "" ? strtolower(trim($charset)) : (isset($meta_encoding) && trim($meta_encoding) != "" ? strtolower(trim($meta_encoding)) : 'iso-8859-1');

// EXPORT INTERCEPTOR (Excel / Word / Window)
if (isset($arrHttp["html"]) && trim($arrHttp["html"]) != "") {
    $opcao = isset($arrHttp["Opcion"]) ? $arrHttp["Opcion"] : "";

    if ($opcao == "W") {
        header("Content-type: application/vnd.ms-excel; charset=$sys_charset");
        header("Content-Disposition: attachment; filename=estatisticas.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
    } elseif ($opcao == "D") {
        header("Content-type: application/vnd.ms-word; charset=$sys_charset");
        header("Content-Disposition: attachment; filename=estatisticas.doc");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=$sys_charset\"></head><body style='font-family: Arial, sans-serif;'>";
    echo "<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #000; padding: 5px; text-align: left; } th { background-color: #f2f2f2; font-weight:bold; }</style>";
    echo stripslashes($arrHttp["html"]);

    if ($opcao == "P") {
        echo "<script>window.print();</script>";
    }
    echo "</body></html>";
    die;
}

$x = explode('|', $arrHttp["base"]);
$arrHttp["base"] = $x[0];
if (!isset($arrHttp["Opcion"])) $arrHttp["Opcion"] = "";
$encabezado = isset($arrHttp["encabezado"]) ? "&encabezado=S" : "";

$backtoscript = "../common/inicio.php";
include("../common/header.php");
include("../common/inc_get-dbinfo.php");
?>

<body>
    <script src="../dataentry/js/lr_trim.js"></script>
    <script src="../../assets/js/echarts.min.js"></script>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #preview_panel,
            #preview_panel * {
                visibility: visible;
            }

            #preview_panel {
                position: absolute;
                left: 0;
                top: 0;
                width: 100% !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            #stats_toolbar,
            #stats_toolbar * {
                display: none !important;
                visibility: hidden !important;
            }

            .analytics-card {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border: none !important;
                box-shadow: none !important;
                margin-bottom: 40px !important;
            }

            .echart-container {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        #preview_panel {
            flex: 1;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            min-width: 0;
            min-height: 500px;
            max-height: 85vh;
            overflow: auto;
        }
    </style>

    <script language="javascript">
        var strValidChars = "0123456789$";

        function toggleLayer(whichLayer) {
            var layers = ['useextproc', 'useextable', 'createtable'];
            layers.forEach(function(layer) {
                var elem = document.getElementById(layer);
                if (!elem) return;
                if (layer === whichLayer) {
                    elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none';
                } else {
                    elem.style.display = 'none';
                    var selects = elem.getElementsByTagName('select');
                    for (var i = 0; i < selects.length; i++) selects[i].selectedIndex = 0;
                }
            });
        }

        function alternarModoBusca(modo) {
            var inputMfn = document.getElementById('Mfn');
            var inputTo = document.getElementById('to');
            var inputExpresion = document.getElementById('Expresion');
            var selectExpr = document.getElementById('Expr');
            var btnBuscar = document.getElementById('btnBuscar');
            var btnSalvar = document.getElementById('btnSalvarBusca');
            var inputDesc = document.getElementById('Descripcion');

            if (modo === 'mfn') {
                inputMfn.disabled = false;
                inputTo.disabled = false;
                inputMfn.value = "1";
                inputTo.value = "<?php echo trim($arrHttp['MAXMFN']); ?>";
                inputExpresion.disabled = true;
                if (selectExpr) selectExpr.disabled = true;
                btnBuscar.disabled = true;
                btnSalvar.disabled = true;
                inputDesc.disabled = true;
            } else {
                inputMfn.disabled = true;
                inputTo.disabled = true;
                inputMfn.value = "";
                inputTo.value = "";
                inputExpresion.disabled = false;
                if (selectExpr) selectExpr.disabled = false;
                btnBuscar.disabled = false;
                btnSalvar.disabled = false;
                inputDesc.disabled = false;
            }
        }

        function popularExpressaoTabela(selectObj) {
            var selected = selectObj.options[selectObj.selectedIndex];
            var expr = selected.getAttribute('data-expr');
            var inputExpresion = document.getElementById('Expresion');
            var radioSearch = document.querySelector('input[name="fonte_dados"][value="search"]');

            if (expr && expr.trim() !== "") {
                inputExpresion.value = expr;
                radioSearch.checked = true;
                alternarModoBusca('search');
            }
        }

        function EnviarFormaUnificada() {
            var fonte = document.querySelector('input[name="fonte_dados"]:checked').value;

            if (fonte === 'mfn') {
                var de = Trim(document.forma1.Mfn.value);
                var a = Trim(document.forma1.to.value);
                for (var i = 0; i < de.length; i++) {
                    if (strValidChars.indexOf(de.charAt(i)) == -1) {
                        alert("<?php echo $msgstr["especificarvaln"] ?>");
                        return;
                    }
                }
                for (var i = 0; i < a.length; i++) {
                    if (strValidChars.indexOf(a.charAt(i)) == -1) {
                        alert("<?php echo $msgstr["especificarvaln"] ?>");
                        return;
                    }
                }
                if (Number(de) <= 0 || Number(a) <= 0 || Number(de) > Number(a) || Number(a) > <?php echo $arrHttp["MAXMFN"] ?>) {
                    alert("<?php echo $msgstr["numfr"] ?>");
                    return;
                }
                document.forma1.Opcion.value = "MFN";
            } else {
                if (Trim(document.forma1.Expresion.value) == "") {
                    alert("<?php echo $msgstr["selreg"] ?>");
                    return;
                }
                document.forma1.Opcion.value = "BUSQUEDA";
            }

            if (document.forma1.proc.selectedIndex < 1 && document.forma1.tables.selectedIndex < 1 && document.forma1.rows.selectedIndex < 1 && document.forma1.cols.selectedIndex < 1) {
                alert("<?php echo $msgstr["seltab"] ?>");
                return;
            }

            if (document.forma1.proc.selectedIndex > 0) document.forma1.Accion.value = "Procesos";
            if (document.forma1.tables.selectedIndex > 0) document.forma1.Accion.value = "Tablas";
            if (document.forma1.rows.selectedIndex > 0 || document.forma1.cols.selectedIndex > 0) document.forma1.Accion.value = "Variables";

            AtualizarDashboard();
        }

        function AtualizarDashboard() {
            var area = document.getElementById('results_area');
            var toolbar = document.getElementById('stats_toolbar');
            area.innerHTML = '<div class="stats-spinner"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br><?php echo $msgstr["processing"]; ?></div>';

            var formData = new FormData(document.forma1);
            var params = new URLSearchParams(formData).toString();

            fetch('stats_ajax.php?' + params)
                .then(function(response) {
                    if (!response.ok) throw new Error('Request error');
                    return response.text();
                })
                .then(function(html) {
                    area.innerHTML = html;
                    setTimeout(RenderCharts, 100);
                    if (typeof toolbar !== 'undefined' && toolbar) toolbar.style.display = 'block';
                })
                .catch(function(err) {
                    area.innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
                });
        }

        function ExportarStats(opcao) {
            if (opcao === 'P') {
                window.print();
                return;
            }
            var htmlContent = document.getElementById('results_area').innerHTML;
            var form = document.sendto;
            form.html.value = htmlContent;
            form.Opcion.value = opcao;
            form.target = "_self";
            form.submit();
        }

        function Buscar() {
            var base = document.forma1.base.value;
            var cipar = document.forma1.cipar.value;
            var Url = "../dataentry/buscar.php?Opcion=formab&Target=s&Tabla=Expresion&base=" + base + "&cipar=" + cipar;
            var msgwin = window.open(Url, "Buscar", "menu=no, resizable,scrollbars,width=750,height=400");
            msgwin.focus();
        }

        function CopiarExpresion() {
            var sel = document.getElementById('Expr');
            var Expr = sel.options[sel.selectedIndex].value;
            document.forma1.Expresion.value = Expr;
        }

        function GuardarBusqueda() {
            document.savesearch.Expresion.value = Trim(document.forma1.Expresion.value);
            if (document.savesearch.Expresion.value == "") {
                alert("<?php echo $msgstr["faltaexpr"] ?>");
                return;
            }
            var Descripcion = document.getElementById('Descripcion').value;
            if (Trim(Descripcion) == "") {
                alert("<?php echo $msgstr["errsave"] ?>");
                return;
            }
            document.savesearch.Descripcion.value = Descripcion;
            var winl = (screen.width - 300) / 2;
            var wint = (screen.height - 200) / 2;
            var msgwin = window.open("", "savesearch", "menu=no,status=yes,width=300, height=200,left=" + winl + ",top=" + wint);
            msgwin.focus();
            document.savesearch.submit();
        }
    </script>

    <?php if (isset($arrHttp["encabezado"])) include("../common/institutional_info.php"); ?>
    <div class="sectionInfo">
        <div class="breadcrumb">
            <?php echo $msgstr["stats"] . ": " . $arrHttp["base"] ?>
        </div>
        <div class="actions">
            <?php include "../common/inc_back.php"; ?>
        </div>
        <div class="spacer">&#160;</div>
    </div>
    <?php include "../common/inc_div-helper.php"; ?>

    <div class="middle form">
        <div class="formContent">
            <div class="dashboard-wrapper">
                <div id="config_panel">
                    <form name="forma1" method="post" action="tables_generate.php" onsubmit="Javascript:return false">
                        <?php if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=s>\n"; ?>
                        <input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
                        <input type=hidden name=cipar value=<?php echo $arrHttp["base"] ?>.par>
                        <input type=hidden name=Opcion>
                        <input type=hidden name=Accion>

                        <div class="stat-option-group">
                            <div class="stat-option-header" onclick="toggleLayer('useextproc')">
                                <strong>1. <?php echo $msgstr["stat_use_exist_pr"]; ?></strong>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div id="useextproc" class="stat-option-content">
                                <select name="proc">
                                    <option value=""><?php echo $msgstr["seltab"] ?></option>
                                    <?php
                                    $file = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/proc.cfg";
                                    if (!file_exists($file)) $file = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/proc.cfg";
                                    if (file_exists($file)) {
                                        foreach (file($file) as $value) {
                                            $value = trim($value);
                                            if ($value != "") {
                                                $t = explode('||', $value);
                                                echo "<option value=\"" . urlencode($value) . "\">" . trim($t[0]) . "</option>";
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="or-separator"><?php echo $msgstr["or"] ?? "OU"; ?></div>

                        <div class="stat-option-group">
                            <div class="stat-option-header" onclick="toggleLayer('useextable')">
                                <strong>2. <?php echo $msgstr["exist_tb"]; ?></strong>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div id="useextable" class="stat-option-content">
                                <select name="tables" onchange="popularExpressaoTabela(this)">
                                    <option value=""><?php echo $msgstr["seltab"] ?></option>
                                    <?php
                                    $file_tabs = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/tabs.cfg";
                                    if (!file_exists($file_tabs)) $file_tabs = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/tabs.cfg";
                                    if (file_exists($file_tabs)) {
                                        foreach (file($file_tabs) as $value) {
                                            $value = trim($value);
                                            if ($value != "") {
                                                $t = explode('|', $value);
                                                $expr = isset($t[4]) ? trim($t[4]) : "";
                                                echo "<option value=\"" . urlencode($value) . "\" data-expr=\"" . htmlspecialchars($expr, ENT_QUOTES) . "\">" . trim($t[0]) . "</option>";
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="or-separator"><?php echo $msgstr["or"] ?? "OU"; ?></div>

                        <div class="stat-option-group">
                            <div class="stat-option-header" onclick="toggleLayer('createtable')">
                                <strong>3. <?php echo $msgstr["stat_create_tmp_tb"] ?></strong>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div id="createtable" class="stat-option-content">
                                <?php
                                $file_stat = $db_path . $arrHttp["base"] . "/def/" . $_SESSION["lang"] . "/stat.cfg";
                                if (!file_exists($file_stat)) $file_stat = $db_path . $arrHttp["base"] . "/def/" . $lang_db . "/stat.cfg";
                                $fp_stat = file_exists($file_stat) ? file($file_stat) : array();
                                ?>
                                <div class="input-row">
                                    <div class="input-group">
                                        <label><?php echo $msgstr["stat_rows_by"] ?></label>
                                        <select name="rows">
                                            <option value=""></option>
                                            <?php
                                            foreach ($fp_stat as $value) {
                                                $value = trim($value);
                                                if ($value != "") {
                                                    $t = explode('|', $value);
                                                    echo "<option value=\"" . urlencode($value) . "\">" . trim($t[0]) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label><?php echo $msgstr["stat_cols_by"] ?></label>
                                        <select name="cols">
                                            <option value=""></option>
                                            <?php
                                            foreach ($fp_stat as $value) {
                                                $value = trim($value);
                                                if ($value != "") {
                                                    $t = explode('|', $value);
                                                    echo "<option value=\"" . urlencode($value) . "\">" . trim($t[0]) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 25px; padding-top: 15px; border-top: 2px solid #eee;">
                            <h4 style="margin-bottom: 15px; font-size: 14px; color: #333;">4. <?php echo $msgstr["data_source"]; ?> (<?php echo $msgstr["generateoutput"] ?>)</h4>

                            <div class="stat-option-group" style="padding: 10px; border-style: dashed; margin-bottom: 10px; background-color: #fafafa;">
                                <label style="font-weight: bold; font-size: 13px; cursor: pointer; display: block;">
                                    <input type="radio" name="fonte_dados" value="mfn" checked onclick="alternarModoBusca('mfn')">
                                    <?php echo $msgstr["r_mfnr"] ?> (<?php echo $msgstr["entire_database"]; ?>)
                                </label>
                                <div class="input-row" style="margin-top: 8px; margin-left: 20px;">
                                    <div class="input-group">
                                        <label><?php echo $msgstr["r_desde"] ?></label>
                                        <input type="text" name="Mfn" id="Mfn" value="1">
                                    </div>
                                    <div class="input-group">
                                        <label><?php echo $msgstr["r_hasta"] ?></label>
                                        <input type="text" name="to" id="to" value="<?php echo trim($arrHttp["MAXMFN"]); ?>">
                                    </div>
                                </div>
                                <small style="margin-left: 20px;">Max MFN: <?php echo trim($arrHttp["MAXMFN"]); ?></small>
                            </div>

                            <div class="stat-option-group" style="padding: 10px; border-style: dashed; background-color: #fafafa;">
                                <label style="font-weight: bold; font-size: 13px; cursor: pointer; display: block;">
                                    <input type="radio" name="fonte_dados" value="search" onclick="alternarModoBusca('search')">
                                    <?php echo $msgstr["r_busqueda"] ?> (<?php echo $msgstr["filtered"]; ?>)
                                </label>
                                <div style="margin-top: 8px; margin-left: 20px;">
                                    <?php
                                    $file_search = $db_path . $arrHttp["base"] . "/pfts/" . $_SESSION["lang"] . "/search_expr.tab";
                                    if (!file_exists($file_search)) $file_search = $db_path . $arrHttp["base"] . "/pfts/" . $lang_db . "/search_expr.tab";

                                    if (file_exists($file_search)) {
                                        echo "<select id='Expr' class='form-control' style='margin-bottom: 8px; width: 100%;' disabled onchange='CopiarExpresion()'>";
                                        echo "<option value=''>-- " . $msgstr["copysearch"] . " --</option>";
                                        $fp_search = file($file_search);
                                        foreach ($fp_search as $value) {
                                            $value = trim($value);
                                            if ($value != "") {
                                                $pp = explode('|', $value);
                                                if (isset($pp[1])) {
                                                    echo "<option value='" . htmlspecialchars($pp[1], ENT_QUOTES) . "'>" . htmlspecialchars($pp[0], ENT_QUOTES) . "</option>";
                                                }
                                            }
                                        }
                                        echo "</select>";
                                    }
                                    ?>
                                    <textarea name="Expresion" id="Expresion" style="height: 60px; width: 100%;" placeholder="<?php echo $msgstr['search_expression']; ?>" disabled></textarea>

                                    <div class="input-row" style="margin-top: 8px; gap: 5px;">
                                        <button class="bt-green" type="button" onclick="Buscar()" title="<?php echo $msgstr['create_search']; ?>" id="btnBuscar" disabled style="padding: 5px 10px;">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <input type="text" name="Descripcion" id="Descripcion" placeholder="<?php echo $msgstr['name_to_save']; ?>" style="flex:1;" disabled>
                                        <button class="bt-gray" type="button" onclick="GuardarBusqueda()" title="<?php echo $msgstr['save_search']; ?>" id="btnSalvarBusca" disabled style="padding: 5px 10px;">
                                            <i class="far fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button class="bt-blue" type="button" onclick="EnviarFormaUnificada()" style="width: 100%; margin-top: 15px; height: 40px; font-size: 16px;">
                                <i class="fa fa-play"></i> <?php echo $msgstr["generate_statistics"]; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <form name=savesearch action=../dataentry/busqueda_guardar.php method=post target=savesearch>
                    <input type=hidden name=base value=<?php echo $arrHttp["base"] ?>>
                    <input type=hidden name=Expresion value="">
                    <input type=hidden name=Descripcion value="">
                </form>

                <div id="preview_panel">
                    <div id="stats_toolbar" style="display:none; margin-bottom: 15px; padding: 10px; background: #eee; border-radius: 4px;">
                        <strong><?php echo $msgstr["sendto"]; ?>:</strong> &nbsp;
                        <button class="bt-blue" type="button" onclick="ExportarStats('W')"><i class="fa fa-file-excel"></i> <?php echo $msgstr["excel"] ?? "Excel"; ?></button>
                        <button class="bt-blue" type="button" onclick="ExportarStats('D')"><i class="fa fa-file-word"></i> <?php echo $msgstr["word"] ?? "Word"; ?></button>
                        <button class="bt-blue" type="button" onclick="ExportarStats('P')"><i class="fas fa-desktop"></i> <?php echo $msgstr["window"] ?? "Window"; ?></button>
                    </div>

                    <div id="results_area">
                        <p style="text-align:center; color:#999; margin-top:100px;">
                            <?php echo $msgstr["select_params_generate"]; ?>
                        </p>
                    </div>
                </div>
            </div> 
            
            <?php include("inc_stat_menu.php"); ?>

        </div>
    </div>
    <form name="sendto" method="post" action="tables_generate.php">
        <input type="hidden" name="html" value="">
        <input type="hidden" name="Opcion" value="">
        <input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
        <?php if (isset($arrHttp["encabezado"])) echo "<input type=hidden name=encabezado value=s>"; ?>
    </form>

    <script>
        function RenderCharts() {
            const containers = document.querySelectorAll('.echart-container');
            containers.forEach(container => {
                try {
                    const b64Data = container.getAttribute('data-chart');
                    if (!b64Data) return;

                    const binStr = window.atob(b64Data);
                    const bytes = new Uint8Array(binStr.length);
                    for (let i = 0; i < binStr.length; i++) {
                        bytes[i] = binStr.charCodeAt(i);
                    }

                    const jsonString = new TextDecoder('utf-8').decode(bytes);
                    const data = JSON.parse(jsonString);

                    if (data.labels.length === 0) {
                        container.innerHTML = "<p style='text-align:center; color:#999; margin-top:150px;'><?php echo $msgstr['not_enough_data']; ?></p>";
                        return;
                    }

                    const myChart = echarts.init(container);
                    const option = {
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'shadow'
                            }
                        },
                        legend: {
                            type: 'scroll',
                            top: 0,
                            padding: [0, 50]
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '20%',
                            containLabel: true
                        },
                        toolbox: {
                            show: true,
                            feature: {
                                magicType: {
                                    type: ['line', 'bar', 'stack']
                                },
                                restore: {},
                                saveAsImage: {
                                    title: '<?php echo $msgstr['export_chart']; ?>'
                                }
                            }
                        },
                        dataZoom: [{
                                type: 'inside',
                                start: 0,
                                end: data.labels.length > 15 ? 30 : 100
                            },
                            {
                                type: 'slider',
                                bottom: 0,
                                start: 0,
                                end: data.labels.length > 15 ? 30 : 100
                            }
                        ],
                        xAxis: {
                            type: 'category',
                            data: data.labels
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: data.series
                    };
                    myChart.setOption(option);
                    window.addEventListener('resize', () => myChart.resize());
                } catch (e) {
                    console.error("Chart Error: ", e);
                    container.innerHTML = "<p style='color:#d9534f; text-align:center; margin-top:150px;'><i class='fas fa-exclamation-triangle'></i> <?php echo $msgstr['chart_error']; ?></p>";
                }
            });
        }
    </script>
    <?php include("../common/footer.php"); ?>