<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiUtils\ActionValidator\IFactory;
use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\Exception\ApiValidationException;
use Ekimik\ApiUtils\Security\RequestIntegrity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestValidation extends Middleware {

    private $environment;
    /** @var RequestIntegrity */
    private $integrityChecker;
    /** @var IFactory */
    private $actionValidatorFactory;

    public function __construct(string $environment, RequestIntegrity $integrityChecker, IFactory $actionValidatorFactory) {
        $this->environment = $environment;
        $this->integrityChecker = $integrityChecker;
        $this->actionValidatorFactory = $actionValidatorFactory;
    }

    /**
     * @inheritdoc
     */
    protected function execute(Request $request, Response $response, callable $next): Response {
        if ($this->environment === 'production') {
            $this->integrityChecker->check($request);
        }

        /** @var \Ekimik\ApiUtils\Resource\Request $apiRequest */
        $apiRequest = $request->getAttribute('apiRequest');
        if (empty($apiRequest)) {
            throw new ApiException(
                "Attribute 'apiRequest' cannot be found in request object, did you add " . ApiRequest::class . " middleware to your stack?",
                500
            );
        }

        $action = $apiRequest->getAction();
        $actionValidator = $this->actionValidatorFactory->create($action);
        $actionValidator->validate($request->getAttribute('apiRequest'));

        if (!$actionValidator->isValid()) {
            throw new ApiValidationException(
                "Validation of input data failed, see 'errors' for more info",
                $actionValidator->getErrors()
            );
        }

        return $next($request, $response);
    }
}