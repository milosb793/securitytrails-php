<?php

require_once dirname(__DIR__) . '/creating-clients.php';

/**
 * Filter
 */
$filter = [
    'filter' => [
        'keyword' => 'walmart',
        'tld' => 'com'
    ]
];

//$results = $client_env->search()->filter($filter);
//$results = $client_env->search()->filter($filter, ['page_max' => 100, 'page_current' => 1]);
//$results = $client_env->search()->filter($filter, ['page_max' => 101, 'page_current' => 1]);
//$results = $client_env->api("search")->filter($filter, ['page_max' => 10, 'page_current' => 1, 'limit' => 110]);


/**
 * Invalid filter example
 */
//$filter = [
//    'filter' => [
//        'keyword1' => 'walmart',
//        'tld' => 'com'
//    ]
//];
//$results = $client_env->search()->filter($filter);


print_r($results);