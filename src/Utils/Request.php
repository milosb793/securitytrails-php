<?php


namespace SecurityTrails\Utils;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Request
{
    /**
     * To change these values, you could extend this class
     * and define your own constants
     */
    const MAX_ATTEMPTS           = 2;
    const SLEEP_BETWEEN_REQUESTS = 0.5;

    public static function getAttempts($custom_attempts = null)
    {
        if ($custom_attempts && is_numeric($custom_attempts)) {
            return $custom_attempts;
        }

        return static::MAX_ATTEMPTS;
    }

    public static function getSleepSecondsBetweenRequestSequence()
    {
        return static::SLEEP_BETWEEN_REQUESTS;
    }

    /**
     * Perform GET request
     *
     * @param       $endpoint
     * @param array $arguments - List of accepted arguments: headers :array, auth :array, attempts :int
     * @return null
     * @throws GuzzleException
     */
    public static function get($endpoint, array $arguments = [])
    {
        $client        = new Client();
        $data          = null;
        $attempts      = static::getAttempts($arguments['attempts'] ?? null);
        $sleep_seconds = static::getSleepSecondsBetweenRequestSequence();

        /**
         * Wrap whole request so make it repeat itself until
         * response is not null or attempts got decreased to zero
         */
        while ($attempts >= 0 && !$data) {

            $response = $client->request('GET', $endpoint, [
                "headers" => $arguments['headers'] ?? [],
                "auth"    => $arguments['auth'] ?? [],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("Can't get page content!", $response->getStatusCode());
            }

            $data = json_decode($response->getBody()->getContents(), true) ?? null;

            if (empty($data)) {
                throw new Exception("Response body is empty!");
            }

            sleep($sleep_seconds);
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
    public static function post($endpoint, array $arguments = [])
    {
        $client        = new Client();
        $data          = null;
        $attempts      = static::getAttempts($arguments['attempts'] ?? null);
        $sleep_seconds = static::getSleepSecondsBetweenRequestSequence();

        /**
         * Wrap whole request so make it repeat itself until
         * response is not null or attempts got decreased to zero
         */
        while ($attempts >= 0 && !$data) {

            $response = $client->request('POST', $endpoint, [
                'json'    => $arguments['payload'] ?? [],
                "auth"    => $arguments['auth'] ?? [],
                "headers" => $arguments['headers'] ?? [],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("Can't get page content!", $response->getStatusCode());
            }

            $data = json_decode($response->getBody()->getContents(), true) ?? null;

            if (empty($data)) {
                throw new Exception("Response body is empty!");
            }

            $attempts--;
            sleep($sleep_seconds);
        }

        return $data;
    }

    /**
     * Build a querystring by given associative array
     *
     * @param array $query_arr
     * @param bool  $append - if append is set to true, querystring will be generated
     * without question mark on start, else question mark will be set on the start
     * @return string
     */
    public static function arrayToQuerystring(array $query_arr, $append = false)
    {
        if (!is_array($query_arr) || empty($query_arr)) {
            return '';
        }

        return (!$append ? '?' : '') .
            implode(
                "&",
                array_map(
                    function ($key) use ($query_arr) { return "{$key}=$query_arr[$key]"; },
                    array_keys($query_arr)
                )
            );
    }
}