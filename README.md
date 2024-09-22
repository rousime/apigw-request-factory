# API Gateway Request Factory

A library for creating a psr-based request based on data passed from the gateway api to the function, as well as for generating a response from a psr-based response.

# Installation

You can install this library via Composer. To do this, simply run the following command in your working directory:
```
composer require rousi/apigw-request-factory
```

# Usage

Example of the function entry point code for "Yandex Cloud Functions":

```php
<?php

use Apigw\ServerRequestFactory;
use Apigw\ResponseEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;

function handler($event, $context)
{
    // Creating a psr-based request from an event
    $request = ServerRequestFactory::from($event);
    // Creating a psr-based response
    $response = (new Psr17Factory)->createResponse(200);

    // Here is your code!

    return ResponseEmitter::emit($response);
}
```

# License
This library is distributed under the MIT License. Please refer to the [LICENSE](/LICENSE) file for more information.