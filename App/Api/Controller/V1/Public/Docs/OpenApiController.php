<?php

declare(strict_types=1);

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

    #[Path('/')]
    #[Method('GET')]
    public function index(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" >
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"> </script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"> </script>
    <script>
    window.onload = function() {
        const ui = SwaggerUIBundle({
            url: window.location.pathname.replace(/\/+$/, '') + '/json',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        })
        window.ui = ui
    }
    </script>
</body>
</html>
HTML;

        return $response->writeBody($html);
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
