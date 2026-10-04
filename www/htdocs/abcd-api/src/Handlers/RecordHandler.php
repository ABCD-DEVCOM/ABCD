<?php

/**
 * Name: RecordHandler.php
 * Author: Roger C. Guilherme
 * Description: Handler for record-related API requests
 * 
 * Created on: 2026-08-15
 */

class RecordHandler
{
    public static function handle($uriSegments, $configApp, $configDatabases)
    {
        $configSearch = require __DIR__ . '/../../config/search_config.php';

        $databaseName = $uriSegments[1] ?? null;
        $mfn = $uriSegments[2] ?? null;

        if (!$databaseName) {
            json_response(['error' => 'Database name is required. Usage: /records/{database_name}'], 400);
        }
        if (!isset($configDatabases[$databaseName])) {
            json_response(['error' => "Database '{$databaseName}' not found."], 404);
        }

        $allowedFormats = ['dc', 'native'];
        $format = strtolower($_GET['format'] ?? 'dc');
        if (!in_array($format, $allowedFormats, true)) {
            json_response(['error' => 'Invalid format. Use one of: ' . implode(', ', $allowedFormats)], 400);
        }

        $dbConfig = $configDatabases[$databaseName];
        $gateway = new CISISGateway($dbConfig, $configApp);

        if ($mfn) {
            self::getSingleRecord($mfn, $databaseName, $dbConfig, $gateway, $format);
        } else {
            self::searchRecords($databaseName, $dbConfig, $gateway, $configSearch, $format);
        }
    }

    private static function getSingleRecord($mfn, $databaseName, $dbConfig, $gateway, $format)
    {
        if (!preg_match('/^\d+$/', $mfn)) {
            json_response(['error' => 'Invalid record identifier.'], 400);
        }

        // Backward compatibility and auto-discovery
        $dcMapping = $dbConfig['formats']['dc'] ?? $dbConfig['mapping'] ?? null;
        if ($format === 'dc' && empty($dcMapping)) {
            json_response(['error' => "Format 'dc' is not available for database '{$databaseName}' yet. Please create a mapping in the Visual Data Mapper."], 404);
        }

        $recordXml = $gateway->getRecordByMfn($dbConfig['database_path'], $mfn, $dcMapping, $format);

        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            die("<div style='background:#111; color:#0f0; padding:20px; font-family:monospace;'>" . 
                "<h3>API DEBUG MODE</h3>" .
                "<b>DB Path sent to WXIS:</b> " . $dbConfig['database_path'] . "<br><br>" .
                "<b>WXIS Request URL:</b> <a href='" . $gateway->getLastUrl() . "' style='color:#0ff' target='_blank'>" . $gateway->getLastUrl() . "</a><br><br>" .
                "<b>RAW WXIS Response:</b><br><pre>" . htmlspecialchars((string)$recordXml) . "</pre></div>");
        }

        if (empty(trim((string)$recordXml))) {
            json_response(['error' => "Record '{$mfn}' not found in database '{$databaseName}'."], 404);
        }

        if (strpos(trim((string)$recordXml), 'WXIS|fatal error|') === 0) {
            json_response(['error' => "WXIS Engine Error: " . trim((string)$recordXml)], 500);
        }

        $recordJson = self::parseIsisXmlToJson($recordXml, $mfn, $format);
        json_response($recordJson);
    }

    private static function searchRecords($databaseName, $dbConfig, $gateway, $configSearch, $format)
    {
        $from = (int)($_GET['from'] ?? 0) + 1;
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $query = $_GET['q'] ?? '$';

        $expression = '$';
        if ($query !== '$') {
            $cisisQuery = $query;
            $searchableFields = $configSearch[$databaseName] ?? [];

            $cisisQuery = preg_replace_callback(
                '/(\w+):(".*?"|\S+)/',
                function ($matches) use ($searchableFields) {
                    $field = strtolower($matches[1]);
                    $term = trim($matches[2], '"');

                    if (isset($searchableFields[$field])) {
                        $prefix = $searchableFields[$field];
                        return '(' . $prefix . strtoupper($term) . ')';
                    }
                    return $matches[0];
                },
                $cisisQuery
            );

            $operatorMap = [' AND ' => ' * ', ' OR '  => ' + ', ' NOT ' => ' ^ '];
            $expression = str_ireplace(array_keys($operatorMap), array_values($operatorMap), $cisisQuery);
        }

        $dcMapping = $dbConfig['formats']['dc'] ?? $dbConfig['mapping'] ?? null;
        if ($format === 'dc' && empty($dcMapping)) {
            json_response(['error' => "Format 'dc' is not available for database '{$databaseName}' yet. Please create a mapping in the Visual Data Mapper."], 404);
        }

        $mfnListResponse = $gateway->search($dbConfig['database_path'], $expression, $from, $limit);
        $totalHits = 0;
        $mfns = [];
        $fallbackMode = false;

        // Fallback for corrupted/missing dictionaries on global search
        if ($mfnListResponse === null || strpos(trim((string)$mfnListResponse), 'WXIS|fatal error|') === 0 || strpos((string)$mfnListResponse, 'trmread/punt') !== false) {
            if ($expression === '$') {
                $fallbackMode = true;
                for ($i = $from; $i < $from + $limit; $i++) {
                    $mfns[] = (string)$i;
                }
            } else {
                json_response([
                    'error' => 'WXIS Engine Error: Inverted file (dictionary) might be missing or corrupted. Please generate the inverted file in ABCD Utilities.', 
                    'raw_wxis_response' => trim((string)$mfnListResponse)
                ], 500);
            }
        } else {
            $parts = explode('|', rtrim($mfnListResponse, '|'));
            if (count($parts) > 0 && strpos($parts[0], 'TOTAL=') === 0) {
                $totalHits = (int)str_replace('TOTAL=', '', $parts[0]);
                array_shift($parts);
            }
            $mfns = array_filter($parts);
        }

        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            die("<div style='background:#111; color:#0f0; padding:20px; font-family:monospace;'>" . 
                "<h3>API DEBUG MODE</h3>" .
                "<b>DB Path sent to WXIS:</b> " . $dbConfig['database_path'] . "<br><br>" .
                "<b>WXIS Request URL:</b> <a href='" . $gateway->getLastUrl() . "' style='color:#0ff' target='_blank'>" . $gateway->getLastUrl() . "</a><br><br>" .
                "<b>RAW WXIS Response:</b><br><pre>" . htmlspecialchars((string)$mfnListResponse) . "</pre></div>");
        }

        $records = [];
        foreach ($mfns as $mfn) {
            if (empty(trim((string)$mfn))) continue;
            $recordXml = $gateway->getRecordByMfn($dbConfig['database_path'], $mfn, $dcMapping, $format);
            
            if (!empty(trim((string)$recordXml))) {
                if (strpos(trim((string)$recordXml), 'WXIS|fatal error|') === 0) {
                    $records[] = ['mfn' => $mfn, 'error' => trim((string)$recordXml)];
                    continue;
                }
                
                $parsedJson = self::parseIsisXmlToJson($recordXml, $mfn, $format);
                if (isset($parsedJson['fields']) && count($parsedJson['fields']) > 0) {
                    $records[] = $parsedJson;
                }
            }
        }

        if ($fallbackMode) {
            $totalHits = (count($records) < $limit) ? ($from + count($records) - 1) : -1;
        }

        $response = [
            'search_info' => [
                'total_hits' => $totalHits,
                'limit' => $limit,
                'from' => $from - 1,
                'format' => $format,
                'query_submitted' => $query,
                'query_executed' => $expression,
            ],
            'records' => $records,
        ];

        json_response($response);
    }

    private static function parseIsisXmlToJson($xmlString, $mfn, $format)
    {
        libxml_use_internal_errors(true);
        
        // Remove known namespaces to prevent SimpleXML unbound prefix crashes
        $cleanXml = preg_replace('/(<\/?)(\w+):([^>]+>)/', '$1$3', $xmlString);

        $xml = simplexml_load_string($cleanXml);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = 'Failed to parse XML. ';
            foreach ($errors as $error) {
                $errorMsg .= trim($error->message) . ' | ';
            }
            libxml_clear_errors();
            return ['mfn' => $mfn, 'error' => $errorMsg, 'raw_xml' => htmlspecialchars((string)$xmlString)];
        }

        $metadata = ['mfn' => $mfn, 'format' => $format, 'fields' => []];

        foreach ($xml->children() as $field_node) {
            $tag = $field_node->getName();
            
            // Tratamento especial para o formato "native" (isisxml style=1)
            // Onde as tags vêm como <field tag="245">
            if ($tag === 'field' && isset($field_node['tag'])) {
                $tag = (string)$field_node['tag'];
            }
            
            $metadata['fields'][$tag][] = self::parseNode($field_node);
        }

        return $metadata;
    }

    private static function parseNode($node)
    {
        $children = $node->children();
        if (count($children) == 0) {
            return trim((string)$node);
        }

        $data = [];
        $fullText = (string)$node;
        $childrenText = '';

        foreach ($children as $child) {
            $child_tag = $child->getName();
            $child_value = trim((string)$child);
            $childrenText .= $child_value;
            $data[$child_tag] = $child_value;
        }

        $indicators = trim(str_replace($childrenText, '', $fullText));
        if (!empty($indicators)) $data['_'] = $indicators;

        return $data;
    }
}