<?php

declare(strict_types=1);

use Framework\Routing\Router;

require_once __DIR__.'/../vendor/autoload.php';

$router = new Router();

$routes = require_once __DIR__.'/../app/routes.php';

$routes($router);

echo $router->dispatch();
