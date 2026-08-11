<?php

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

        $dbConfig = $configDatabases[$databaseName];
        $gateway = new CISISGateway($dbConfig, $configApp);

        // If an MFN was passed in the URL, fetch a single record
        if ($mfn) {
            self::getSingleRecord($mfn, $databaseName, $dbConfig, $gateway);
        } else {
            // --- 1st CHANGE: Pass $databaseName to the method ---
            self::searchRecords($databaseName, $dbConfig, $gateway, $configSearch);
        }
    }

    private static function getSingleRecord($mfn, $databaseName, $dbConfig, $gateway)
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $mfn)) {
            json_response(['error' => 'Invalid record identifier.'], 400);
        }

        $recordXml = $gateway->getRecordByMfn($dbConfig['database_path'], $mfn, $dbConfig['mapping']);

        if (empty($recordXml)) {
            json_response(['error' => "Record '{$mfn}' not found in database '{$databaseName}'."], 404);
        }

        $recordJson = self::parseIsisXmlToJson($recordXml, $mfn);
        json_response($recordJson);
    }

    // --- 2nd CHANGE: Receive $databaseName as the first parameter ---
    private static function searchRecords($databaseName, $dbConfig, $gateway, $configSearch)
    {
        $from = (int)($_GET['from'] ?? 0) + 1;
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $query = $_GET['q'] ?? '$';

        // --- ADVANCED SEARCH TRANSLATOR (FINAL AND CORRECT VERSION) ---
        $expression = '$';
        if ($query !== '$') {
            $cisisQuery = $query;
            // Use the '$databaseName' key (e.g., 'marc') to fetch the configuration
            $searchableFields = $configSearch[$databaseName] ?? [];

            // 1. Find and translate all 'field:value' patterns
            $cisisQuery = preg_replace_callback(
                // The regex captures: field:value or field:"value with spaces"
                '/(\w+):(".*?"|\S+)/',
                function ($matches) use ($searchableFields) {
                    $field = strtolower($matches[1]);
                    $term = trim($matches[2], '"');

                    // Check if the field (e.g., 'author') exists in our map
                    if (isset($searchableFields[$field])) {
                        $prefix = $searchableFields[$field];
                        // Build the CISIS expression: e.g., (AU_FERREIRA)
                        return '(' . $prefix . strtoupper($term) . ')';
                    }

                    // If the field is not mapped, return the original term for free search
                    return $matches[0];
                },
                $cisisQuery
            );

            $operatorMap = [' AND ' => ' * ', ' OR '  => ' + ', ' NOT ' => ' ^ '];
            $expression = str_ireplace(array_keys($operatorMap), array_values($operatorMap), $cisisQuery);
        }
        // --- END OF TRANSLATOR ---

        // The rest of the function is correct and needs no changes.
        $mfnListResponse = $gateway->search($dbConfig['database_path'], $expression, $from, $limit);

        if ($mfnListResponse === null) {
            json_response(['error' => 'An error occurred while communicating with the WXIS engine.'], 502);
        }

        $totalHits = 0;
        $mfns = [];
        $parts = explode('|', rtrim($mfnListResponse, '|'));
        if (count($parts) > 0 && strpos($parts[0], 'TOTAL=') === 0) {
            $totalHits = (int)str_replace('TOTAL=', '', $parts[0]);
            array_shift($parts);
        }
        $mfns = array_filter($parts);

        $records = [];
        foreach ($mfns as $mfn) {
            if (empty(trim($mfn))) continue;
            $recordXml = $gateway->getRecordByMfn($dbConfig['database_path'], $mfn, $dbConfig['mapping']);
            if (!empty($recordXml)) {
                $records[] = self::parseIsisXmlToJson($recordXml, $mfn);
            }
        }

        $response = [
            'search_info' => [
                'total_hits' => $totalHits,
                'limit' => $limit,
                'from' => $from - 1,
                'query_submitted' => $query,
                'query_executed' => $expression,
            ],
            'records' => $records,
        ];

        json_response($response);
    }

    private static function parseIsisXmlToJson($xmlString, $mfn)
    {
        libxml_use_internal_errors(true);

        // --- MAIN FIX ---
        // Remove "dc:" and "oai_dc:" prefixes from XML to simplify it for the parser.
        $xmlString = str_replace(['dc:', 'oai_dc:'], '', $xmlString);
        // --- END OF FIX ---

        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            return ['error' => 'Failed to process WXIS response.'];
        }

        $metadata = [];
        $metadata['mfn'] = $mfn;

        $fields = [];
        // Now that prefixes are removed, a simple children() works.
        foreach ($xml->children() as $field_node) {
            $tag = $field_node->getName();
            $parsed_content = self::parseNode($field_node);
            $fields[$tag][] = $parsed_content;
        }
        $metadata['fields'] = $fields;

        return $metadata;
    }


    private static function parseNode($node)
    {
        $children = $node->children();

        if (count($children) == 0) {
            return trim((string)$node);
        }

        $data = [];

        // --- CORRECTED EXTRACTION LOGIC ---
        $fullText = (string)$node;
        $childrenText = '';

        foreach ($children as $child) {
            $child_tag = $child->getName();
            $child_value = trim((string)$child);
            $childrenText .= $child_value; // Concatenate the children's text
            $data[$child_tag] = $child_value;
        }

        // Subtract children's text from full text to isolate indicators
        $indicators = trim(str_replace($childrenText, '', $fullText));

        if (!empty($indicators)) {
            $data['_'] = $indicators;
        }
        // --- END OF EXTRACTION LOGIC ---

        return $data;
    }
}
