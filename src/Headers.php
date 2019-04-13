<?php

namespace Ekimik\ApiMiddleware;

use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class Headers extends Middleware {

    private $headers = [];

    /**
     * @param array $headers key-value pairs
     */
    public function __construct(array $headers) {
        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function execute(IRequest $request, IResponse $response, callable $next): IResponse {
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $next($request, $response);
    }
}