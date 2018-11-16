<?php


namespace SecurityTrails\HttpClient;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use SecurityTrails\Client\Configuration;
use SecurityTrails\HttpClient\Throttle\ThrottleRequestInterface;
use SecurityTrails\SecurityTrails\HttpClient\HttpClient;

class GuzzleHttpClient extends HttpClient
{

    private $http_client;

    /*
     * Throttle requests
     * @var bool
     */
    private $throttle_requests;

    /*
     * Client for throttling requests,
     * if $throttle_requests is set to true
     * @var ThrottleRequestsInterface
     */
    public $request_throttler;

    /*
     * Retry failed requests
     * @var bool
     */
    private $retry_failed_requests;

    /*
     * Max attempts to retry failed request,
     * if $retry_failed_requests is set to true
     * @var int
     */
    private $max_attempts;

    /*
     * Time to sleep between requests in seconds
     * @var int|float
     */
    private $sleep_between_requests;

    /**
     * GuzzleHttpClient constructor.
     *
     * @param array $settings
     * @throws Exception
     */
    public function __construct(array $settings = [])
    {
        $this->http_client = new \GuzzleHttp\Client();

        $this->throttle_requests = Configuration::default('request_throttle_requests', $settings);
        if ($this->throttle_requests) {
            $this->request_throttler = new Throttle\RequestThrottler();
        }

        $this->retry_failed_requests  = Configuration::default('request_retry_failed_requests', $settings);
        $this->sleep_between_requests = Configuration::default('request_sleep_between_requests', $settings);
        $this->max_attempts           = Configuration::default('request_max_attempts', $settings);
    }

    public function setCustomRequestThrottler(ThrottleRequestInterface $throttle_client)
    {
        $this->throttle_requests = true;
        $this->request_throttler = $throttle_client;
    }

    public function throttle($identification_key, $unit_number, $interval_sec)
    {
        $this->request_throttler->throttle($identification_key, $unit_number, $interval_sec);
    }

    public function getMaxAttempts()
    {
        return $this->max_attempts ?? 1;
    }

    public function setMaxAttempts(int $attempts)
    {
        if (is_numeric($attempts) && $attempts > 0) {
            $this->max_attempts = $attempts;
        }
    }

    public function getSleepSecondsBetweenRequestSequence()
    {
        return $this->sleep_between_requests;
    }

    public function setSleepSecondsBetweenRequestSequence($seconds)
    {
        if (is_float($seconds) || is_integer($seconds)) {
            $this->sleep_between_requests = $seconds;
        }
    }

    /**
     * Perform GET request
     *
     * @param       $endpoint
     * @param array $arguments - List of accepted arguments: headers :array, auth :array, attempts :int
     * @return null
     * @throws GuzzleException
     */
    public function get(string $endpoint, array $arguments = [])
    {
        /**
         * Wrap whole request so make it repeat itself until
         * response is not null or attempts got decreased to zero
         */

        $data     = null;
        $attempts = $this->getMaxAttempts();

        while ($attempts >= 0 && $data == null) {
            $response = $this->http_client->request('GET', $endpoint, [
                "headers" => $arguments['headers'] ?? [],
                "auth"    => $arguments['auth'] ?? [],
            ]);

            $response_parsed = $this->response($response);
            $data            = $response_parsed['data'];

            $attempts--;
        }

        return $data;
    }

    /**
     * Perform POST request
     *
     * @param       $endpoint
     * @param array $arguments - List of accepted arguments: headers :array, auth :array, attempts :int, payload :array
     * @return array|null
     * @throws GuzzleException
     */
    public function post(string $endpoint, array $arguments = [])
    {
        /**
         * Wrap whole request so make it repeat itself until
         * response is not null or attempts got decreased to zero
         */

        $data     = null;
        $attempts = $this->getMaxAttempts();

        while ($attempts >= 0 && $data == null) {
            $response = $this->http_client->request('POST', $endpoint, [
                'json'    => $this->createJsonBody($arguments['payload'] ?? []),
                "auth"    => $arguments['auth'] ?? [],
                "headers" => $arguments['headers'] ?? [],
            ]);

            $response_parsed = $this->response($response);
            $data            = $response_parsed['data'];

            $attempts--;
        }

        return $data;
    }


}