<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\Resource\Request;
use Ekimik\ApiUtils\Security\Authorizator;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class RequestAuth extends Middleware {

    /** @var Authorizator */
    private $authorizator;
    private $apiIdent;
    private $environment;
    private $tokenHeaderName;

    public function __construct(
        Authorizator $authorizator,
        string $apiIdent,
        string $environment,
        string $tokenHeaderName = 'X-AUTH-TOKEN'
    ) {
        $this->authorizator = $authorizator;
        $this->apiIdent = $apiIdent;
        $this->environment = $environment;
        $this->tokenHeaderName = $tokenHeaderName;
    }

    /**
     * @inheritdoc
     */
    protected function execute(IRequest $request, IResponse $response, callable $next): IResponse {
        /** @var Request $apiRequest */
        $apiRequest = $request->getAttribute('apiRequest');
        if (empty($apiRequest)) {
            throw new ApiException(
                "Attribute 'apiRequest' cannot be found in request object, did you add " . ApiRequest::class . " middleware to your stack?",
                500
            );
        }

        $action = $apiRequest->getAction();
        if (
            $action->isPublic()
            || $this->environment === 'develop'
        ) {
            return $next($request, $response);
        }

        $token = $request->getHeader($this->tokenHeaderName)[0] ?? null;
        if (empty($token)) {
            throw new ApiException('Missing token for request auth', 400);
        }

        $this->authorizator->createAuthRequest($this->apiIdent)
            ->where($action->getAuthorization()['resource'], $action->getAuthorization()['privilege'])
            ->withToken($token);

        $authResult = $this->authorizator->authorize();
        if ($authResult) {
            return $next($request, $response);
        }

        throw new ApiException('Unauthorized', 403);
    }

}