<?php

namespace App\Api\Controller\V1\Public\Docs;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\OpenApi\ISchemaBuilder;
use App\Api\Controller\V1\Public\BasePublicV1Controller;
use Psr\Http\Message\ResponseInterface;

#[Path('/docs')]
final class OpenApiController extends BasePublicV1Controller
{
    public function __construct(private readonly ISchemaBuilder $schemaBuilder)
    {
    }

    #[Path('/json')]
    #[Method('GET')]
    public function meta(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        return $response
            ->withAddedHeader('Access-Control-Allow-Origin', '*')
            ->writeJsonBody(
                $this->schemaBuilder->build()->toArray()
            );
    }
}