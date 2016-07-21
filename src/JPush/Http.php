<?php
namespace JPush;
use JPush\Exceptions\APIConnectionException;
use JPush\Exceptions\APIRequestException;

final class Http {

    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');

    public static function get($client, $url) {
        $response = self::sendRequest($client, $url, Config::HTTP_GET, $body=null);
        return self::processResp($response);
    }
    public static function post($client, $url, $body) {
        $response = self::sendRequest($client, $url, Config::HTTP_POST, $body);
        return self::processResp($response);
    }
    public static function put($client, $url, $body) {
        $response = self::sendRequest($client, $url, Config::HTTP_PUT, $body);
        return self::processResp($response);
    }
    public static function delete($client, $url) {
        $response = self::sendRequest($client, $url, Config::HTTP_DELETE, $body=null);
        return self::processResp($response);
    }

    public static function sendRequest($client, $url, $method, $body=null, $times=1) {
        self::log($client, "Send " . $method . " " . $url . ", body:" . $body . ", times:" . $times);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, Config::USER_AGENT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::CONNECT_TIMEOUT);  // 连接建立最长耗时
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::READ_TIMEOUT);  // 请求最长耗时
        // 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        // 设置Basic认证
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $client->getAuthStr());
        // 设置Post参数
        if ($method === Config::HTTP_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method === Config::HTTP_DELETE || $method === Config::HTTP_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Connection: Keep-Alive'
        ));

        $output = curl_exec($ch);
        $response = array();
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            if ($errorCode === 28) {
                throw new APIConnectionException("Response timeout. Your request has probably be received by JPush Server,please check that whether need to be pushed again.", true);
            } else if ($errorCode === 56) {
                // resolve error[56 Problem (2) in the Chunked-Encoded data]
                throw new APIConnectionException("Response timeout, maybe cause by old CURL version. Your request has probably be received by JPush Server, please check that whether need to be pushed again.", true);
            } else if ($times >= $this->retryTimes) {
                throw new APIConnectionException("Connect timeout. Please retry later. Error:" . $errorCode . " " . curl_error($ch));
            } else {
                self::log($client, "Send " . $method . " " . $url . " fail, curl_code:" . $errorCode . ", body:" . $body . ", times:" . $times);
                return self::request($url, $method, $body, ++$times);
            }
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            $headers = array();
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers['http_code'] = $line;
                    } else if (strpos($line, ": ")) {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
                    }
                }
            }
            $response['headers'] = $headers;
            $response['body'] = $body;
            $response['http_code'] = $httpCode;
        }
        curl_close($ch);
        return $response;
    }

    public static function processResp($response) {
        if($response['http_code'] === 200) {
            $result = array();
            $data = json_decode($response['body'], true);
            if (!is_null($data)) {
                $result['body'] = $data;
            }
            $result['http_code'] = $response['http_code'];
            $headers = $response['headers'];
            if (is_array($headers)) {
                $limit = array();
                foreach (self::$LIMIT_KEYS as $key => $value) {
                    if (array_key_exists($key, $headers)) {
                        $limit[$value] = $headers[$key];
                    }
                }
                if (count($limit) > 0) {
                    $result['headers'] = $limit;
                }
                return $result;
            }
            return $result;
        } else {
            throw new APIRequestException($response);
        }
    }

    public static function log($client, $content) {
        if (!is_null($client->getLogFile())) {
            error_log($content . "\r\n", 3, $client->getLogFile());
        }
    }
}