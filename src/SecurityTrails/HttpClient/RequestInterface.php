<?php

namespace SecurityTrails\HttpClient;

interface RequestInterface
{
    public function get(string $url, array $settings = []);

    public function post(string $url, array $settings = []);
}