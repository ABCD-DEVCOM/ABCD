<?php

/**
 * Name: DatabaseHandler.php
 * Author: Roger C. Guilherme
 * Description: Handler for database-related API requests
 * 
 * Created on: 2026-08-15
 */

class DatabaseHandler
{
    public static function handle($uriSegments, $configDatabases)
    {
        array_shift($uriSegments);
        $databaseName = $uriSegments[0] ?? null;
        if ($databaseName) {
            self::getDatabaseByName($databaseName, $configDatabases);
        } else {
            self::getAllDatabases($configDatabases);
        }
    }
    private static function getAllDatabases($configDatabases)
    {
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
    private static function getDatabaseByName($name, $configDatabases)
    {
        if (isset($configDatabases[$name])) {
            $db = $configDatabases[$name];

            $availableFormats = ['native'];
            if (!empty($db['formats']['dc']) || !empty($db['mapping'])) {
                $availableFormats[] = 'dc';
            }

            $response = [
                'key' => $name,
                'name' => $db['name'],
                'description' => $db['description'],
                'cisis_version' => $db['cisis_version'],
                'available_formats' => $availableFormats,
            ];
            json_response($response);
        } else {
            json_response(['error' => "Base de dados '{$name}' nao encontrada."], 404);
        }
    }
}
