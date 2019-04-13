<?php

namespace Ekimik\ApiMiddleware\Tests;

use Ekimik\ApiDesc\Resource\Action;
use Ekimik\ApiMiddleware\RequestValidation;
use Ekimik\ApiUtils\ActionValidator\IActionValidator;
use Ekimik\ApiUtils\ActionValidator\IFactory;
use Ekimik\ApiUtils\Exception\ApiException;
use Ekimik\ApiUtils\InputData\Completion;
use Ekimik\ApiUtils\Resource\Request;
use Ekimik\ApiUtils\Security\RequestIntegrity;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Nette\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class RequestValidationTest extends TestCase {

    /** @var RequestValidation */
    private $middleware;
    /** @var callable */
    private $nextCb;

    protected function setUp() {
        parent::setUp();
        $testName = $this->getName();

        $this->nextCb = function (IRequest $request, IResponse $response) {
            return $response;
        };

        $isValid = true;
        $errors = [];
        if ($testName === 'testExecutionRequestNotValid') {
            $isValid = false;
            $errors = [
                ['message' => 'Foo error']
            ];
        }
        $avf = $this->getMockBuilder(IFactory::class)->setMethods(['create'])->getMockForAbstractClass();
        $av = $this->getMockBuilder(IActionValidator::class)->setMethods(['isValid', 'getErrors'])->getMockForAbstractClass();
        $av->method('isValid')->willReturn($isValid);
        $av->method('getErrors')->willReturn($errors);
        $avf->method('create')->willReturn($av);

        $reqIntegrity = $this->getMockBuilder(RequestIntegrity::class)->setMethods(['check'])->setConstructorArgs(['secret'])->getMock();
        if ($testName === 'testExecutionIntegrityCheckFailed') {
            $reqIntegrity->method('check')->willThrowException(new ApiException('Foo exception', 422));
        } else {
            $reqIntegrity->method('check')->willReturn(true);
        }

        $this->middleware = new RequestValidation('production', $reqIntegrity, $avf);
    }

    /**
     * @covers RequestValidation
     */
    public function testExecutionIntegrityCheckFailed() {
        /** @var IResponse $response */
        $response = call_user_func(
            $this->middleware,
            new ServerRequest('GET', 'http://www.example.com/foo/bar'),
            new Response(),
            $this->nextCb
        );
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            '{"errors":[{"message":"Foo exception"}],"responseData":[]}',
            $response->getBody()->getContents()
        );
    }

    /**
     * @covers RequestValidation
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
     * @covers RequestValidation
     */
    public function testExecutionRequestNotValid() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar');
        $apiRequest = new Request([], new Action('foo/bar', 'GET'), new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(422, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertTrue(Strings::contains($body, "Validation of input data failed, see 'errors' for more info"));
        $this->assertTrue(Strings::contains($body, "Foo error"));
    }

    /**
     * @covers RequestValidation
     */
    public function testExecution() {
        $r = new ServerRequest('GET', 'http://www.example.com/foo/bar');
        $apiRequest = new Request([], new Action('foo/bar', 'GET'), new Completion());
        $r = $r->withAttribute('apiRequest', $apiRequest);

        /** @var IResponse $response */
        $response = call_user_func($this->middleware, $r, new Response(), $this->nextCb);
        $this->assertEquals(200, $response->getStatusCode());
    }

}
