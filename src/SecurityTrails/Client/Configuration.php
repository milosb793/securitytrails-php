<?php

namespace SecurityTrails\Client;

use Dotenv\Dotenv;

class Configuration
{
    const DEFAULTS = [
        'api_version'                    => 1,
        'request_throttle_requests'      => true,
        'request_retry_failed_requests'  => true,
        'request_sleep_between_requests' => 0.5,
        'request_max_attempts'           => 3,
    ];

    private static $path_to_env;
    private static $env_filename;

    private static $settings = [];

    private function __construct() { }

    public static function defaults($overrides)
    {
        self::$settings = self::DEFAULTS;

        foreach (self::$settings as $key => $val) {
            $key = strtolower(trim($key));

            if (!empty($overrides[$key])) {
                self::$settings[$key] = $overrides[$key];
            }
        }

        self::$env_filename = null;
        self::$path_to_env  = null;

        return self::$settings;
    }

    public static function env($path_to_env = '', $env_filename = '.env')
    {
        $dotenv = new Dotenv($path_to_env, $env_filename);

        self::$env_filename = $env_filename;
        self::$path_to_env  = $path_to_env;
        self::$settings     = $dotenv->load();

        return self::$settings;
    }

    public static function default($key, $settings)
    {
        $key = strtolower(trim($key));

        // Check does key exists in predefined settings
        if (isset(self::DEFAULTS[$key])) {
            if ($settings[$key] != null) {
                return self::DEFAULTS[$key] ?? null;
            } else {
                return $settings[$key];
            }
        } else {
            throw new \Exception("The settings for `{$key}` does not exist!");
        }

    }

}