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

        $url = $this->getAPIBaseUrl() . "/domains/list";
        $limit = $opts['limit'] ?? -1;
        list($current_page, $max_page) = $this->getPaginationDetails($opts);
        $throttle_identifier = $this->client->http_client->generateThrottleIdentifier("filter:{$max_page}");

        $output = [];
        while ($current_page <= $max_page) {
            $querystring = $this->client->http_client->arrayToQuerystring(['page' => $current_page]);
            $response = $this->client->http_client->post($url . $querystring, [
                'headers' => $opts['headers'] ?? $this->getDefaultHeaders(),
                'payload' => $filter
            ]);

            $records = $response['records'] ?? [];

            if (empty($records)) {
                break;
            }

            $output = array_merge($output, $records);
            $current_page++;

            $this->throttle($throttle_identifier);
        }

        $output = array_slice($output, 0, $limit);

        return [count($output), $output];
    }

}