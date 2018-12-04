<?php

ini_set('memory_limit', -1);
require_once dirname(dirname(__DIR__)) .'/vendor/autoload.php';

use SecurityTrails\Client\SecurityTrailsClient;

$client_env = SecurityTrailsClient::make('.env');

/**
 * Associated
 */
$associated = $client_env->api("domain")->associated('walmart.com');
$associated = $client_env->api("domain")->associated('walmart.com', ['page_max' => 10, 'page_current' => 1]);
$associated = $client_env->api("domain")->associated('walmart.com', ['page_max' => 10, 'page_current' => 1, 'limit' => 110]);
$associated = $client_env->api("domain")->associated('amazon.com', ['page_max' => 100, 'page_current' => 1]);
$associated = $client_env->api("domain")->associated('amazon.com', ['page_max' => 101, 'page_current' => 1]);
$associated = $client_env->api("domain")->associated('amazon.com', ['page_max' => 100, 'page_current' => 1]);

print_r($associated);