<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\ProjectFacade;
use App\Api\Response\ProjectResponse;

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
    #[Response(description: 'List of projects', entity: ProjectResponse::class . '[]')]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $data = array_map(
            fn(array $item) => ProjectResponse::fromArray($item),
            $this->projectFacade->getAll()
        );

        return $response->writeJsonBody($data);
    }

    #[Path('/{id}')]
    #[Method('GET')]
    #[Response(description: 'Project detail', entity: ProjectResponse::class)]
    #[Response(description: 'Project not found', code: '')]
    public function detail(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $id = (int)$request->getParameter('id');
        $project = $this->projectFacade->getById($id);

        if ($project === null)
        {
            return $response->withStatus(404)
                ->writeJsonBody(['error' => 'Project not found']);
        }

        return $response->writeJsonBody(ProjectResponse::fromArray($project));
    }
}
