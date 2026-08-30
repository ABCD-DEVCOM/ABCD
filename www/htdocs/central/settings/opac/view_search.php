<?php
/*
* @file        view_search.php
* @description Page for analyzing OPAC search logs.
* @author      Refactored by Roger C. Guilherme
* @date        2026-08-29
*
* CHANGE LOG:
* 2026-08-29 - Massive performance optimization: Streaming read (fopen), 
*              bounded array retention for UI, and lazy geocoding (Top 10 only) to prevent server crashes.
*              Adjusted UI grid for optimal visualization inside ABCD layout.
*/

include("conf_opac_top.php");
$n_wiki_help = "abcd-modules/opac-abcd/opac-admin/tools/search-analytics";
include "../../common/inc_div-helper.php";

$log_dir = $db_path . "/opac_conf/logs/";
$log_files = glob($log_dir . "opac_*.log");
if ($log_files) {
    rsort($log_files);
} else {
    $log_files = [];
}

$arquivo_selecionado = "";
if (isset($_GET['log_file']) && in_array($_GET['log_file'], $log_files)) {
    $arquivo_selecionado = $_GET['log_file'];
} elseif (!empty($log_files)) {
    $arquivo_selecionado = $log_files[0];
}

// Função de geolocalização com timeout rigoroso (2 segundos) para evitar travamento
function geoLocalizacao($ip)
{
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $url = "http://ip-api.com/json/{$ip}?fields=status,lat,lon,city,regionName,country";
    $resposta = @file_get_contents($url, false, $ctx);
    if ($resposta === FALSE) return false;

    $dados = json_decode($resposta, true);
    if ($dados && $dados['status'] === 'success') {
        return [
            'local' => $dados['city'] . ", " . $dados['regionName'] . ", " . $dados['country'],
            'lat' => $dados['lat'],
            'lon' => $dados['lon']
        ];
    }
    return false;
}

$linhas_processadas = 0;
$registros = [];
$contagem_termos = [];
$contagem_ips = []; // Contamos os IPs primeiro, geolocalizamos depois
$limite_tabela = 2500; // Protege o navegador limitando as linhas da tabela

$handle = false;
if (!empty($arquivo_selecionado) && file_exists($arquivo_selecionado)) {
    $handle = @fopen($arquivo_selecionado, "r");
}

if ($handle) {
    while (($linha = fgets($handle)) !== false) {
        $linha = trim($linha);
        if (empty($linha)) continue;

        if (strpos($linha, '|') !== false) {
            $dados = explode('|', $linha);
            $datahora = trim($dados[0] ?? "");
            $ip       = trim($dados[1] ?? "");
            $termo    = strtolower(trim($dados[2] ?? "", " \t\n\r\0\x0B\""));
        } else {
            $dados = explode("\t", $linha);
            if (count($dados) < 3) continue;
            $datahora = $dados[0];
            $ip       = $dados[1];
            $termo    = strtolower(trim($dados[2]));
        }

        if ($ip != "") {
            // Conta os IPs (extremamente rápido em memória)
            $contagem_ips[$ip] = ($contagem_ips[$ip] ?? 0) + 1;

            // Mantém apenas os últimos N registros para a tabela
            $registros[] = [
                'datahora' => $datahora,
                'ip' => $ip,
                'termo' => htmlspecialchars($termo)
            ];

            if (count($registros) > $limite_tabela) {
                array_shift($registros); // Remove o mais antigo do buffer
            }

            if ($termo != '') {
                $contagem_termos[$termo] = ($contagem_termos[$termo] ?? 0) + 1;
            }
        }
        $linhas_processadas++;
    }
    fclose($handle);
}

// Inverte para exibir os mais recentes no topo
$registros = array_reverse($registros);

// Processa Top 10 Termos
arsort($contagem_termos);
$top_termos = array_slice($contagem_termos, 0, 10, true);

// Processa Top 10 IPs e faz Geolocalização APENAS para eles (salva o servidor)
arsort($contagem_ips);
$top_ips = array_slice($contagem_ips, 0, 10, true);

$top_cidades = [];
$marcadores_mapa = [];

foreach ($top_ips as $ip => $qtd) {
    $geo = geoLocalizacao($ip);
    if ($geo) {
        $local_nome = $geo['local'];
        $top_cidades[$local_nome] = ($top_cidades[$local_nome] ?? 0) + $qtd;

        $marcadores_mapa[] = [
            'lat' => $geo['lat'],
            'lon' => $geo['lon'],
            'local' => htmlspecialchars($local_nome, ENT_QUOTES),
            'ip' => $ip,
            'qtd' => $qtd
        ];
    } else {
        $top_cidades["IP Não Rastreado ($ip)"] = $qtd;
    }
}
arsort($top_cidades);
?>
<script>
    var idPage = "general";
</script>
<div class="middle form row m-0">
    <div class="formContent col-2 m-2 p-0">
        <?php include("conf_opac_menu.php"); ?>
    </div>
    <div class="formContent col-9 m-2" style="max-width: 100%; overflow-x: hidden;">

        <h3>
            <?php echo $msgstr['cfg_Research_Analysis']; ?>
            <?php if (!empty($arquivo_selecionado)): ?>
                <small style="font-size: 14px; color: #666;">(<?php echo basename($arquivo_selecionado); ?>)</small>
            <?php endif; ?>
        </h3>

        <!-- Filtro Centralizado -->
        <div style="background-color: var(--abcd-gray-100); border: 1px solid var(--abcd-gray-300); border-radius: 5px; padding: 15px; margin-bottom: 20px; text-align: center;">
            <form method="GET" name="log_selection" style="margin: 0;">
                <label for="log_file" style="font-weight: bold; margin-right: 10px;"><?php echo $msgstr['cfg_select_log_file']; ?></label>
                <select id="log_file" name="log_file" class="textEntry" style="min-width: 300px; padding: 5px;" onchange="this.form.submit()">
                    <?php if (empty($log_files)): ?>
                        <option value=""><?php echo $msgstr['cfg_no_logs_found'] ?? 'Nenhum log encontrado'; ?></option>
                    <?php else: ?>
                        <?php foreach ($log_files as $file): ?>
                            <?php
                            $display_name = basename($file, '.log');
                            $parts = explode('_', $display_name);
                            $date_part = end($parts);
                            @list($ano, $mes) = explode('-', $date_part);

                            $meses = ["", $msgstr['january'] ?? "Jan", $msgstr['february'] ?? "Fev", $msgstr['march'] ?? "Mar", $msgstr['april'] ?? "Abr", $msgstr['may'] ?? "Mai", $msgstr['june'] ?? "Jun", $msgstr['july'] ?? "Jul", $msgstr['august'] ?? "Ago", $msgstr['september'] ?? "Set", $msgstr['october'] ?? "Out", $msgstr['november'] ?? "Nov", $msgstr['december'] ?? "Dez"];
                            $display_text = isset($meses[(int)$mes]) ? $meses[(int)$mes] . " / " . $ano : basename($file);
                            ?>
                            <option value="<?php echo htmlspecialchars($file); ?>" <?php echo ($file == $arquivo_selecionado) ? 'selected' : ''; ?>>
                                <?php echo $display_text; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>

        <?php if ($linhas_processadas > 0): ?>

            <!-- Quadros Lado a Lado para Top 10 -->
            <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                <div style="flex: 1; min-width: 0;">
                    <h5 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;"><i class="fas fa-search"></i> <?php echo $msgstr['cfg_top10_terms'] ?? 'Top 10 Termos Buscados'; ?></h5>
                    <table class="table striped" style="width: 100%;">
                        <thead>
                            <tr>
                                <th><?php echo $msgstr['cfg_search_term']; ?></th>
                                <th style="width: 100px; text-align: center;"><?php echo $msgstr['cfg_quantity']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_termos as $termo => $qtd): ?>
                                <tr>
                                    <td style="word-break: break-all;"><strong><?php echo htmlspecialchars($termo); ?></strong></td>
                                    <td style="text-align: center;"><?php echo $qtd; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="flex: 1; min-width: 0;">
                    <h5 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;"><i class="fas fa-globe-americas"></i> <?php echo $msgstr['cfg_top10_cities'] ?? 'Top 10 Cidades'; ?></h5>
                    <table class="table striped" style="width: 100%;">
                        <thead>
                            <tr>
                                <th><?php echo $msgstr['cfg_city'] ?? 'Ciudad'; ?></th>
                                <th style="width: 100px; text-align: center;"><?php echo $msgstr['cfg_quantity']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_cidades as $cidade => $qtd): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cidade); ?></td>
                                    <td style="text-align: center;"><?php echo $qtd; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botão de Exportação -->
            <div style="text-align: center; margin-bottom: 20px;">
                <h5><i class="fas fa-list"></i> <?php echo $msgstr['cfg_search_list'] ?? 'Lista de Búsqueda'; ?> <small>(Max: <?php echo $limite_tabela; ?>)</small></h5>
                <button id="btnExportarCSV" class="bt bt-green"><i class="far fa-file-excel"></i> <?php echo $msgstr['export_csv'] ?? 'Exportar CSV'; ?></button>
            </div>

            <!-- Tabela Principal do DataTables -->
            <div style="width: 100%; overflow-x: auto;">
                <table class="table striped" id="tabelaLog" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php echo $msgstr['cfg_date_hour'] ?? 'Fecha/Hora'; ?></th>
                            <th style="width: 120px;">IP</th>
                            <th><?php echo $msgstr['cfg_search_term'] ?? 'Término de búsqueda'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <tr>
                                <td><?php echo $registro['datahora']; ?></td>
                                <td><a href="https://ipinfo.io/<?php echo $registro['ip']; ?>" target="_blank" title="Rastrear IP"><?php echo $registro['ip']; ?></a></td>
                                <td style="word-break: break-all;"><?php echo $registro['termo']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($marcadores_mapa)): ?>
                <h5 style="margin-top: 40px; border-bottom: 1px solid #ccc; padding-bottom: 5px;"><i class="fas fa-map-marker-alt"></i> <?php echo $msgstr['cfg_map'] ?? 'Mapa (Top IPs)'; ?></h5>
                <div id="mapa" style="height: 400px; width: 100%; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 30px;"></div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert info" style="background-color: var(--abcd-blue); color: white; padding: 15px; border-radius: 4px;">
                <i class="fas fa-info-circle"></i> <?php echo empty($log_files) ? ($msgstr['cfg_no_logs_found_msg'] ?? 'Nenhum arquivo de log disponível.') : ($msgstr['cfg_log_empty_msg'] ?? 'O log selecionado está vazio.'); ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include("../../common/footer.php"); ?>

<!-- DataTables CSS e JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="/assets/css/leaflet.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="/assets/js/leaflet.js"></script>

<!-- Estilo extra para o DataTables encaixar bem -->
<style>
    .dataTables_wrapper {
        margin-bottom: 30px;
        font-size: 0.9em;
    }

    .dataTables_filter {
        margin-bottom: 10px;
    }

    .dataTables_filter input {
        border: 1px solid #ccc;
        padding: 4px;
        border-radius: 3px;
    }

    table.dataTable.no-footer {
        border-bottom: 1px solid #ddd;
    }
</style>

<script>
    $(document).ready(function() {
        if ($('#tabelaLog').length) {
            $('#tabelaLog').DataTable({
                "paging": true,
                "pageLength": 25,
                "lengthMenu": [
                    [25, 50, 100, 500],
                    [25, 50, 100, 500]
                ],
                "info": true,
                "searching": true,
                "order": [
                    [0, "desc"]
                ], // Ordena por data decrescente
                "language": {
                    "url": "/assets/js/datatable-<?php echo $lang; ?>.json"
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($marcadores_mapa)): ?>
            const mapa = L.map('mapa').setView([-15, -47], 3);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(mapa);

            const marcadores = <?php echo json_encode($marcadores_mapa, JSON_UNESCAPED_UNICODE); ?>;

            marcadores.forEach(m => {
                L.marker([m.lat, m.lon])
                    .addTo(mapa)
                    .bindPopup(`<b>${m.local}</b><br>IP: ${m.ip}<br>Acessos: ${m.qtd}`);
            });
        <?php endif; ?>
    });

    // CSV Simplificado
    document.getElementById('btnExportarCSV')?.addEventListener('click', function() {
        const tabela = document.getElementById('tabelaLog');
        let csv = 'Data/Hora;IP;Termo Pesquisado\n';

        for (let i = 1; i < tabela.rows.length; i++) {
            let row = tabela.rows[i];
            let data = row.cells[0].innerText;
            let ip = row.cells[1].innerText;
            let termo = row.cells[2].innerText.replace(/"/g, '""');
            csv += `"${data}";"${ip}";"${termo}"\n`;
        }

        const blob = new Blob(["\uFEFF" + csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement('a');
        link.setAttribute('href', URL.createObjectURL(blob));
        link.setAttribute('download', 'opac_analytics_export.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>