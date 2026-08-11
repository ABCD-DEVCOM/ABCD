<?php
header('Content-Type: application/json; charset=utf-8');

// --- CARREGAMENTO E CONFIGURAÇÃO ---
// Os caminhos agora são relativos à raiz da API
$configApp = require __DIR__ . '/config/app.php';
$configDatabases = require __DIR__ . '/config/databases.php';
$configAuth = require __DIR__ . '/config/auth.php';

// --- ROTEAMENTO ---
$requestUri = $_GET['request'] ?? '';
$uriSegments = explode('/', rtrim($requestUri, '/'));
$resource = $uriSegments[0] ?? null;

// --- FUNÇÃO DE RESPOSTA PADRÃO ---
function json_response($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- NOVA FUNÇÃO DE VERIFICAÇÃO DE SEGURANÇA ---
function check_security($uriSegments, $configDatabases, $validKeys)
{
    $method = $_SERVER['REQUEST_METHOD'];
    $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

    if (($uriSegments[0] ?? null) !== 'records') {
        return;
    }

    $databaseName = $uriSegments[1] ?? null;
    if (!$databaseName || !isset($configDatabases[$databaseName])) {
        return;
    }

    $accessLevel = $configDatabases[$databaseName]['access'] ?? 'public';

    // Escrita SEMPRE exige chave válida, independente do nível de acesso da base
    $needsAuth = $isWrite || $accessLevel === 'restricted';

    if ($needsAuth) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        // Hash the incoming key to compare with our stored hashes
        $hashedIncomingKey = $apiKey ? hash('sha256', $apiKey) : null;
        $keyInfo = $validKeys[$hashedIncomingKey] ?? null;

        if ($keyInfo === null) {
            json_response(['error' => 'Chave de API (X-API-Key) invalida ou ausente.'], 401);
        }

        if ($isWrite && ($keyInfo['scope'] ?? 'read') === 'read') {
            json_response(['error' => 'Esta chave nao tem permissao de escrita.'], 403);
        }
    }
}
// --- FIM DA FUNÇÃO DE SEGURANÇA ---


// --- EXECUÇÃO DO "PORTÃO DE ENTRADA" ---
check_security($uriSegments, $configDatabases, $configAuth['keys']);

// --- TRATAMENTO DOS ENDPOINTS ---
switch ($resource) {
    case 'databases':
        require __DIR__ . '/src/Handlers/DatabaseHandler.php';
        DatabaseHandler::handle($uriSegments, $configDatabases);
        break;

    case 'records':
        // Esta seção será implementada nos próximos passos
        require __DIR__ . '/src/Handlers/RecordHandler.php';
        require __DIR__ . '/src/Core/CISISGateway.php';
        RecordHandler::handle($uriSegments, $configApp, $configDatabases);
        break;

    case '': // Raiz da API
        json_response(['message' => 'Bem-vindo à API do ABCD']);
        break;

    default:
        json_response(['error' => 'Endpoint nao encontrado.'], 404);
        break;
}
