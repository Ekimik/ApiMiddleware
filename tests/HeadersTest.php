<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiMiddleware\Headers;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class HeadersTest extends TestCase {

    /** @var callable */
    private $nextCb;

    protected function setUp() {
        parent::setUp();
        $this->nextCb = function (IRequest $request, IResponse $response) {
            return $response;
        };
    }

    /**
     * @covers Headers
     */
    public function testExecution() {
        $middleware = new Headers(['Content-Type' => 'application/json', 'X-FOOBAR' => 123]);
        /** @var IResponse $response */
        $response = call_user_func(
            $middleware,
            new ServerRequest('GET', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertCount(2, $response->getHeaders());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals([123], $response->getHeader('x-foobar'));
    }

}
