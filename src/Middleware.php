<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\Resource\Response as ApiResponse;
use function GuzzleHttp\Psr7\stream_for;
use Nette\Utils\Json;
use Psr\Http\Message\ServerRequestInterface as IRequest;
use Psr\Http\Message\ResponseInterface as IResponse;

abstract class Middleware {

    public function __invoke(IRequest $request, IResponse $response, callable $next): IResponse {
        try {
            return $this->execute($request, $response, $next);
        } catch (\Throwable $e) {
            $r = new ApiResponse();
            $r->addError(['message' => $e->getMessage()]);
            $code = 500;

            if ($e instanceof ApiException) {
                $code = $e->getCode();
                foreach ($e->getErrors() as $error) {
                    $r->addError($error);
                }
            }

            return $response
                ->withStatus($code)
                ->withHeader('Content-type', 'application/json')
                ->withBody(stream_for(Json::encode($r->getResponse())));
        }
    }

    /**
     * @param IRequest $request
     * @param IResponse $response
     * @param callable $next
     * @return IResponse
     * @throws ApiException
     */
    protected abstract function execute(IRequest $request, IResponse $response, callable $next): IResponse;

}
