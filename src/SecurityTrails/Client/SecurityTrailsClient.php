<?php

namespace SecurityTrails\Client;

use SecurityTrails\Api\DomainApi;
use SecurityTrails\HttpClient\GuzzleHttpClient;
use SecurityTrails\HttpClient\HttpClient;
use SecurityTrails\Utils\Util;

/**
 * Class SecurityTrailsClient
 *
 * @package SecurityTrails\Client
 */
class SecurityTrailsClient
{
    public $api_key;
    public $http_client;
    public $domain_api;

    /**
     * SecurityTrails constructor.
     *
     * @param       $api_key
     * @param array $opts
     * @throws \Exception
     */
    private function __construct($api_key, array $opts = [])
    {
        $this->api_key     = $this->setAPIKey($api_key);
        $this->http_client = new GuzzleHttpClient($opts);

        $this->domain_api = new DomainApi($this);
    }

    public function __get($name)
    {
        switch ($name):
            case "domain":
                return $this->domain_api;
            default:
                return $this->$name;
        endswitch;
    }

    /**
     * SCT Client can be built by passing array of settings
     * or by passing path to env file
     *
     * @param $opts
     * @return SecurityTrailsClient
     * @throws \Exception
     */
    public static function make($opts)
    {
        if (empty($opts)) {
            throw new \Exception('Invalid settings provided!');
        }

        $client = null;

        if (is_string($opts)) {
            $client = self::makeClientUsingEnvFile($opts);

        } elseif (is_array($opts)) {
            $settings = Config::defaults($opts);
            $client   = self::makeClientUsingArray($settings);
        }

        return $client;
    }

    /**
     * Make Client using .env file
     *
     * @param $env_path - absolute or relative path to .env file
     * @return SecurityTrailsClient
     * @throws \Exception
     */
    public static function makeClientUsingEnvFile($env_path)
    {
        list($env_path, $env_filename) = Util::explodePath($env_path);
        $settings = Config::env($env_path, $env_filename);

        $api_key = $settings['api_key'];

        $client = new self($api_key, $settings);

        return $client;
    }

    /**
     * Make Client using predefined associative array
     * All available settings options
     * are listed in @SecurityTrails\Client\Config class
     *
     * @param array $config
     * @return SecurityTrailsClient
     * @throws \Exception
     */
    public static function makeClientUsingArray(array $config = [])
    {
        $api_key = $config['api_key'] ?? '';
        $client  = new self($api_key, $config);

        return $client;
    }

    /**
     * Change default HttpClient
     *
     * @param HttpClient $http_client
     */
    public function setHttpClient(HttpClient $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * Get API key
     *
     * @return mixed
     */
    public function getAPIKey()
    {
        return $this->api_key;
    }

    /**
     * Set API key
     *
     * @param $api_key
     * @return mixed
     * @throws \Exception
     */
    public function setAPIKey($api_key)
    {
        if (empty($api_key) || !is_string($api_key)) {
            throw new \Exception('Invalid API key provided!');
        }

        return $api_key;
    }


    /**
     * Get .env file path
     *
     * @return mixed
     * @throws \Exception
     */
    public function getEnvPath()
    {
        return Config::default('env_path');
    }

    /**
     * Set .env file path
     *
     * @param string $path
     * @throws \Exception
     */
    public function setEnvPath($path = '.env')
    {
        Config::set('env_path', $path);
    }

    /**
     * Reference to the Domain API
     *
     * @return DomainApi
     */
    public function domain()
    {
        return $this->domain_api;
    }

    /**
     * Facade method for available API-es
     *
     * Certain API could be accessed via attribute,
     * concrete method call (ex. @domain()) or with this method
     * by passing for example `DomainApi::class`, 'domain' or
     * 'DomainApi' string (for Domain API)
     *
     * @param $class
     * @return DomainApi
     * @throws \Exception
     */
    public function api($class)
    {
        switch ($class) {
            case "domain":
            case "SecurityTrails\Api\DomainApi":
            case "DomainApi":
                return self::domain();
            default:
                throw new \Exception('Unsupported API class!');
        }
    }
}