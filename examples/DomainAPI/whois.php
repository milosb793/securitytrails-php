<?php

ini_set('memory_limit', -1);
require_once dirname(dirname(__DIR__)) .'/vendor/autoload.php';

use SecurityTrails\Api\DomainApi;
use SecurityTrails\Client\SecurityTrailsClient;
use SecurityTrails\Resources\Record;


$client_env = SecurityTrailsClient::make('.env');

/**
 * WHOIS
 */
$whois = $client_env->domain()->whois('walmart.com');
