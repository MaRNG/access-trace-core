<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Model\Facade\ImportFacade;

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
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        return $response->writeJsonBody($this->importFacade->getAll());
    }
}
