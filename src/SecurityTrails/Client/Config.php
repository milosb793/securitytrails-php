<?php

namespace SecurityTrails\Client;

use SecurityTrails\Utils\Util;

/**
 * Class Config
 *
 * This class contains all user settings in static field.
 * User can use .env file to initialize settings
 * or to pass an array with settings.
 * All available parameters and their defaults
 * are listed in the DEFAULTS constant
 *
 * @package SecurityTrails\Client
 */
class Config
{
    /**
     * Default settings
     */
    const DEFAULTS = [
        'api_key'                        => null,
        'api_version'                    => 1,
        'request_throttle_requests'      => true,
        'request_retry_failed_requests'  => true,
        'request_throttle_sleep_seconds' => 1,
        'request_max_attempts'           => 3,
        'headers'                        => null,
    ];

    /**
     * Path to env file
     * @var
     */
    private static $env_path;

    /**
     * User settings array
     * @var array
     */
    private static $user_settings = [];

    private function __construct() { }

    /**
     * Get default settings if user haven't
     * specified any
     *
     * @param $overrides
     * @return array
     */
    public static function defaults($overrides)
    {
        self::$user_settings = self::DEFAULTS;

        foreach (self::$user_settings as $key => $val) {
            $key = strtolower(trim($key));

            if (!empty($overrides[$key])) {
                self::$user_settings[$key] = $overrides[$key];
            }
        }

        self::$env_path                  = null;
        self::$user_settings['env_path'] = self::$env_path;

        return self::$user_settings;
    }

    /**
     * Get settings from .env file
     *
     * @param string $path_to_env
     * @param string $env_filename
     * @return array
     */
    public static function env($path_to_env = '', $env_filename = '.env')
    {
        self::$user_settings             = Util::loadEnv($path_to_env, $env_filename);
        self::$env_path                  = $path_to_env . $env_filename;
        self::$user_settings['env_path'] = self::$env_path;

        return self::$user_settings;
    }

    /**
     * Check does settings option
     * exists in given array
     *
     * @param $key
     * @return bool
     */
    private static function keyExists($key)
    {
        $key  = strtolower($key);
        $keys = array_keys(self::DEFAULTS);

        return in_array($key, $keys);
    }

    /**
     * Get default settings for given option name,
     * and initialize it to default if not set
     *
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public static function default($key)
    {
        if (empty($key)) {
            throw new \Exception("Invalid settings key provided: {$key}");
        }

        $key = strtolower($key);
        if (isset(self::$user_settings[$key])) {
            return self::$user_settings[$key];

        } elseif (self::keyExists($key)) {
            self::$user_settings[$key] = self::DEFAULTS[$key];

            return self::$user_settings[$key];

        } else {
            throw new \Exception("Invalid settings key provided: {$key}");
        }

    }

    /**
     * Set given settings option if it's valid
     *
     * @param $key
     * @param $value
     * @throws \Exception
     */
    public static function set($key, $value)
    {
        if (empty($key) || !self::keyExists($key)) {
            throw new \Exception("Invalid settings option provided: {$key}");
        }

        self::$user_settings[strtolower($key)] = $value;
    }


}