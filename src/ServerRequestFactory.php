<?php

namespace Apigw;

use Nyholm\Psr7\ServerRequest;


class ServerRequestFactory
{
    public const DEFAULT_OPTIONS = [
        'parse_cookie' => false,
        'parse_server_params' => false,
        'parse_body' => true
    ];

    private const APIGW_HOST = 'apigw.yandexcloud.net';

    /**
     * @var string
     */
    private static string $serverAddr;

    /**
     * @param array $event
     * @param array $options
     * 
     * @return ServerRequest
     */
    public static function from(array $event, array $options = []): ServerRequest
    {
        $options = \array_merge(static::DEFAULT_OPTIONS, $options);

        $body = $event['isBase64Encoded'] ? \base64_decode($event['body']) : $event['body'];

        return (new ServerRequest(
            method:         $event['requestContext']['httpMethod'],
            uri:            static::generateUri($event),
            headers:        $event['multiValueHeaders'],
            body:           $body,
            version:        '2.0',
            serverParams:   ($options['parse_server_params'] ? static::getServerParams($event) : [])
        ))
        ->withAttribute('requestContext', $event['requestContext'])
        ->withAttribute('operationId', $event['operationId'] ?? '')
        ->withAttribute('parameters', $event['parameters'] ?? $event['params'])
        ->withAttribute('pathParameters', $event['pathParameters'] ?? $event['pathParams'])
        ->withCookieParams($options['parse_cookie'] ? static::parseCookie($event) : [])
        ->withParsedBody($options['parse_body'] ? static::parseBody($body, $event['headers']['Content-Type'] ?? '') : []);
    }

    /**
     * @param string $event
     * 
     * @return string
     */
    protected static function generateUri(&$event)
    {
        return (
            $event['headers']['X-Forwarded-Proto']
            . '://'
            . $event['headers']['Host']
            . $event['headers']['X-Envoy-Original-Path'] ?? $event['path']
        );
    }

    /**
     * @param string $body
     * @param string $contentType
     * 
     * @return array
     */
    protected static function parseBody($body, $contentType)
    {
        $contentType = \explode(';', $contentType)[0];

        switch($contentType)
        {
            case 'application/json':
                return \json_decode($body, true);

            case 'application/x-www-form-urlencoded':
                $data = [];
                \parse_str($body, $data);
                return $data;
        }

        return [];
    }

    /**
     * @param array $event
     * 
     * @return array
     */
    protected static function getServerParams(&$event)
    {
        return [
            'SERVER_ADDR' =>    static::getServerAddr(),
            'SERVER_NAME' =>    $event['headers']['Host'],
            'SERVER_PROTOCOL' => 'HTTP/2.0',
            'REQUEST_METHOD' => $event['requestContext']['httpMethod'],
            'REQUEST_TIME' =>   $event['requestContext']['requestTimeEpoch'],
            'HTTPS' =>          'https', // apigw does not support non-secure protocols
            'REMOTE_ADDR' =>    $event['requestContext']['identity']['sourceIp'],
            'REMOTE_PORT' =>    \explode(':', $event['headers']['X-Real-Remote-Address'])[1],
            'REQUEST_URI' =>    $event['headers']['X-Envoy-Original-Path'],
            'PATH_INFO' =>      $event['path']
        ];
    }

    /**
     * @return string
     */
    protected static function getServerAddr()
    {
        if (!isset(static::$serverAddr))
        {
            foreach (\dns_get_record('apigw.yandexcloud.net', \DNS_A) as $record)
            {
                if ($record['type'] == 'A')
                {
                    static::$serverAddr = $record['ip'];
                    break;
                }
            }
        }

        return static::$serverAddr;
    }

    /**
     * @param array $event
     * 
     * @return array
     */
    protected static function parseCookie(&$event)
    {
        $cookie = [];

        foreach(\explode('; ', $event['headers']['Cookie']) as $rawCookie)
        {
            $cookieData = \explode('=', $rawCookie);

            $cookie[
                $cookieData[0]
            ] = $cookieData[1];
        }

        return $cookie;
    }
}