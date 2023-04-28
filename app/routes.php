<?php

declare(strict_types=1);

use Framework\Routing\Router;

return static function (Router $router): void {
    $router->add(
        'GET',
        '/',
        static fn () => 'hello world',
    );
    $router->add(
        'GET',
        '/old-home',
        static fn () => $router->redirect('/'),
    );
    $router->add(
        'GET',
        '/has-server-error',
        static fn () => throw new Exception(),
    );
    $router->add(
        'GET',
        '/has-validation-error',
        static fn () => $router->dispatchNotAllowed(),
    );

    $router->add('GET', '/products/view/{product}', static function () use ($router) {
        $parameters = $router->getCurrent()?->getParameters();

        $product = $parameters['product'] ?? '';

        return "product is {$product}";
    });

    $router->add(
        'GET',
        '/services/view/{service?}/{a}',
        static function () use ($router) {
            $parameters = $router->getCurrent()?->getParameters();

            if (empty($parameters['service'])) {
                return 'all services';
            }

            return "service is {$parameters['service']}";
        }
    );
};
