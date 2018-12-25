<?php

namespace SecurityTrails\Api;

use SecurityTrails\Client\Config;
use SecurityTrails\Client\SecurityTrailsClient;

/**
 * Class SecurityTrailsAbstractApi
 *
 * @package SecurityTrails\Api
 */
class SecurityTrailsAbstractApi
{
    private   $api_base_url;
    private   $api_version;
    protected $client;
    private   $headers;

    /**
     * SecurityTrails API hostname
     */
    const SCT_HOST = 'api.securitytrails.com';

    /**
     * Default querystring name used for
     * authentication
     */
    const API_KEY_HEADER = 'apikey';

    /**
     * Template for API URL
     */
    const API_BASE = "https://" . self::SCT_HOST . "/v%VERSION";

    /**
     * One response could contain
     * max. 100 of records
     */
    const PAGE_SIZE = 100;

    /**
     *  To avoid 429 (Too Many Requests) status code
     * it's recommended to wait an second after sequence of
     * five requests
     */
    const RATE_LIMIT = 5;

    /**
     * SecurityTrailsAbstractApi constructor.
     *
     * @param SecurityTrailsClient $client
     * @throws \Exception
     */
    public function __construct(SecurityTrailsClient $client)
    {
        $this->client = $client;
        $this->setAPIBaseUrl();
        $this->headers = Config::default('headers') ?? $this->getDefaultHeaders();
    }

    /**
     * Set API version if it's supported
     * Check @isSupportedAPIVersion method for
     * available versions
     *
     * @param $version
     * @throws \Exception
     */
    public function setAPIVersion($version)
    {
        if (!$this->isSupportedAPIVersion($version)) {
            throw new \Exception('The given API version is not supported!');
        }

        $this->api_version = $version;
    }

    /**
     * Get API version
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAPIVersion()
    {
        return $this->api_version ?? $this->api_version = Config::default('api_version');
    }

    /**
     * Generate API URL for given API version
     *
     * @param null $version
     * @throws \Exception
     */
    public function setAPIBaseUrl($version = null)
    {
        $version = $version ?? $this->getAPIVersion();

        $this->setAPIVersion($version);
        $this->api_base_url = str_replace('%VERSION', $version, self::API_BASE);
    }

    /**
     * Default API URL getter
     *
     * @return mixed
     */
    public function getAPIBaseUrl()
    {
        return $this->api_base_url;
    }

    /**
     * Check whether given version number
     * is supported
     *
     * @param $version
     * @return bool
     */
    public function isSupportedAPIVersion($version)
    {
        $supported_versions = ['v1'];

        return in_array("v{$version}", $supported_versions);
    }

    /**
     * Get global headers
     *
     * @return array|mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Update headers array used to perform
     * each request (used if not overrode within request call)
     *
     * @param array $headers
     * @throws \Exception
     */
    public function setHeaders($headers = [])
    {
        if (!is_array($headers)) {
            throw new \Exception('Headers must be an associative array!');
        }

        if (empty($headers)) {
            $headers = $this->getDefaultHeaders();
        }

        foreach ($headers as $key => $val) {
            $headers[strtolower($key)] = $val;
        }

        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Get default headers array
     *
     * @return array
     * @throws \Exception
     */
    public function getDefaultHeaders()
    {
        return [
            "content-type"       => "application/json",
            self::API_KEY_HEADER => Config::default('api_key'),
        ];
    }

    /**
     * Extract pagination details from given
     * list of attributes
     *
     * Returns:
     *
     * [ page_current, calculated_max_page]
     *
     * @param array $opts
     * @return array
     * @throws \Exception
     */
    public function getPaginationDetails(array $opts)
    {
        $page_current      = $opts['page_current'] ?? 1;
        $page_max          = $opts['page_max'] ?? 1;
        $limit             = $opts['limit'] ?? -1;
        $computed_max_page = ($limit == -1) ? $page_max : intval(ceil($limit / self::PAGE_SIZE));

        $is_invalid = !is_numeric($page_current) || $page_current <= 0 || $page_current > $computed_max_page ||
            !is_numeric($computed_max_page) || $computed_max_page < 0 || $computed_max_page > 100;

        if ($is_invalid) {
            throw new \Exception("Invalid pagination details! Current page: {$page_current}, Max page: {$computed_max_page}");
        }

        return [$page_current, $computed_max_page];
    }

    /**
     * Throttle requests
     *
     * Sleep between sequence of request for specified
     * time (`request_throttle_sleep_seconds`), to avoid 429 error.
     * This feature can be turned off by setting `request_throttle_requests` to 0
     *
     * @param $identificator
     * @return string - milliseconds slept
     * @throws \Exception
     */
    public function throttle($identificator)
    {
        if (!$this->client->http_client->throttleRequests()) {
            return null;
        }

        $num_of_requests = self::RATE_LIMIT - 1; // counting zero
        $time_to_wait    = Config::default('request_throttle_sleep_seconds');

        return $this
            ->client
            ->http_client
            ->throttle($identificator, $num_of_requests, $time_to_wait);
    }

    public function response($data, $opts = [])
    {
        $limit = $opts['limit'] ?? -1;
        $output = array_slice($data ?? [], 0, $limit);

        return [count($output), $output];
    }

    /**
     * @param $url
     * @param array $opts
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function fetch($url, $opts = [])
    {
        $response = $this->client->http_client->get($url, [
            'headers' => $opts['headers'] ?? self::getDefaultHeaders()
        ]);

        return $this->response($response, $opts);
    }

    /**
     * @param $url
     * @param string $throttle_key
     * @param array $opts
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function fetchManyRecords($url, $throttle_key = '', $opts = [])
    {
        $payload = $opts['payload'] ?? [];
        list($current_page, $max_page) = $this->getPaginationDetails($opts);
        $output = [];

        while ($current_page <= $max_page) {
            $querystring = $this->client->http_client->arrayToQuerystring(['page' => $current_page]);

            if ($opts['method'] == 'post') {
                $response = $this->client->http_client->post($url . $querystring, [
                    'headers' => $opts['headers'] ?? $this->getDefaultHeaders(),
                    'payload' => $payload
                ]);

            } else {
                $response = $this->client->http_client->get($url . $querystring, [
                    'headers' => $opts['headers'] ?? $this->getDefaultHeaders(),
                ]);
            }

            $records = $response['records'] ?? [];

            if (empty($records)) {
                break;
            }

            $output = array_merge($output, $records);
            $current_page++;

            $this->throttle($throttle_key);
        }

        return $this->response($output, $opts);
    }
}