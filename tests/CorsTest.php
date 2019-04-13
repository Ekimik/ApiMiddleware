<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiMiddleware\Cors;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class CorsTest extends TestCase {

    /** @var callable */
    private $nextCb;

    protected function setUp() {
        parent::setUp();
        $this->nextCb = function (IRequest $request, IResponse $response) {
            return $response;
        };
    }

    /**
     * @covers Cors
     */
    public function testExecution() {
        $middleware = new Cors('*', ['X-FOOBAR', 'Some-Header'], ['GET', 'POST']);

        /** @var IResponse $response */
        $response = call_user_func(
            $middleware,
            new ServerRequest('GET', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertEquals(['*'], $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEmpty($response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEmpty($response->getHeader('Access-Control-Allow-Methods'));

        /** @var IResponse $response */
        $response = call_user_func(
            $middleware,
            new ServerRequest('OPTIONS', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertEquals(['*'], $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals(['X-FOOBAR,Some-Header'], $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEquals(['GET,POST'], $response->getHeader('Access-Control-Allow-Methods'));
    }

}
