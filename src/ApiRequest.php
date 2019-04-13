<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiDesc\ApiDescriptor;
use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\InputData\Completion;
use Ekimik\ApiUtils\Resource\Request;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class ApiRequest extends Middleware {

    /** @var ApiDescriptor */
    private $apiDesc;

    public function __construct(ApiDescriptor $apiDesc) {
        $this->apiDesc = $apiDesc;
    }

    /**
     * @inheritdoc
     */
    protected function execute(IRequest $request, IResponse $response, callable $next): IResponse {
        $path = trim($request->getUri()->getPath(), '/');
        $action = $this->apiDesc->getAction($request->getMethod(), $path);

        if (empty($action)) {
            throw new ApiException('Unknown API action', 404);
        }

        $apiRequest = new Request(
            $this->getInputData($request),
            $action,
            new Completion()
        );

        $request = $request->withAttribute('apiRequest', $apiRequest);
        return $next($request, $response);
    }

    private function getInputData(IRequest $request): array {
        $method = $request->getMethod();

        if (in_array($method, ['GET', 'DELETE'])) {
            return $request->getQueryParams();
        }

        return $request->getParsedBody();
    }


}