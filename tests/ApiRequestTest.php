<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiDesc\Api;
use Ekimik\ApiDesc\ApiDescriptor;
use Ekimik\ApiDesc\Resource\Action;
use Ekimik\ApiDesc\Resource\Description;
use Ekimik\ApiMiddleware\ApiRequest;
use Ekimik\ApiUtils\Resource\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class ApiRequestTest extends TestCase {

    /** @var DummyApiDesc */
    private $apiDesc;
    /** @var callable */
    private $nextCb;
    /** @var ApiRequest */
    private $middleware;

    /** @var IRequest */
    private $finalRequest;
    /** @var IResponse */
    private $finalResponse;

    protected function setUp() {
        parent::setUp();

        $this->apiDesc = new DummyApiDesc();
        $this->middleware = new ApiRequest($this->apiDesc);
        $this->nextCb = function (IRequest $request, IResponse $response) {
            $this->finalRequest = $request;
            $this->finalResponse = $response;

            return $response;
        };
    }

    /**
     * @covers ApiRequest
     */
    public function testExecutionUnknownAction() {
        /** @var IResponse $response */
        $response = call_user_func(
            $this->middleware,
            new ServerRequest('GET', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertNull($this->finalRequest);
        $this->assertNull($this->finalResponse);
    }

    /**
     * @covers ApiRequest
     */
    public function testExecution() {
        /** @var IResponse $response */
        call_user_func(
            $this->middleware,
            new ServerRequest('GET', 'http://www.example.com/v1/foo'),
            new Response(),
            $this->nextCb
        );

        $this->assertNotNull($this->finalRequest);
        $this->assertNotNull($this->finalResponse);

        /** @var Request $apiRequest */
        $apiRequest = $this->finalRequest->getAttribute('apiRequest');
        $this->assertInstanceOf(Request::class, $apiRequest);
        $this->assertSame($this->apiDesc->a1, $apiRequest->getAction());
    }

}

class DummyApiDesc extends ApiDescriptor {

    /** @var Action */
    public $a1;
    /** @var Action */
    public $a2;

    protected function createApi(): Api {
        return new Api('foobar', 'v1');
    }

    protected function getFooResourceDescription(): Description {
        $this->a1 = new Action('v1/foo', 'GET');
        $this->a2 = new Action('v1/foo', 'POST');

        $desc = new Description('foo');
        $desc->addAction($this->a1);
        $desc->addAction($this->a2);

        return $desc;
    }

}
