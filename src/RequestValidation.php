<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiUtils\ActionValidator\IFactory;
use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\Exception\ApiValidationException;
use Ekimik\ApiUtils\Resource\Request;
use Ekimik\ApiUtils\Security\RequestIntegrity;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

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
    protected function execute(IRequest $request, IResponse $response, callable $next): IResponse {
        if ($this->environment === 'production') {
            $this->integrityChecker->check($request);
        }

        /** @var Request $apiRequest */
        $apiRequest = $request->getAttribute('apiRequest');
        if (empty($apiRequest) && !$apiRequest instanceof Request) {
            throw new ApiException(
                "Attribute 'apiRequest' cannot be found in request object, did you add " . ApiRequest::class . " middleware to your stack?",
                500
            );
        }

		$this->validateHeaders($request);
        $this->validateData($request);

        return $next($request, $response);
    }

    private function validateHeaders(IRequest $request) {
		/** @var Request $apiRequest */
		$apiRequest = $request->getAttribute('apiRequest');
		$action = $apiRequest->getAction();

		$headers = $action->getHeaders();
		$errors = [];
		foreach ($headers as $headerDef) {
			if ($headerDef['required'] && !$request->hasHeader($headerDef['name'])) {
				$errors[] = ['message' => "Required header '{$headerDef['name']}' is missing"];
			}
		}

		if (!empty($errors)) {
			throw new ApiValidationException("Validation of input data failed, see 'errors' for more info", $errors);
		}
	}

	private function validateData(IRequest $request) {
		/** @var Request $apiRequest */
		$apiRequest = $request->getAttribute('apiRequest');

		$action = $apiRequest->getAction();
		$actionValidator = $this->actionValidatorFactory->create($action);
		$actionValidator->validate($apiRequest);

		if (!$actionValidator->isValid()) {
			$errors = $actionValidator->getErrors();
			throw new ApiValidationException("Validation of input data failed, see 'errors' for more info", $errors);
		}
	}

}