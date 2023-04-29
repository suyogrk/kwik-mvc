<?php

use Framework\Routing\Route;
use Framework\Routing\Router;

beforeEach(function () {
    $this->router = new Router();
});

afterEach(function () {
    unset($this->router);
});
test(
/**
 * @throws ReflectionException
 */ /**
 * @throws ReflectionException
 */ 'add function works correctly', function () {
    $method  = 'GET';
    $path    = '/';
    $handler = function () {
        return "test";
    };

    $reflection = (new ReflectionClass(Router::class));

    $addMethod = $reflection->getMethod('add');

    $addMethod->invoke($this->router, $method, $path, $handler);

    $routes = $reflection->getProperty('routes')->getValue($this->router);

    expect($routes)->toBeArray()
                   ->toHaveCount(1)
                   ->and($routes[0])
                   ->toBeInstanceOf(Route::class)
                   ->and($routes[0]->getPath())->toBe($path)
                   ->and($routes[0]->getMethod())->toBe($method)
                   ->and($routes[0]->dispatch())->toBe('test');
});

test(
/**
 * @throws ReflectionException
 */ 'dispatch function', function (string $method, string $path, callable $handler) {
    $route = $this->getMockBuilder(Route::class)->setConstructorArgs([$method, $path, $handler])->getMock();

    $route->expects($this->once())->method('dispatch')->willReturn('found');
    $route->expects($this->once())->method('getPath')->willReturn($path);
    $route->expects($this->once())->method('matches')->willReturn(true);

    $reflection = (new ReflectionClass(Router::class));
    $reflection->getProperty('routes')->setValue($this->router, [$route]);

    expect($this->router->dispatch())->toBe('found');

})->with([
             'get route' => [
                 'method'  => 'GET',
                 'path'    => '/',
                 'handler' => fn() => 'found',
             ],
         ]);

test('errorHandler function', function (int $code, callable $handler) {
    $this->router->errorHandler($code, $handler);

    $reflection = (new ReflectionClass(Router::class));

    $errorHandlers = $reflection->getProperty('errorHandlers')->getValue($this->router);
    expect($errorHandlers[$code])->toBe($handler);
})->with([
             [
                 'code'    => 404,
                 'handler' => fn() => 'not found',
             ],
         ]);

test('dispatchNotFound function', function () {
    $this->router->errorHandler(404, fn() => 'not found');
    expect($this->router->dispatchNotFound())->toBe('not found');
});

test('dispatchError function', function () {
    $this->router->errorHandler('500', fn() => 'error');
    expect($this->router->dispatchError())->toBe('error');
});

test('testRedirect function');

test('getCurrentRoute function', function () {
    $expected = $this->createMock(Route::class);
    $property = (new ReflectionClass(Router::class))
        ->getProperty('currentRoute');
    $property->setValue($this->router, $expected);
    $this->assertSame($expected, $this->router->getCurrentRoute());
});