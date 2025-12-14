<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\IpFacade;
use App\Api\Response\LogEntryResponse;

#[Path('/ips')]
final class IpController extends BaseSecuredV1Controller
{
    public function __construct(
        private readonly IpFacade $ipFacade
    )
    {
    }

    #[Path('/{ip}/activity')]
    #[Method('GET')]
    #[RequestParameter(name: 'ip', type: 'string', in: 'path', required: true)]
    #[RequestParameter(name: 'detailed', type: 'string', in: 'query', required: false)]
    #[RequestParameter(name: 'projectId', type: 'int', in: 'query', required: false)]
    #[RequestParameter(name: 'accessLogId', type: 'int', in: 'query', required: false)]
    #[Response(description: 'List of log entries for IP', entity: LogEntryResponse::class . '[]')]
    public function activity(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $ip = $request->getParameter('ip');
        $detailed = $request->getParameter('detailed', false);
        $projectId = $request->getParameter('projectId');
        $accessLogId = $request->getParameter('accessLogId');

        $isDetailed = filter_var($detailed, FILTER_VALIDATE_BOOLEAN);
        $projectIdInt = $projectId !== null ? (int)$projectId : null;
        $accessLogIdInt = $accessLogId !== null ? (int)$accessLogId : null;

        $data = array_map(
            fn(array $item) => LogEntryResponse::fromArray($item),
            $this->ipFacade->getActivity($ip, $isDetailed, $projectIdInt, $accessLogIdInt)
        );

        return $response->writeJsonBody($data);
    }
}
