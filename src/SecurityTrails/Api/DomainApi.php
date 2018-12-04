<?php

namespace SecurityTrails\Api;

use SecurityTrails\Client\Config;
use SecurityTrails\HttpClient\GuzzleHttpClient;
use SecurityTrails\Utils\Validator;
use SecurityTrails\Client\SecurityTrailsClient;

/**
 * Class DomainApi
 *
 * Domain API provides information domain specific
 * like domain info, associated domains, WHOIS current info,
 * tags and subdomains data
 *
 * @package SecurityTrails\Api
 */
class DomainApi extends SecurityTrailsAbstractApi
{

    public function __construct(SecurityTrailsClient $client)
    {
        parent::__construct($client);
    }

    /**
     * Get DNS Records for given domain, like:
     * A, AAAA, NS, MX, SOA, and TXT
     *
     * @param       $domain
     * @param array $opts
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function info($domain, array $opts = [])
    {
        Validator::validateDomain($domain);

        $url = parent::getAPIBaseUrl() . "/domain/{$domain}";
        $response = $this->client->http_client->get($url, [
            'headers' => $opts['headers'] ?? parent::getDefaultHeaders()
        ]);

        return $response;
    }

    /**
     * List all subdomains for given domain
     *
     * @param       $domain
     * @param array $opts:
     *     - `limit` (any integer number between 1 and -1 (as infinity)); default: -1
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function subdomains($domain, array $opts = [])
    {
        $limit = $opts['limit'] ?? -1;

        Validator::validateDomain($domain);

        $url = parent::getAPIBaseUrl() . "/domain/{$domain}/subdomains";
        $response = $this->client->http_client->get($url, [
            'headers' => parent::getDefaultHeaders()
        ]);

        $data = $response['subdomains'] ?? [];
        return [count($data), array_slice($data, 0, $limit)];
    }

    /**
     * Returns tags for a given hostname
     *
     * @param       $domain
     * @param array $opts
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function tags($domain, array $opts = [])
    {
        Validator::validateDomain($domain);

        $url = parent::getAPIBaseUrl() . "/domain/{$domain}/tags";
        $response = $this->client->http_client->get($url, [
            'headers' => $opts['headers'] ?? parent::getDefaultHeaders()
        ]);

        $data = $response['tags'] ?? [];

        return $data;
    }

    /**
     * Find all domains that are related to a domain you input
     * Search is based on whois_email or organization_name
     * Results are paginated into packet of 100  WhoisRecord's
     *
     * @param       $domain
     * @param array $opts :
     *  - page_current: an integer >= 0; default: 1
     *  - page_max: an integer >= 0; default: 1
     *  - limit:
     * @return array
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function associated($domain, array $opts = [])
    {
        Validator::validateDomain($domain);

        $url = parent::getAPIBaseUrl() . "/domain/{$domain}/associated";
        $limit = $opts['limit'] ?? -1;
        list($current_page, $max_page) = $this->getPaginationDetails($opts);
        $throttle_identifier = $this->client->http_client->generateThrottleIdentifier("associated:{$max_page}");

        $output = [];
        while ($current_page <= $max_page) {
            $querystring = $this->client->http_client->arrayToQuerystring(['page' => $current_page]);
            $response = $this->client->http_client->get($url . $querystring, [
                'headers' => $opts['headers'] ?? $this->getDefaultHeaders()
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

    /**
     * Returns the current WHOIS data
     * about a given domain with the stats merged together
     *
     * @param       $domain
     * @param array $opts
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function whois($domain, array $opts = [])
    {
        Validator::validateDomain($domain);

        $url = parent::getAPIBaseUrl() . "/domain/{$domain}/whois";
        $response = $this->client->http_client->get($url, [
            'headers' => $opts['headers'] ?? parent::getDefaultHeaders()
        ]);

        $data = $response ?? [];

        return $data;
    }
}