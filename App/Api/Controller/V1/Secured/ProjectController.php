<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\ProjectFacade;

#[Path('/projects')]
final class ProjectController extends BaseSecuredV1Controller
{
    public function __construct(
        private readonly ProjectFacade $projectFacade
    )
    {
    }

    #[Path('/')]
    #[Method('GET')]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        return $response->writeJsonBody($this->projectFacade->getAll());
    }

    #[Path('/{id}')]
    #[Method('GET')]
    public function detail(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $id = (int)$request->getParameter('id');
        $project = $this->projectFacade->getById($id);

        if ($project === null)
        {
            return $response->withStatus(404)
                ->writeJsonBody(['error' => 'Project not found']);
        }

        return $response->writeJsonBody($project);
    }
}
