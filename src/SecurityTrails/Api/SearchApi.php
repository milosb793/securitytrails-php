<?php


namespace SecurityTrails\Api;

use SecurityTrails\Client\SecurityTrailsClient;
use SecurityTrails\Utils\Validator;

class SearchApi extends SecurityTrailsAbstractApi
{
    public function __construct(SecurityTrailsClient $client)
    {
        parent::__construct($client);
    }

    /**
     * Search Domain (filter)
     *
     * Filter and search specific records using this endpoint.
     * All supported searching keywords:
     *
     * ipv4 (can include a network mask)
     * ipv6
     * mx
     * ns
     * cname
     * subdomain
     * apex_domain
     * soa_email
     * tld
     * whois_email
     * whois_street1
     * whois_street2
     * whois_street3
     * whois_street4
     * whois_telephone
     * whois_postalCode
     * whois_organization
     * whois_name
     * whois_fax
     * whois_city
     * keyword (substring of a hostname, e.g. the value of oa would yield all hostnames containing oa characters)
     *
     * Example of filter payload:
     * ```
     * {
     *  "filter": {
     *   "ipv4": "123.123.123.123",
     *   "apex_domain": "example.com",
     *   "ns": "ns1.example.com",
     *   "tld": "com"
     *   }
     * }
     * ```
     *
     * IMPORTANT: all keywords must be lowercase!
     *
     * @param array $filter
     * @param array $opts
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function filter(array $filter, array $opts = [])
    {
        Validator::validateFilter($filter);

        $url = $this->getAPIBaseUrl() . '/domains/list';
        $throttle_identifier = $this->client->http_client->generateThrottleIdentifier("filter:{$url}");
        $opts['payload'] = $filter;
        $opts['method'] = 'post';

        $data = $this->fetchManyRecords($url, $throttle_identifier, $opts);

        return $data;
    }

    /**
     * Filter and search specific records using our DSL with this endpoint
     *
     * DSL stands for “Domains Specific Language”. It is a way
     * for you to query our Exploration end point with flexible
     * SQL like queries. This document will show you the fields
     * available as well as give examples of how to make queries.
     * The DSL for SecurityTrails is similar to the syntax used
     * for SQL where predicates.
     * Check more on: https://docs.securitytrails.com/docs/how-to-use-the-dsl
     *
     * @param string $query
     * @param array $opts
     * @return array
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function dsl(array $query, array $opts = [])
    {
        Validator::validateDslQuery($query);

        $url = $this->getAPIBaseUrl() . '/domains/list';
        $throttle_identifier = $this->client->http_client->generateThrottleIdentifier("dsl:{$url}");
        $opts['method'] = 'post';
        $opts['payload'] = $query;

        $data = $this->fetchManyRecords($url, $throttle_identifier, $opts);

        return $data;

    }

}