<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'REST Commerce API',
    version: '1.0.0',
    description: 'API documentation for REST Commerce platform'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API v1 base URL'
)]
class OpenApiSpec
{
    // Root OpenAPI definition container
}
