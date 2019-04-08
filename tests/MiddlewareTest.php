<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiMiddleware\Middleware;
use Ekimik\ApiUtils\Exception\ApiException;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class MiddlewareTest extends TestCase {

    /** @var Middleware */
    private $middleware;
    /** @var callable */
    private $nextCb;
    /** @var ServerRequest */
    private $request;
    /** @var Response */
    private $response;

    protected function setUp() {
        parent::setUp();

        $this->middleware = $this->getMockBuilder(Middleware::class)->setMethods(['execute'])->getMockForAbstractClass();
        $this->request = new ServerRequest('GET', '/foo/bar');
        $this->response = new Response();
        $this->nextCb = function (IRequest $request, IResponse $response) {
            throw new \LogicException('Should not be executed');
        };

        $testName = $this->getName();
        if ($testName === 'testExecutionGeneralException') {
            $this->middleware->method('execute')->willThrowException(new \Exception('Foo exception'));
        } else if ($testName === 'testExecutionApiException') {
            $prev = new ApiException('Baz exception', 401);
            $this->middleware->method('execute')->willThrowException(new ApiException('Bar exception', 400, $prev));
        } else {
            $this->middleware->method('execute')->willReturn(new Response(200, [], '{"foo": "bar"}'));
        }
    }

    /**
     * @covers Middleware
     */
    public function testExecutionGeneralException() {
        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $this->request, $this->response, $this->nextCb);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(
            '{"errors":[{"message":"Foo exception"}],"responseData":[]}',
            $response->getBody()->getContents()
        );
    }

    /**
     * @covers Middleware
     */
    public function testExecutionApiException() {
        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $this->request, $this->response, $this->nextCb);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            '{"errors":[{"message":"Bar exception"},{"message":"Baz exception"}],"responseData":[]}',
            $response->getBody()->getContents()
        );
    }

    /**
     * @covers Middleware
     */
    public function testExecution() {
        $response = call_user_func($this->middleware, $this->request, $this->response, $this->nextCb);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            '{"foo": "bar"}',
            $response->getBody()->getContents()
        );
    }

}
