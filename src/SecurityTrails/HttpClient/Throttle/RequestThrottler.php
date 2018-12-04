<?php

namespace SecurityTrails\HttpClient\Throttle;

use Stiphle\Throttle\LeakyBucket;

/**
 * Class RequestThrottler
 *
 * Request Throttling class relayed on
 * LuckyBucket throttling class and @throttle method
 *
 * @package SecurityTrails\HttpClient\Throttle
 */
class RequestThrottler extends LeakyBucket implements ThrottleRequestInterface
{
    /**
     * RequestThrottler constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Throttle requests
     *
     * Sleep between sequence of request for
     * specified time (`request_throttle_sleep_seconds`), to avoid 429 error.
     * This feature can be turned off by setting `request_throttle_requests` to 0.
     * Identification key is any string that is used as
     * identification for the throttling.
     *
     * @param string $identification_key
     * @param int    $unit_number - how much of things
     * @param int    $interval_sec - pass @unit_number of items for specified seconds
     * @return int
     */
    public function throttle($identification_key, $unit_number, $interval_sec)
    {
        return parent::throttle($identification_key, $unit_number, $interval_sec * 1000);
    }
}