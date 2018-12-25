<?php

require_once dirname(__DIR__) . '/creating-clients.php';

/**
 * Valid query
 */
$query = [
    'query' => "keyword = 'walmart' and tld = 'com'"
];

$response = $client_env->search()->dsl($query);
$response = $client_env->search_api->dsl($query);
$response = $client_env->api('search')->dsl($query);
$response = $client_env->search()->dsl($query, ['page_current' => 1, 'page_max' => 10]);
$response = $client_env->search()->dsl($query, ['page_current' => 1, 'page_max' => 10, 'limit' => 205]);
print_r($response);

/**
 * Invalid query
 */

$query = [
    'query' => "foo = 'bar'"
];

$response = $client_env->search()->dsl($query);
print_r($response);