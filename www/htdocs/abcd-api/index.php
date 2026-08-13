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
            json_response(['error' => 'Invalid or missing API key (X-API-Key).'], 401);
        }

        if ($isWrite && ($keyInfo['scope'] ?? 'read') === 'read') {
            json_response(['error' => 'This key does not have write permission.'], 403);
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
        // This section will be implemented in the next steps
        require __DIR__ . '/src/Handlers/RecordHandler.php';
        require __DIR__ . '/src/Core/CISISGateway.php';
        RecordHandler::handle($uriSegments, $configApp, $configDatabases);
        break;

    case '': // Root of the API
        json_response(['message' => 'Welcome to the ABCD API']);
        break;

    default:
        json_response(['error' => 'Endpoint not found.'], 404);
        break;
}
