<?php

namespace SecurityTrails;

class SecurityTrailsClient
{
    const API_BASE              = "https://api.securitytrails.com/v%VERSION";
    const SEARCHING_DOMAINS     = self::API_BASE . "/search/list/";
    const STATS_ENDPOINT        = self::API_BASE . "/search/list/stats";
    const DOMAIN_INFO_ENDPOINT  = self::API_BASE . "/domain/%DOMAIN";
    const ASSOCIATED_ENDPOINT   = self::API_BASE . "/domain/%DOMAIN/associated";
    const SUBDOMAINS_ENDPOINT   = self::API_BASE . "/domain/%DOMAIN/subdomains";
    const REVERSE_DNS_IPS       = self::API_BASE . "/ips/list";
    const REVERSE_DNS_IPS_STATS = self::API_BASE . "/ips/stats";
    const PAGE_SIZE             = 100;

    private $api_key;
    private $http_client;



    /**
     * SCT Client can be built by passing array of settings
     * or by passing path to env file
     *
     * @param $opts
     * @throws \Exception
     */
    public static function make($opts)
    {
        // if there is no params provided, use default
        if (empty($opts)) {
            throw new \Exception('No API key provided!');

        } elseif (is_string($opts)) {
            return self::makeClientUsingEnv($opts);

        } elseif (is_array($opts)) {
            return self::makeClientUsingArray($opts);

        } else {
            throw new \Exception('Invalid parameters provided!');

        }

    }

    public static function makeClientUsingEnvFile($path_to_env = '', $env = '.env')
    {
        Utils\Util::loadEnv($path_to_env, $env);

        $api_key = getenv('API_KEY');
        $opts    = [
            'version'                   => getenv('VERSION'),
            'request_sleep_between_req' => getenv('REQUEST_SLEEP_BETWEEN_REQUESTS'),
            'request_max_attempts'      => getenv('REQUEST_MAX_ATTEMPTS'),
            'request_queue_timeout'     => getenv('REQUEST_QUEUE_TIMEOUT'),
            'request_queue_max_request' => getenv('REQUEST_QUEUE_MAX_REQUEST'),
            'request_queue_max_retries' => getenv('REQUEST_QUEUE_MAX_RETRIES'),
            'request_queue_lock_path'   => getenv('REQUEST_QUEUE_LOCK_PATH'),
            'path_to_env' => $path_to_env,
            'env_filename' => $env
        ];

        $client = new self($api_key, $opts);

        return $client;
    }

    public static function makeClientUsingArray(array $config = [])
    {
        $api_key = $config['api_key'] ?? '';
        $client = new self($api_key, $config);

        return $client;
    }


    /**
     * SecurityTrails constructor.
     *
     * @param       $api_key
     * @param array $opts
     * @throws \Exception
     */
    private function __construct($api_key, array $opts = [])
    {
        $this->api_key = $this->setAPIKey($api_key);
        $this->headers = $this->setHeaders($opts['headers'] ?? []);
        $this->api_version = $this->setAPIVersion($opts['version']);
        $this->path_to_env = $this->setPathToEnv($opts['path_to_env'] ?? '');
        $this->env_filename = $this->setEnvFilename($opts['env_filename'] ?? '');
        
    }


    public function getAPIKey()
    {
        return $this->api_key;
    }

    public function setAPIKey($api_key)
    {
        if (empty($api_key) || !is_string($api_key)) {
            throw new \Exception('Invalid API key provided!');
        }

        $this->api_key = $api_key;
    }
    
    public function setPathToEnv($path_to_env)
    {
        if (empty($path_to_env)) {
            $path_to_env = dirname(dirname(__DIR__));
        }

       $this->path_to_env = $path_to_env;
    }

    public function getPathToEnv()
    {
        return $this->path_to_env;
    }

    public function setEnvFilename($filename = '.env')
    {
        $this->env_filename = $filename;
    }

    public function getEnvFilename()
    {
        return $this->env_filename;
    }

    public function getAPIBaseUrl()
    {
        return $this->api_base_url;
    }

    public function setAPIBaseUrl($version = 1)
    {
        $this->api_base_url = str_replace('%VERSION', $version, self::API_BASE);
    }

    public function getAPIVersion()
    {
        return $this->api_version;
    }
    
    public function setAPIVersion($version)
    {
        if (!$this->isSupportedAPIVersion($version)) {
            throw new \Exception('The given API version is not supported!');
        } 
        
        $this->api_version = $version;
    }
    
    public function isSupportedAPIVersion($version)
    {
        $supported_versions = ['v1'];
        return in_array("v{$version}", $supported_versions);
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = [])
    {
        if (!is_array($headers)) {
            throw new \Exception('Headers should be an array!');
        }

        if (empty($headers)) {
            $headers = $this->getDefaultHeaders();
        }

        $this->headers = $headers;
    }

    public function getDefaultHeaders()
    {
        return [
            "Content-Type" => "application/json",
            "APIKEY"       => $this->api_key,
        ];
    }



    public static function getDomainInfo($domain)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $url  = str_replace("%DOMAIN", $domain, self::DOMAIN_INFO_ENDPOINT);
        $data = RequestQueue::get($url, [], 2, self::getHeaders());

        return $data;
    }

    public static function getIPsByHostname($domain)
    {
        $info = self::getDomainInfo($domain);
        $data = $info["current_dns"]["a"]["values"] ?? [];

        return array_map(function ($e) { return $e['ip']; }, $data);
    }

    /**
     * @param $payload
     * @return mixed|null
     */
    public static function getStats($payload)
    {
        $url  = self::STATS_ENDPOINT;
        $data = RequestQueue::post($url, [], $payload, 2, self::getHeaders());

        return [
            $data["tld_count"],
            $data["hostname_count"],
            $data["domain_count"],
        ];
    }

    public static function getStatsByIp($ip)
    {
        if (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\.(\\\d+)?/", $ip) === false) {
            Log::error("Given IP is not valid. IP: ", $ip);

            return null;
        }

        $payload = [
            "filter" => [
                "ipv4" => $ip,
            ],
        ];

        return self::getStats($payload);

    }

    /**
     * @param     $domain
     * @param int $page
     * @return array|null
     */
    public static function associatedDomain($domain, $page = 1)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        if (!is_numeric($page)) {
            Log::error("Page number is not a valid value. Page: ", $page);

            return null;
        }

        $url  = str_replace("%DOMAIN", $domain, self::ASSOCIATED_ENDPOINT);
        $data = RequestQueue::get($url, ["page" => $page], 2, self::getHeaders());
        $data = $data["records"];

        return $data;
    }

    /**
     * @param     $domain
     * @param int $limit
     * @return array|null
     */
    public static function manyAssociatedDomains($domain, $limit = self::PAGE_SIZE * 100)
    {
        if (!is_numeric($limit) || $limit <= 0) {
            Log::error("Number of records is not a valid value. Value: ", $limit);

            return null;
        }

        $output   = [];
        $page     = 0;
        $page_max = round($limit / self::PAGE_SIZE);

        Log::info("\nFetching Associated Domains: ");
        while (($response = self::associatedDomain($domain, ++$page)) && $page < $page_max) {
            $output = array_merge($output, $response);
            if ($page % 10 == 0) {
                Log::info("associated - no.: {$page}");
            }
        }

        $output = Utils::sort_by_alexa_rank($output);

        return $output;
    }

    /**
     * Get all subdomains from domain
     * Does not support pagination, implemented manually
     *
     * @param     $domain
     * @param int $page
     * @return null
     * @throws Exception
     */
    public static function subdomains($domain, $page = 1)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $url  = str_replace("%DOMAIN", $domain, self::SUBDOMAINS_ENDPOINT);
        $data = RequestQueue::get($url, ["page" => $page], 2, self::getHeaders());

        return $data["subdomains"] ?? [];
    }

    /**
     * @param     $domain
     * @param int $limit
     * @return null
     * @throws Exception
     */
    public static function manySubdomains($domain, $limit = self::PAGE_SIZE * 100)
    {
        if (!is_numeric($limit) || $limit <= 0) {
            Log::error("Number of records is not a valid value. Limit: ", $limit);

            return null;
        }

        $output     = [];
        $subdomains = self::subdomains($domain);

        $count = 0;
        Log::info("Fetching Subdomains: ");
        foreach ($subdomains as $subdomain) {
            $subdomain = (($subdomain != $domain) ? "{$subdomain}.{$domain}" : $domain);
            $ip        = SecurityTrailsClient::getIPsByHostname($subdomain)[0] ?? "-";
            $hosting   = MaxMind::getOrganizationByIpAddress($ip) ?? "-";

            $output[] = [
                'ip'        => $ip,
                'hosting'   => $hosting,
                'subdomain' => $subdomain,
            ];

            if (++$count % 10 == 0) {
                Log::info("subdomains - request no.: {$count} / " . count($subdomains));
            }

        }

        // sort whole list by number of ports
        Utils::sort_2d_array_by_size($output, "ports", SORT_ASC);

        // add apex domain and open ports for it
        $ip          = SecurityTrailsClient::getIPsByHostname($domain)[0] ?? "-";
        $apex_domain = [
            "ip"        => $ip,
            "hosting"   => MaxMind::getOrganizationByIpAddress($ip) ?? "-",
            "subdomain" => $domain,
        ];

        array_unshift($output, $apex_domain);

        $output = self::addOpenPortsToSubdomains($output);

        return $output;
    }

    public static function getReverseDnsByListOfIps($ips, $limit = self::PAGE_SIZE * 100)
    {
        if (!is_array($ips) || empty($ips)) {
            Log::error("IPs is not array or it's empty!", $ips);

            return [];
        }

        if (!is_numeric($limit) || $limit <= 0) {
            Log::error("Number of records is not a valid value. Value: ", $limit);

            return null;
        }

        $page     = 0;
        $output   = [];
        $page_max = round($limit / self::PAGE_SIZE);

        $ips = array_map(function ($e) { return "'{$e}'"; }, $ips);

        $counter = 0;
        $payload = ["query" => "ip in (" . implode(', ', $ips) . ")"];
        Log::info("Fetching Reverse DNS list by IPS: ");
        while (($response = self::ipsReverseDns($payload, ++$page)) && $page < $page_max) {
            $output = array_merge($output, $response);
            if ($page % 10 == 0) {
                Log::info("reverse dns - request no.: {$page}");
            }
        }

        return $output;
    }

    public static function addOpenPortsToSubdomains($subdomains_data, $ptrs_stacked = false)
    {
        // getting IPs from array and making packages of 21 IPs per package
        // TODO: remove when elasticsearch get stable
        $get_keys = function ($ips) {
            $item_count = 17;
            $packages   = [];

            $keys = array_filter(array_keys($ips), function ($e) { return $e != "-"; });

            $count = 0;
            foreach ($keys as $i => $key) {
                $packages[$count][] = $key;

                if ($i % $item_count == 0) {
                    $count++;
                }
            }

            return $packages;
        };

        $subdomains_with_the_same_ip = [];

        // stacking ptrs with the same ip
        foreach ($subdomains_data as $item) {
            $ip      = $item['ip'];
            $ptr     = $item['subdomain'];
            $hosting = $item['hosting'];

            $subdomains_with_the_same_ip[$ip]['ptr'][]   = $ptr;
            $subdomains_with_the_same_ip[$ip]['hosting'] = $hosting;
        }

        // getting just IPs
        $ips_packages = $get_keys($subdomains_with_the_same_ip);

        // getting port data for each of IP pack
        $ports_data = [];
        Log::info("Fetching the open ports for subdomains: ");
        foreach ($ips_packages as $p_count => $package) {
            if (empty($package)) {
                Log::info("Package no. {$p_count} is empty: ", $package);
                continue;
            }

            $data_tmp = self::getReverseDnsByListOfIps($package);
            Log::info("Package no. {$p_count} data size:  " . count($data_tmp));
            $ports_data = array_merge($data_tmp, $ports_data);
        }

        Log::info("Total ports data count: ", count($ports_data));

        // packing ports for each IP
        foreach ($ports_data as $item) {
            $ip    = $item['ip'];
            $ports = $item['ports'] ?? [];

            $subdomains_with_the_same_ip[$ip]['ports'] = $ports;
        }

        if ($ptrs_stacked) {
            return $subdomains_with_the_same_ip;
        }

        // extract back PTRs
        $output = [];
        foreach ($subdomains_with_the_same_ip as $ip => $item) {
            $ports   = $item['ports'];
            $hosting = $item['hosting'];

            foreach ($item['ptr'] as $ptr) {
                $output[] = [
                    'ip'        => $ip,
                    'ports'     => $ports,
                    'hosting'   => $hosting,
                    'subdomain' => $ptr,
                ];
            }
        }

        Log::info("Total all records: ", count($output));

        Utils::sort_2d_array_by_size($output, "ports", SORT_DESC);

        return $output;
    }

    /**
     * @param     $payload
     * @param int $page
     * @return array|null
     */
    public static function searchDomains($payload, $page = 1)
    {
        if (!is_array($payload)) {
            Log::error("Payload must be an array!", $payload);

            return null;
        }

        if (!is_numeric($page)) {
            Log::error("Page number is not a valid value. Page: ", $page);

            return null;
        }

        $url  = self::SEARCHING_DOMAINS . "?page={$page}";
        $data = RequestQueue::post($url, ["page" => $page], $payload, 2, self::getHeaders());
        $data = $data["records"];

        return $data;
    }

    /**
     * @param     $payload
     * @param int $limit
     * @return array|null
     */
    public static function manySearchDomains($payload, $limit = self::PAGE_SIZE * 100)
    {
        if (!is_numeric($limit) || $limit <= 0) {
            Log::error("Number of records is not a valid value. Limit: ", $limit);

            return null;
        }

        $output   = [];
        $page     = 0;
        $page_max = round($limit / self::PAGE_SIZE);

        $counter = 0;
        Log::info("Fetching data - Search Domains: ");
        while (($response = self::searchDomains($payload, ++$page)) && $page <= $page_max) {
            $output = array_merge($output, $response);
            if ($page % 10 == 0) {
                Log::info("search domains - request no.: {$page}");
            }

        }

        $output = Utils::sort_by_alexa_rank($output);

        return $output;
    }

    public static function getReverseDnsIpsCount($domain)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $url     = self::REVERSE_DNS_IPS;
        $payload = ['query' => "ptr_part = '{$domain}'"];
        $data    = RequestQueue::post($url, [], $payload, 2, self::getHeaders());
        $count   = $data["record_count"] ?? 0;

        return $count;
    }

    public static function ipsReverseDns($payload, $page = 1)
    {
        if (!is_array($payload)) {
            Log::error("Payload must be an array!", $payload);

            return null;
        }

        if (!is_numeric($page)) {
            Log::error("Page number is not a valid value. Page: ", $page);

            return null;
        }

        $url  = self::REVERSE_DNS_IPS;
        $data = RequestQueue::post($url, ["page" => $page], $payload, 2, self::getHeaders());
        $data = $data["records"];

        return $data;
    }

    /**
     * Get Reverse DNS IPs for given IP range
     * Can retrieve just first 100 pages for now
     *
     * @param     $term
     * @param int $limit
     * @return array|null
     */
    public static function getIPsReverseDnsByDomainName($term, $limit = self::PAGE_SIZE * 100)
    {
        if (!filter_var($term, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $term);

            return null;
        }

        if (!is_numeric($limit) || $limit <= 0) {
            Log::error("Number of records is not а valid value. Value: ", $limit);

            return null;
        }

        $page     = 0;
        $output   = [];
        $page_max = round($limit / self::PAGE_SIZE);

        $payload = ["query" => "ptr_part = '{$term}'"];

        Log::info("Fetching IPs Reverse DNS by Domain Name:");
        while (($response = self::ipsReverseDns($payload, ++$page)) && $page < $page_max) {
            $output = array_merge($output, $response);
            if ($page % 10 == 0) {
                Log::info("reverse dns by d.n. - request no.: {$page}");
            }
        }

        return $output;
    }

    public static function getOpenPorts($domain, $get_cached = true)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $data = CacheResolver::resolveReverseDns($domain, $get_cached);

        if (empty($data)) {
            Log::info("There is no data for domain: {$domain}.");

            return null;
        }

        $ports = [];
        foreach ($data as $item) {
            if (!empty($item['ports'])) {
                $ptr        = $item['ptr'];
                $curr_ports = $item['ports'] ?? [];

                if (!empty($ports[$ptr])) {
                    $ports[$ptr] = array_unique(array_merge($ports[$ptr], $curr_ports));
                } else {
                    $ports[$ptr] = $curr_ports;
                }

                $ports[$ptr] = (!empty($ports[$ptr]) ? array_unique(array_merge($ports[$ptr],
                    $curr_ports)) : $curr_ports);
            }
        }

        return $ports;
    }

    public static function getReverseDnsStats($domain, $reverse_dns_data = null)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $url = self::REVERSE_DNS_IPS_STATS;

        $get_stats = function ($ips) use ($url) {
            $ips_pattern = "ip in ("
                . implode(",",
                    array_map(function ($e) { return "'{$e}'"; }, $ips)
                )
                . ")";

            $payload  = ["query" => $ips_pattern];
            $response = RequestQueue::post($url, [], $payload, 2, self::getHeaders());

            return $response;
        };

        if (!$reverse_dns_data) {
            $reverse_dns_data = CacheResolver::resolveReverseDns($domain, true);
        }

        $ips_for_summary = [];
        foreach ($reverse_dns_data as $item) {
            $ip = $item['ip'];

            if (!empty($ip)) {
                if (!in_array($ip, $ips_for_summary)) {
                    $ips_for_summary[] = $ip;
                }
            }
        }

        $ips_packages = self::chunkBigIPList($ips_for_summary);

        // building query
        $data = [];
        Log::info("Fetching Reverse DNS stats: ");
        foreach ($ips_packages as $p_count => $package) {
            $stats                    = $get_stats($package);
            $data['ports']            = array_merge($stats['ports'], $data['ports'] ?? []);
            $data['top_ptr_patterns'] = array_merge($stats['top_ptr_patterns'], $data['top_ptr_patterns'] ?? []);

            Log::info("reverse dns stats - request (package) no. : {$p_count}");
        }

        $ports      = [];
        $ports_data = [];
        foreach ($data['ports'] as $p) {
            if (!in_array($p['key'], $ports)) {
                $ports[]      = $p['key'];
                $ports_data[] = $p;
            }
        }

        $ptrs      = [];
        $ptrs_data = [];
        foreach ($data['top_ptr_patterns'] as $p) {
            if (!in_array($p['key'], $ptrs)) {
                $ptrs[]      = $p['key'];
                $ptrs_data[] = $p;
            }
        }

        $data['ports']            = $ports_data;
        $data['top_ptr_patterns'] = $ptrs_data;

        return $data;
    }

    public static function protocols($domain, $ips_data = null)
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            Log::error("Given domain is not valid. Domain: ", $domain);

            return null;
        }

        $get_open_ports = function ($ips) {
            $ips_pattern = "ip in ("
                . implode(",",
                    array_map(function ($e) { return "'{$e}'"; }, $ips)
                )
                . ")";

            $payload = ["query" => $ips_pattern];

            $page   = 0;
            $output = [];
            Log::info("Fetching Open Ports for given array of IPs: ");
            while (($response = self::ipsReverseDns($payload, ++$page)) && $page < 100) {
                $output = array_merge($output, $response ?? []);
                if ($page % 10 == 0) {
                    Log::info("open ports data - request no.: {$page}");
                }
            }

            return $output;
        };

        // get list of IPs from IP Blocks table
        if ($ips_data == null) {
            $ips_data = @$_SESSION["ips"][$domain];
            if (empty($ips_data)) {
                $ips_data = CacheResolver::resolveIPs($domain);
            }
        }

        $ips = [];

        foreach ($ips_data as $item) {
            $ip = $item['ip'];

            if (!empty($ip)) {
                if (!in_array($ip, $ips)) {
                    $ips[] = $ip;
                }
            }
        }

        // package IPs
        $ip_packages = self::chunkBigIPList($ips);

        $data = [];
        Log::info("Fetching Open Ports (global): ");
        foreach ($ip_packages as $p_count => $package) {
            $data_tmp = $get_open_ports($package);
            $data     = array_merge($data, $data_tmp);

            Log::info("open ports package no.: {$p_count}");
        }

        // stacking records by port
        $output = [];
        foreach ($data as $value) {
            $ips   = $value["ip"];
            $ports = $value['ports'] ?? [];

            foreach ($ports as $port_num) {
                if (!in_array($ips, $output[$port_num] ?? [])) {
                    $output[$port_num][] = $ips;
                }
            }
        }

        ksort($output);

        return $output;

    }

    private static function chunkBigIPList($big_list, $items_per_array = 17)
    {
        $packages = [];

        $keys = array_filter($big_list, function ($e) { return $e != "-"; });

        $count = 0;
        foreach ($keys as $i => $key) {
            $packages[$count][] = $key;
            if ($i % $items_per_array == 0) {
                $count++;
            }
        }

        return $packages;
    }
}