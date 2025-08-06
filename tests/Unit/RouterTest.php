<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\router\HTTPRouterInterface;
use Monoelf\Framework\http\router\Route;
use Monoelf\Framework\http\router\RouteGroup;
use Monoelf\Framework\http\router\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class RouterTest extends TestCase
{
    public function testRoute(): void
    {
        $router = $this->createRouter();

        $route = $router->add('CUSTOM', '/test', 'handler::method');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['handler', 'method'], $route->handler);
        $this->assertEquals('CUSTOM', $route->method);
        $this->assertEquals('/test', $route->path);
    }

    public function testRouteGroup(): void
    {
        $router = $this->createRouter();

        $routeGroup = $router->group('group', function (Router $router) {
        });

        $this->assertInstanceOf(RouteGroup::class, $routeGroup);
        $this->assertSame('group', $routeGroup->getName());
    }


    public function testDispatch(): void
    {
        $wasCalled = false;
        $handler = function () use (&$wasCalled) {
            $wasCalled = true;
        };
        $router = $this->createRouter();
        $router->get('/test', $handler);
        $request = $this->createMockRequest('/test', 'GET');

        $router->dispatch($request);

        $this->assertTrue($wasCalled);
    }

    public function testGet(): void
    {
        $router = $this->createRouter();

        $router->get('/test', 'handler::method');

        $this->assertTrue($router->has('GET', '/test'));
    }

    public function testPost(): void
    {
        $router = $this->createRouter();

        $router->post('/test', 'handler::method');

        $this->assertTrue($router->has('POST', '/test'));
    }

    public function testPut(): void
    {
        $router = $this->createRouter();

        $router->put('/test', 'handler::method');

        $this->assertTrue($router->has('PUT', '/test'));
    }

    public function testPatch(): void
    {
        $router = $this->createRouter();

        $router->patch('/test', 'handler::method');

        $this->assertTrue($router->has('PATCH', '/test'));
    }

    public function testDelete(): void
    {
        $router = $this->createRouter();

        $router->delete('/test', 'handler::method');

        $this->assertTrue($router->has('DELETE', '/test'));
    }

    public function testCustom(): void
    {
        $router = $this->createRouter();

        $router->add('CUSTOM', '/test', 'handler::method');

        $this->assertTrue($router->has('CUSTOM', '/test'));
    }

    public function testExistingRouteButNotExistingMethod(): void
    {
        $router = $this->createRouter();

        $router->add('CUSTOM', '/test', 'handler::method');

        $this->assertFalse($router->has('NOT_EXISTS', '/test'));
    }

    public function testHandleByController(): void
    {
        $controller = $this->createMockController('actionTest');
        $router = $this->createRouter();
        $router->get('/test', [$controller, 'actionTest']);
        $request = $this->createMockRequest('/test', 'GET');

        $controller->expects($this->once())->method('actionTest');

        $router->dispatch($request);
    }


    public function testHandleByControllerWithExpectedParam(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{param}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET', ['param' => 123]);

        $controller->expects($this->once())->method('actionIndex')->with(['param' => 123]);

        $router->dispatch($request);
    }

    public function testHandleByControllerWithTwoExpectedParam(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{param1}{param2}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET', ['param2' => 2, 'param1' => 1]);

        $controller->expects($this->once())->method('actionIndex')->with([
            'param1' => 1,
            'param2' => 2
        ]);

        $router->dispatch($request);
    }

    public function testHandleByControllerWithTwoExpectedParamsAndGivenExtraQueryParams(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{param1}{param2}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET', ['param2' => 2, 'param1' => 1, 'extra' => 3]);

        $controller->expects($this->once())->method('actionIndex')->with([
            'param1' => 1,
            'param2' => 2
        ]);

        $router->dispatch($request);
    }

    public function testHandleByControllerWithExpectedParamWithoutQueryParams(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{param}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET');

        $this->expectException(InvalidArgumentException::class);

        $router->dispatch($request);
    }

    public function testHandleByControllerWithParamsWithDefaultValueWithGivenQueryValue(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{?paramDefault=700}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET', ['paramDefault' => 1]);

        $controller->expects($this->once())->method('actionIndex')->with([
            'paramDefault' => 1
        ]);

        $router->dispatch($request);
    }

    public function testHandleByControllerWithParamsWithDefaultWithoutGivenValue(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->get('/test?{?paramDefault=700}', [$controller, 'actionIndex']);
        $request = $this->createMockRequest('/test', 'GET');

        $controller->expects($this->once())->method('actionIndex')->with([
            'paramDefault' => 700
        ]);

        $router->dispatch($request);
    }

    public function testHandleByControllerGroupsSuccess(): void
    {
        $controller = $this->createMockController();
        $router = $this->createRouter();
        $router->group('api', function (HTTPRouterInterface $router) use ($controller): void {
            $router->group('v1', function (HTTPRouterInterface $router) use ($controller): void {
                $router->get('/test', [$controller, 'actionIndex']);
            });
        });
        $request = $this->createMockRequest('/api/v1/test', 'GET');

        $controller->expects($this->once())->method('actionIndex');

        $router->dispatch($request);
    }

    private function createMockController(string $action = 'actionIndex'): object
    {
        return $this->getMockBuilder(\stdClass::class)
            ->addMethods([$action])
            ->getMock();
    }

    private function createMockRequest(string $url, string $method, array $queryParams = []): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($url);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);
        $request->method('getQueryParams')->willReturn($queryParams);

        return $request;
    }

    private function createRouter(): Router
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('call')
            ->willReturnCallback(function ($handler, $method, $params) {
                return $handler->$method($params);
            });

        return new Router($mockContainer);
    }
}
