<?php

namespace SecurityTrails\Utils;

/**
 * Class Validator
 *
 * Class used for certain validations
 *
 * @package SecurityTrails\Utils
 */
abstract class Validator
{
    /**
     * Check whether domain is in valid format
     *
     * @param $domain
     * @return bool
     */
    public static function isDomainValid($domain)
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN) !== false;
    }

    /**
     * Validate domain
     *
     * @param $domain
     * @return bool
     * @throws \Exception
     */
    public static function validateDomain($domain)
    {
        if (!self::isDomainValid($domain)) {
            throw new \Exception("Domain: {$domain} is invalid!");
        }

        return true;
    }

}