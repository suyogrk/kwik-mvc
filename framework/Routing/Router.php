<?php

declare(strict_types=1);

namespace Framework\Routing;

use Exception;
use Throwable;

final class Router
{
    public const OPTIONAL_PARAMETER_REGEX = '#{[^}]+}#';
    /**
     * @var array<Route>
     */
    private array $routes = [];

    /** @var array<int|string, callable> */
    private array $errorHandlers = [];

    private Route $currentRoute;

    /**
     * @param string   $method  Http method
     * @param string   $path    The URL Path
     * @param callable $handler Callable function to be executed when the route is matched
     */
    public function add(
        string $method,
        string $path,
        callable $handler
    ): Route {
        return $this->routes[] = new Route(
            $method,
            $path,
            $handler
        );
    }

    /** Dispatch the route for the correct path
     */
    public function dispatch(): mixed
    {
        $paths = $this->getPaths();

        //Get the method and uri from the server
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = $_SERVER['REQUEST_URI'] ?? '/';

        // Match the request method and path to the correct route
        $matchingRoute = $this->match($requestMethod, $requestPath);

        if ($matchingRoute) {
            try {
                $this->currentRoute = $matchingRoute;

                return $matchingRoute->dispatch();
            } catch (Throwable $e) {
                return $this->dispatchError();
            }
        }

        //if the path exists but the method is not present send not allowed message
        if (in_array($requestPath, $paths)) {
            return $this->dispatchNotAllowed();
        }

        return $this->dispatchNotFound();
    }

    public function errorHandler(int $code, callable $handler): void
    {
        $this->errorHandlers[$code] = $handler;
    }

    public function dispatchNotAllowed(): string
    {
        $this->errorHandlers[400] ??= static fn () => 'not allowed';

        return $this->errorHandlers[400]();
    }

    public function dispatchNotFound(): string
    {
        $this->errorHandlers[404] ??= static fn () => 'not found';

        return $this->errorHandlers[404]();
    }

    public function dispatchError(): string
    {
        $this->errorHandlers[500] ??= static fn () => 'server error';

        return $this->errorHandlers[500]();
    }

    public function redirect(string $path): void
    {
        header(
            "Location: {$path}",
            $replace = true,
            $code = 301
        );

        exit;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    /**
     * @param array<string, mixed>  $parameters
     *
     * @throws Exception
     */
    public function route(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                $finds = [];
                $replaces = [];

                foreach ($parameters as $key => $value) {
                    //for required parameters
                    $finds[] = "{{$key}}";
                    $replaces[] = $value;

                    //for optional parameters
                    $finds[] = "{{$key}?}";
                    $replaces[] = $value;
                }

                $path = $route->getPath();
                $path = str_replace($finds, $replaces, $path);

                //remove optional parameters that are not provided
                return preg_replace(self::OPTIONAL_PARAMETER_REGEX, '', $path) ?? '';
            }
        }

        throw new Exception('no route with that name');
    }

    /**
     * @return array<string>
     */
    private function getPaths(): array
    {
        $paths = [];

        foreach ($this->routes as $route) {
            $paths[] = $route->getPath();
        }

        return $paths;
    }

    /**
     * Matches the HTTP method and Path to the route.
     * return Route object if route is matched
     * return null if no route is matched.
     */
    private function match(string $method, string $path): ?Route
    {
        foreach ($this->routes as $route) {
            /** @var Route $route */

            if ($route->matches($method, $path)) {
                return $route;
            }
        }

        return null;
    }
}
