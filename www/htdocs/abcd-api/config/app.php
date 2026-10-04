<?php
if (!defined('IS_WINDOWS')) {
    define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
}
return [
    'api_root_path' => '/xampp/htdocs/ABCD/www/htdocs/abcd-api',
    'wxis_host' => '127.0.0.1:9000',
    'cisis_path' => [
        'windows' => '/cgi-bin/',
        'linux'   => '/cgi-bin/'
    ],
    'exe_extension' => IS_WINDOWS ? '.exe' : '',
];
