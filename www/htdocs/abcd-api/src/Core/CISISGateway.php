<?php

class CISISGateway
{
    private $wxisHost;
    private $wxisActionBase;
    private $apiRootPath;

    public function __construct($dbConfig, $appConfig)
    {
        $this->wxisHost = $appConfig['wxis_host'];
        $this->apiRootPath = $appConfig['api_root_path'];
        $this->wxisActionBase = $appConfig['cisis_path'][IS_WINDOWS ? 'windows' : 'linux']
            . $dbConfig['cisis_version']
            . 'wxis'
            . $appConfig['exe_extension'];
    }

    public function getRecordByMfn($dbPath, $mfn, $mappingFile)
    {
        $params = [
            'database' => $dbPath,
            'expression' => $mfn,
            'metadata_format' => 'isis2json',
            'mapping_file' => $this->apiRootPath . '/resources/mappings/' . $mappingFile,
        ];

        $url = $this->buildWxisUrl('getrecord.xis', $params);
        $responseXml = file_get_contents($url);

        if ($responseXml === false) {
            return null;
        }

        return mb_convert_encoding($responseXml, 'UTF-8', 'ISO-8859-1');
    }

    public function search($dbPath, $expression, $from = 1, $count = 10)
    {
        $count = max(1, min(100, (int)$count));
        $from  = max(1, (int)$from);

        $params = [
            'database' => $dbPath,
            'expression' => $expression,
            'from' => $from,
            'count' => $count,
        ];

        $url = $this->buildWxisUrl('getidentifiers.xis', $params);

        // Create a stream context with a 10-second timeout to prevent server hanging
        $context = stream_context_create(['http' => ['timeout' => 10]]);

        // The @ symbol suppresses the raw PHP HTML warning if the connection fails,
        // preventing it from breaking the strict JSON response format.
        $responseText = @file_get_contents($url, false, $context);

        if ($responseText === false) {
            // Optional: You can capture the exact error internally for debugging logs
            // $error = error_get_last();
            // error_log("WXIS Connection Error: " . ($error['message'] ?? 'Unknown error'));

            return null;
        }

        // WXIS does not return an HTTP error code if the search result is empty, 
        // so we just process and return the text response it provides.
        return $responseText;
    }

    private function buildWxisUrl($isisScript, $params)
    {
        $protocol = "http://";
        $scriptPath = $this->apiRootPath . '/resources/xis/' . $isisScript;
        $request = $protocol . $this->wxisHost . $this->wxisActionBase . "?IsisScript=" . $scriptPath;
        foreach ($params as $key => $value) {
            $request .= "&" . urlencode($key) . "=" . urlencode($value);
        }
        return $request;
    }
}
