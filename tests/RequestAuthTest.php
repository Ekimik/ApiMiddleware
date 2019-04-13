<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiDesc\Resource\Action;
use Ekimik\ApiMiddleware\RequestAuth;
use Ekimik\ApiUtils\InputData\Completion;
use Ekimik\ApiUtils\Resource\Request;
use Ekimik\ApiUtils\Security\Authorizator;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Nette\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class RequestAuthTest extends TestCase {

    /** @var RequestAuth */
    private $middleware;
    /** @var callable */
    private $nextCb;

    protected function setUp() {
        parent::setUp();

        $this->nextCb = function (IRequest $request, IResponse $response) {
            return $response;
        };

        $authorizator = new Authorizator(null);
        if ($this->getName() === 'testExecutionAuthFailed') {
            $authorizator = $this->getMockBuilder(Authorizator::class)
                ->setMethods(['authorize'])
                ->setConstructorArgs(['http://www.example.com/authapi'])
                ->getMock();
            $authorizator->method('authorize')->willReturn(false);
        }

        $this->middleware = new RequestAuth($authorizator, 'fooApi', 'production');
    }

    /**
     * @covers RequestAuth
     */
    public function testExecutionNoApiRequest() {
        /** @var IResponse $response */
        $response = call_user_func(
            $this->middleware,
            new ServerRequest('GET', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(Strings::contains(
            $response->getBody()->getContents(),
            "Attribute 'apiRequest' cannot be found in request"
        ));
    }

    /**
     * @covers RequestAuth
     */
    public function testExecutionPublicAction() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar');
        $apiRequest = new Request([], new Action('foo/bar', 'GET'), new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers RequestAuth
     */
    public function testExecutionNoToken() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar');
        $a = new Action('foo/bar', 'GET');
        $a->setAuthorization('foo', 'read');
        $apiRequest = new Request([], $a, new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(Strings::contains($response->getBody()->getContents(), 'Missing token for request auth'));
    }

    /**
     * @covers RequestAuth
     */
    public function testExecutionAuthFailed() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar', ['X-AUTH-TOKEN' => 'token123']);
        $a = new Action('foo/bar', 'GET');
        $a->setAuthorization('foo', 'read');
        $apiRequest = new Request([], $a, new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(Strings::contains($response->getBody()->getContents(), 'Unauthorized'));
    }

    /**
     * @covers RequestAuth
     */
    public function testExecution() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar', ['X-AUTH-TOKEN' => 'token123']);
        $a = new Action('foo/bar', 'GET');
        $a->setAuthorization('foo', 'read');
        $apiRequest = new Request([], $a, new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(200, $response->getStatusCode());
    }

}
