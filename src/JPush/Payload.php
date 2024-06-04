<?php

namespace JPush;

class Payload
{
    protected $client;

    /**
     * Payload constructor.
     * @param $client JPush
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * 拼接URL和参数
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function buildUrlWithParams($url, $params = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    protected function get($url, $params = null)
    {
        if (!empty($params)) {
            $url = $this->buildUrlWithParams($url, $params);
        }
        return Http::get($this->client, $url);
    }
    protected function post($url, $body)
    {
        return Http::post($this->client, $url, $body);
    }
    protected function put($url, $body)
    {
        return Http::put($this->client, $url, $body);
    }
    protected function delete($url)
    {
        return Http::delete($this->client, $url);
    }
}
