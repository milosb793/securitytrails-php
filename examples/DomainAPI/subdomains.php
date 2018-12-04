<?php

ini_set('memory_limit', -1);
require_once dirname(dirname(__DIR__)) .'/vendor/autoload.php';

use SecurityTrails\Client\SecurityTrailsClient;


$client_env = SecurityTrailsClient::make('.env');

/**
 * Subdomains
 */
$subdomains = $client_env->domain()->subdomains('walmart.com');
$subdomains = $client_env->domain()->subdomains('walmart.com', ['limit' => 2]);

print_r($subdomains);
