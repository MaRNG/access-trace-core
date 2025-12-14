<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Secured;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Facade\IpFacade;

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
    public function activity(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $ip = $request->getParameter('ip');
        $detailed = $request->getParameter('detailed', false);
        $isDetailed = filter_var($detailed, FILTER_VALIDATE_BOOLEAN);

        return $response->writeJsonBody($this->ipFacade->getActivity($ip, $isDetailed));
    }
}
