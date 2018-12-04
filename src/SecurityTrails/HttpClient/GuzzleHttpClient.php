<?php


namespace SecurityTrails\HttpClient;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SecurityTrails\Client\Config;
use SecurityTrails\HttpClient\Throttle\ThrottleRequestInterface;
use SecurityTrails\HttpClient\HttpClient;

/**
 * Class GuzzleHttpClient
 *
 * Guzzle HTTP client relayed on HttpClient
 *
 * @package SecurityTrails\HttpClient
 */
class GuzzleHttpClient extends HttpClient
{
    /**
     * @var \GuzzleHttp\Client
     */
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
     * Time to sleep between sequence of requests in seconds
     * @var int|float
     */
    private $throttle_sleep_seconds;

    /**
     * GuzzleHttpClient constructor.
     *
     * @param array $settings
     * @throws Exception
     */
    public function __construct(array $settings = [])
    {
        $this->http_client = new \GuzzleHttp\Client();

        $this->throttle_requests = Config::default('request_throttle_requests');
        if ($this->throttle_requests) {
            $this->request_throttler = new Throttle\RequestThrottler();
        }

        $this->retry_failed_requests  = Config::default('request_retry_failed_requests');
        $this->throttle_sleep_seconds = Config::default('request_throttle_sleep_seconds');
        $this->max_attempts           = Config::default('request_max_attempts');
    }

    /**
     * Set custom request throttler
     *
     * @param ThrottleRequestInterface $throttle_client
     */
    public function setCustomRequestThrottler(ThrottleRequestInterface $throttle_client)
    {
        $this->throttle_requests = true;
        $this->request_throttler = $throttle_client;
    }

    /**
     * Throttle requests
     *
     * Sleep between sequence of request for specified
     * time (`request_throttle_sleep_seconds`), to avoid 429 error.
     * This feature can be turned off by setting `request_throttle_requests` to 0
     *
     * @param $identification_key
     * @param $unit_number
     * @param $interval_sec
     * @return string - milliseconds slept
     */
    public function throttle($identification_key, $unit_number, $interval_sec)
    {
        return $this->request_throttler->throttle($identification_key, $unit_number, $interval_sec);
    }

    /**
     * Get request max attempts number
     *
     * @return int|mixed
     */
    public function getMaxAttempts()
    {
        return $this->max_attempts ?? 1;
    }

    /**
     * Set request max attempts number
     *
     * @param int $attempts
     */
    public function setMaxAttempts(int $attempts)
    {
        if (is_numeric($attempts) && $attempts > 0) {
            $this->max_attempts = $attempts;
        }
    }

    /**
     * Get sleeping seconds between request sequence
     *
     * @return mixed
     */
    public function getSleepSecondsBetweenRequestSequence()
    {
        return $this->throttle_sleep_seconds;
    }

    /**
     * Get sleeping seconds between request sequence
     *
     * @param $seconds
     */
    public function setSleepSecondsBetweenRequestSequence($seconds)
    {
        if (is_float($seconds) || is_integer($seconds)) {
            $this->throttle_sleep_seconds = $seconds;
        }
    }

    /**
     * Generate throttle unique identifier
     * for certain requests sequence based on
     * user API key and given string
     *
     * @param string $string
     * @return string
     * @throws Exception
     */
    public function generateThrottleIdentifier(string $string)
    {
        return md5(Config::default('api_key') . ":{$string}");
    }

    /**
     * Check whether throttling is enabled
     *
     * @return bool
     */
    public function throttleRequests()
    {
        return $this->throttle_requests;
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
        $attempts = 1;
        if ($this->retry_failed_requests) {
            $attempts = $this->getMaxAttempts();
        }

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
     * @throws Exception
     */
    public function post(string $endpoint, array $arguments = [])
    {
        /**
         * Wrap whole request so make it repeat itself until
         * response is not null or attempts got decreased to zero
         */

        $data     = null;
        $attempts = 1;
        if ($this->retry_failed_requests) {
            $attempts = $this->getMaxAttempts();
        }

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