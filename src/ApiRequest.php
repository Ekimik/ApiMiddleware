<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiDesc\ApiDescriptor;
use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\InputData\Completion;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiRequest extends Middleware {

    /** @var ApiDescriptor */
    private $apiDesc;

    public function __construct(ApiDescriptor $apiDesc) {
        $this->apiDesc = $apiDesc;
    }

    /**
     * @inheritdoc
     */
    protected function execute(Request $request, Response $response, callable $next): Response {
        $path = trim($request->getUri()->getPath(), '/');
        $action = $this->apiDesc->getAction($request->getMethod(), $path);

        if (empty($action)) {
            throw new ApiException('Unknown API action', 404);
        }

        $apiRequest = new \Ekimik\ApiUtils\Resource\Request(
            $this->getInputData($request),
            $action,
            new Completion()
        );

        $request = $request->withAttribute('apiRequest', $apiRequest);
        return $next($request, $response);
    }

    private function getInputData(Request $request): array {
        $method = $request->getMethod();

        if (in_array($method, ['GET', 'DELETE'])) {
            return $request->getQueryParams();
        }

        return $request->getParsedBody();
    }


}