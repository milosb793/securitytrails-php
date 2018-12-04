<?php

ini_set('memory_limit', -1);
require_once dirname(dirname(__DIR__)) .'/vendor/autoload.php';

use SecurityTrails\Client\SecurityTrailsClient;


$client_env = SecurityTrailsClient::make('.env');

/**
 * Tags
 */
$tags = $client_env->domain()->tags('walmart.com');

print_r($tags);