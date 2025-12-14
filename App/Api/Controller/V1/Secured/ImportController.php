<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\ImportFacade;
use App\Api\Response\ImportResponse;

#[Path('/imports')]
final class ImportController extends BaseSecuredV1Controller
{
    public function __construct(
        private readonly ImportFacade $importFacade
    )
    {
    }

    #[Path('/')]
    #[Method('GET')]
    #[Response(description: 'List of imports', entity: ImportResponse::class . '[]')]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $data = array_map(
            fn(array $item) => ImportResponse::fromArray($item),
            $this->importFacade->getAll()
        );

        return $response->writeJsonBody($data);
    }
}
