<?php
ini_set('memory_limit', -1);
require_once dirname(__DIR__) .'/vendor/autoload.php';

use SecurityTrails\Client\SecurityTrailsClient;

/**
 * Building Clients
 */

$client_arr = SecurityTrailsClient::make([
    'api_key' => '<API_KEY>'
]);

$client_env = SecurityTrailsClient::make('.env');

