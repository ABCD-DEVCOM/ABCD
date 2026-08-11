<?php

class DatabaseHandler {
    
    public static function handle($uriSegments, $configDatabases) {
        // Remove o nome do recurso ('databases') para ver se há um ID/nome específico
        array_shift($uriSegments);
        $databaseName = $uriSegments[0] ?? null;

        if ($databaseName) {
            self::getDatabaseByName($databaseName, $configDatabases);
        } else {
            self::getAllDatabases($configDatabases);
        }
    }

    private static function getAllDatabases($configDatabases) {
        $response = [];
        foreach ($configDatabases as $key => $db) {
            $response[] = [
                'key' => $key,
                'name' => $db['name'],
                'description' => $db['description'],
            ];
        }
        json_response($response);
    }

    private static function getDatabaseByName($name, $configDatabases) {
        if (isset($configDatabases[$name])) {
            $db = $configDatabases[$name];
            // Não expomos o caminho completo do servidor na resposta
            $response = [
                'key' => $name,
                'name' => $db['name'],
                'description' => $db['description'],
                'cisis_version' => $db['cisis_version']
            ];
            json_response($response);
        } else {
            json_response(['error' => "Base de dados '{$name}' nao encontrada."], 404);
        }
    }
}