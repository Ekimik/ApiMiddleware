<?php

namespace Ekimik\ApiMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorsHeaders extends Middleware {

    private $origin;
    private $headers;
    private $methods;

    public function __construct(string $origin, array $headers, array $methods) {
        $this->origin = $origin;
        $this->headers = $headers;
        $this->methods = $methods;
    }

    /**
     * @inheritdoc
     */
    protected function execute(Request $request, Response $response, callable $next): Response {
        $response = $response->withHeader('Access-Control-Allow-Origin', $this->origin);

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withHeader('Access-Control-Allow-Headers', implode(',', $this->headers));
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $this->methods));
        }

        return $next($request, $response);
    }
}