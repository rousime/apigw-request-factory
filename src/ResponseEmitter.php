<?php

namespace Apigw;

use Psr\Http\Message\ResponseInterface;


class ResponseEmitter
{
    public static function emit(ResponseInterface $response, bool $isBase64Encoded = false): array
    {
        return [
            'multiValueHeaders' => $response->getHeaders(),
            'statusCode' => $response->getStatusCode(),
            'isBase64Encoded' => $isBase64Encoded,
            'body' => (string) $response->getBody()
        ];
    }
}