<?php

use Framework\Routing\Route;
use Symfony\Component\Console\Helper\Dumper;

use function PHPUnit\Framework\assertFalse;

beforeEach(function () {
    $this->method = '42';
    $this->path = '42';
    $this->handler = function () {
    };
    $this->route = new Route($this->method, $this->path, $this->handler);
});

afterEach(function () {
    unset($this->method);
    unset($this->path);
    unset($this->handler);
    unset($this->route);
});

test('getMethod function is working correctly', function () {
    $expected = '42';
    $property = (new ReflectionClass(Route::class))->getProperty('method');
    $property->setAccessible(true);
    $property->setValue($this->route, $expected);

    $this->assertSame($expected, $this->route->getPath());
});

test('getPath function is working correctly', function () {
    $expected = '42';
    $property = (new ReflectionClass(Route::class))
        ->getProperty('path');
    $property->setValue($this->route, $expected);
    $this->assertSame($expected, $this->route->getPath());
});

test('getParameters function is working correctly', function () {
    $expected = [];
    $property = (new ReflectionClass(Route::class))
        ->getProperty('parameters');
    $property->setValue($this->route, $expected);
    $this->assertSame($expected, $this->route->getParameters());
});

test('test matches function returns the correct result', function(string $method, string $path, string $requestPath, callable $handler, bool $result) {
    $route = new Route($method, $path, $handler);

    expect($route->matches($method, $requestPath))->toBe($result);
})->with([
             'get request optional parameters may be missing' => [
                 'method' => 'GET',
                 'path' => '/a/{a?}',
                 'request_path' => '/a/b',
                 'handler' => function() {},
                 'result' => true,
             ]
         ])->skip();

test('matches function returns the correct result', function(string $method, string $path, string $requestPath, callable $handler, bool $result) {
    $route = new Route($method, $path, $handler);

    expect($route->matches($method, $requestPath))->toBe($result);
})->with([
    'empty path' => [
        'method' => 'GET',
        'path' => '/',
        'request_path' => '/',
        'handler' => function() {},
        'result' => true,
    ],
    'normal get path' => [
        'method' => 'GET',
        'path' => '/abc',
        'request_path' => '/abc',
        'handler' => function() {},
        'result' => true,
    ],
    'complex get path' => [
        'method' => 'GET',
        'path' => '/a/b/c',
        'request_path' => '/a/b/c',
        'handler' => function() {},
        'result' => true,
    ],
    'get path should not have required parameters missing' => [
        'method' => 'GET',
        'path' => '/{a}',
        'request_path' => '/',
        'handler' => function() {},
        'result' => false,
    ],
    'get path should not have required parameters missing 2' => [
        'method' => 'GET',
        'path' => '/{a}/{b}',
        'request_path' => '/a/',
        'handler' => function() {},
        'result' => false,
    ],
    'get path should have required parameters' => [
        'method' => 'GET',
        'path' => '/{a}',
        'request_path' => '/a',
        'handler' => function() {},
        'result' => true,
    ],
    'get path should have required parameters 2' => [
        'method' => 'GET',
        'path' => '/{a}/{b}',
        'request_path' => '/a/b',
        'handler' => function() {},
        'result' => true,
    ],
    'get path should work with optional parameters' => [
        'method' => 'GET',
        'path' => '/{a?}',
        'request_path' => '/a',
        'handler' => function() {},
        'result' => true,
    ],
]);
