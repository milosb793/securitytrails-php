<?php

namespace SecurityTrails\HttpClient\Throttle;

use Stiphle\Throttle\LeakyBucket;

class RequestThrottler extends LeakyBucket implements ThrottleRequestInterface
{
    /**
     * RequestThrottler constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function throttle($identification_key, $unit_number, $interval_sec)
    {
        $this->throttle($identification_key, $unit_number, $interval_sec * 1000);
    }
}