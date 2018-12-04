<?php

namespace SecurityTrails\Utils;

use Dotenv\Dotenv;

/**
 * Class Util
 *
 * @package SecurityTrails\Utils
 */
abstract class Util
{
    /**
     * Extract file name from file path and
     * return both
     *
     * Returns: [ file_dir, file_name ]
     *
     * @param $full_path
     * @return array
     * @throws \Exception
     */
    public static function explodePath($full_path)
    {
        if (empty($full_path) || !is_string($full_path)) {
            throw new \Exception('Invalid path given!');
        }

        $file_name = basename($full_path);

        return [
            explode($file_name, $full_path)[0] ?? '',
            $file_name,
        ];
    }

    /**
     * Load settings from .env file
     *
     * @param string $path_to_env
     * @param string $env_filename
     * @return array
     */
    public static function loadEnv($path_to_env = '', $env_filename = '.env')
    {
        if (empty($path_to_env)) {
            $path_to_env = dirname(dirname(dirname(__DIR__)));
        }

        $dotenv = new Dotenv($path_to_env, $env_filename);
        $dotenv->load();

        $settings_key_value_pairs = [];
        array_map(function ($key) use (&$settings_key_value_pairs) {
            $settings_key_value_pairs[strtolower($key)] = getenv($key);
        }, $dotenv->getEnvironmentVariableNames());

        return $settings_key_value_pairs;
    }
}