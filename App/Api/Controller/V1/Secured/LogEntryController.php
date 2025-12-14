<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\LogEntryFacade;

#[Path('/entries')]
final class LogEntryController extends BaseSecuredV1Controller
{
    public function __construct(
        private readonly LogEntryFacade $logEntryFacade
    )
    {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'from', type: 'string', in: 'query', required: true, description: 'ISO 8601 datetime')]
    #[RequestParameter(name: 'to', type: 'string', in: 'query', required: true, description: 'ISO 8601 datetime')]
    #[RequestParameter(name: 'projectId', type: 'int', in: 'query', required: false)]
    #[RequestParameter(name: 'accessLogId', type: 'int', in: 'query', required: false)]
    #[RequestParameter(name: 'detailed', type: 'string', in: 'query', required: false)]
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $fromStr = $request->getParameter('from');
        $toStr = $request->getParameter('to');
        $projectId = $request->getParameter('projectId');
        $accessLogId = $request->getParameter('accessLogId');
        $detailed = $request->getParameter('detailed', false);

        try
        {
            $from = new \DateTimeImmutable($fromStr);
            $to = new \DateTimeImmutable($toStr);
        } catch (\Exception $e)
        {
            return $response->withStatus(400)->writeJsonBody(['error' => 'Invalid date format']);
        }

        $projectIdInt = $projectId !== null ? (int)$projectId : null;
        $accessLogIdInt = $accessLogId !== null ? (int)$accessLogId : null;
        $isDetailed = filter_var($detailed, FILTER_VALIDATE_BOOLEAN);

        return $response->writeJsonBody($this->logEntryFacade->getEntriesByTimeRange($from, $to, $projectIdInt, $accessLogIdInt, $isDetailed));
    }
}
