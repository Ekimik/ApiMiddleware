<?php

namespace Ekimik\ApiMiddleware;

use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\Resource\Response as ApiResponse;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class Middleware {

    public function __invoke(Request $request, Response $response, callable $next): Response {
        try {
            return $this->execute($request, $response, $next);
        } catch (\Throwable $e) {
            $r = new ApiResponse();
            $r->addError(['message' => $e->getMessage()]);
            $code = 500;

            if ($e instanceof ApiException) {
                $code = $e->getCode();
                foreach ($e->getErrors() as $error) {
                    $r->addError(['message' => $error]);
                }
            }

            return $response
                ->withStatus($code)
                ->withJson($r->getResponse());
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws ApiException
     */
    protected abstract function execute(Request $request, Response $response, callable $next): Response;

}
