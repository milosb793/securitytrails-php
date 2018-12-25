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
    public static function isDomainValid(string $domain)
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN) !== false;
    }

    /**
     * Check whether IPv4 is in valid format
     *
     * @param $ip
     * @return bool
     */
    public static function isIPv4Valid(string $ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check whether IPv6 is in valid format
     *
     * @param $ip
     * @return bool
     */
    public static function isIPv6Valid(string $ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Check is email in a valid format
     *
     * @param $email
     * @return bool
     */
    public static function isEmailValid($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check is string variable valid
     *
     * @param string $string
     * @param int $min_len
     * @return bool
     */
    public static function isValidString(string $string, int $min_len = 1)
    {
        return is_string($string) && strlen($string) >= $min_len;
    }

    /**
     * Check is filter valid
     *
     * @param $filter
     * @return bool
     * @throws \Exception
     */
    public static function isFilterValid(array $filter)
    {
        $filter_keyword   = 'filter';
        $validation_rules = [
            'ipv4'               => function ($ip) { return self::isIPv4Valid($ip); },
            'ipv6'               => function ($ip) { return self::isIPv6Valid($ip); },
            'apex_domain'        => function ($domain) { return self::isDomainValid($domain); },
            'keyword'            => function ($keyword) { return self::isValidString($keyword); },
            'mx'                 => function ($mx) { return self::isDomainValid($mx); },
            'ns'                 => function ($ns) { return self::isDomainValid($ns); },
            'cname'              => function ($cname) { return self::isDomainValid($cname); },
            'subdomain'          => function ($sub) { return self::isValidString($sub); },
            'soa_email'          => function ($soa_email) { return self::isValidString($soa_email); },
            'tld'                => function ($tld) { return self::isValidString($tld); },
            'whois_email'        => function ($email) { return self::isEmailValid($email); },
            'whois_street1'      => function ($street) { return self::isValidString($street); },
            'whois_street2'      => function ($street) { return self::isValidString($street); },
            'whois_street3'      => function ($street) { return self::isValidString($street); },
            'whois_street4'      => function ($street) { return self::isValidString($street); },
            'whois_telephone'    => function ($tel) { return self::isValidString($tel, 5); },
            'whois_postalCode'   => function ($code) { self::isValidString($code, 3); },
            'whois_organization' => function ($org) { return self::isValidString($org); },
            'whois_name'         => function ($name) { return self::isValidString($name); },
            'whois_fax'          => function ($tel) { return self::isValidString($tel, 5); },
            'whois_city'         => function ($city) { return self::isValidString($city, 2); },
        ];

        $all_filter_keywords = array_values(array_keys($validation_rules));

        // if filter is empty somehow
        if (!is_array($filter) || !isset($filter[$filter_keyword]) || empty($filter[$filter_keyword])) {
            throw new \Exception('Filter seems empty or invalid!');
        }

        foreach ($filter[$filter_keyword] as $filter_option => $value) {
            $filter_option = strtolower($filter_option);
            if (array_search($filter_option, $all_filter_keywords) == false) {
                throw new \Exception("Invalid filter option provided: {$filter_option}");
            }

            // validate given filter option
            if (! $validation_rules[$filter_option]($value) ) {
                throw new \Exception("Value for given filter option: {$filter_option} is invalid: {$value}");
            }
        }

        return true;
    }

    /**
     * Check is DSL query valid
     *
     * @param array $query
     * @return bool
     * @throws \Exception
     */
    public static function isDslQueryValid(array $query)
    {
        $query_keyword = 'query';
        $valid = isset($query[$query_keyword]) && !empty($query[$query_keyword]) &&
                 is_string($query[$query_keyword]) && strlen($query[$query_keyword]) > 5 &&
                 strlen($query[$query_keyword]) <= 5000;

        if (!$valid) {
            throw new \Exception("Invalid DSL query given: {$query}");
        }

        return true;
    }

    /**
     * Validate domain
     *
     * @param $domain
     * @return bool
     * @throws \Exception
     */
    public static function validateDomain(string $domain)
    {
        if (!self::isDomainValid($domain)) {
            throw new \Exception("Domain: {$domain} is invalid!");
        }

        return true;
    }

    /**
     * Validate Filter
     *
     * @param $filter
     * @return bool
     * @throws \Exception
     */
    public static function validateFilter(array $filter)
    {
        return self::isFilterValid($filter);
    }

    /**
     * Validate DSL query
     *
     * @param array $query
     * @return bool
     * @throws \Exception
     */
    public static function validateDslQuery(array $query)
    {
        return self::isDslQueryValid($query);
    }

}