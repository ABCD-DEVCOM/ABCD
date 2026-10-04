<?php

/**
 * Name: CISISGateway.php
 * Author: Roger C. Guilherme
 * Description: Gateway class for interacting with the CISIS/WXIS system
 * 
 * Created on: 2026-08-15
 */

class CISISGateway
{
    private $wxisHost;
    private $wxisActionBase;
    private $apiRootPath;
    private $lastUrl;

    public function __construct($dbConfig, $appConfig)
    {
        $this->wxisHost = $appConfig['wxis_host'];
        $this->apiRootPath = $appConfig['api_root_path'];
        $this->wxisActionBase = $appConfig['cisis_path'][IS_WINDOWS ? 'windows' : 'linux']
            . $dbConfig['cisis_version']
            . 'wxis'
            . $appConfig['exe_extension'];
    }

    public function getRecordByMfn($dbPath, $mfn, $dcMappingFile, $format = 'dc')
    {
        $metadataFormatMap = [
            'dc'     => 'OAI_DC',
            'native' => 'ISIS',
        ];
        $metadataFormat = $metadataFormatMap[$format] ?? 'OAI_DC';

        $params = [
            'database' => $dbPath,
            'expression' => $mfn,
            'metadata_format' => $metadataFormat,
        ];

        // O formato "dc" depende de mapping_file (crosswalk .pft ou .i2x). "native" usa a tabela embutida no XIS.
        if ($metadataFormat === 'OAI_DC') {
            if (empty($dcMappingFile)) {
                return null;
            }
            $params['mapping_file'] = $this->apiRootPath . '/resources/mappings/' . $dcMappingFile;
        }

        $url = $this->buildWxisUrl('getrecord.xis', $params);
        $this->lastUrl = $url;

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $responseXml = @file_get_contents($url, false, $context);

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
        $this->lastUrl = $url;

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $responseText = @file_get_contents($url, false, $context);

        if ($responseText === false) {
            return null;
        }

        return $responseText;
    }

    public function getLastUrl()
    {
        return $this->lastUrl;
    }

    private function buildWxisUrl($isisScript, $params)
    {
        $protocol = "http://";
        $host = preg_replace('/^https?:\/\//', '', $this->wxisHost);
        $scriptPath = $this->apiRootPath . '/resources/xis/' . $isisScript;

        $request = $protocol . $host . $this->wxisActionBase . "?IsisScript=" . $scriptPath;

        foreach ($params as $key => $value) {
            $request .= "&" . urlencode($key) . "=" . urlencode($value);
        }

        return $request;
    }
}
