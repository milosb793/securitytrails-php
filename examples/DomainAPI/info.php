<?php

ini_set('memory_limit', -1);
require_once dirname(dirname(__DIR__)) .'/vendor/autoload.php';

use SecurityTrails\Api\DomainApi;
use SecurityTrails\Client\SecurityTrailsClient;
use SecurityTrails\Resources\Record;


$client_env = SecurityTrailsClient::make('.env');

/**
 * Domain info
 */
$info = $client_env->api(DomainApi::class)->info('walmart.com');
$info = $client_env->api("domain")->info('walmart.com');
$info = $client_env->domain()->info('walmart.com');
$info = $client_env->domain->info('walmart.com');
$info = $client_env->domain_api->info('walmart.com');

print_r($info);