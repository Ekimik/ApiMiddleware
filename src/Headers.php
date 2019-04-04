<?php

namespace Ekimik\ApiMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
    protected function execute(Request $request, Response $response, callable $next): Response {
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $next($request, $response);
    }
}