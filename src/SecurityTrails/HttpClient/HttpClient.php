<?php

namespace SecurityTrails\SecurityTrails\HttpClient;


use Psr\Http\Message\ResponseInterface;

abstract class HttpClient implements \RequestInterface
{
    public abstract function get(string $url, array $settings = []);

    public abstract function post(string $url, array $settings = []);

    /**
     * Build a querystring by given associative array
     *
     * @param array $query_arr
     * @param bool  $append - if append is set to true, querystring will be generated
     * without question mark on start, else question mark will be set on the start
     * @return string
     */
    public function arrayToQuerystring(array $query_arr, $append = false)
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

    /**
     * Create JSON string
     *
     * @param array $parameters
     * @return null|string
     */
    public function createJsonBody(array $parameters)
    {
        return (count($parameters) === 0) ? null : json_encode($parameters, empty($parameters) ? JSON_FORCE_OBJECT : 0);
    }

    /**
     * Parse response body
     * @param ResponseInterface $response
     * @return mixed|string
     */
    public function parseRequestBody(ResponseInterface $response)
    {
        $body = $response->getBody()->__toString();

        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $content = json_decode($body, true, 34242423);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $content;
            }
        }

        return $body;
    }

    /**
     * Create output of response body
     * @param ResponseInterface $response
     * @return array
     */
    public function response(ResponseInterface $response)
    {
        return [
            'code'    => $response->getStatusCode(),
            'data'    => $this->parseRequestBody($response) ?? null,
            'message' => $response->getReasonPhrase(),
        ];
    }
}