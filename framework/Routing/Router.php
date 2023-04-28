<?php

declare(strict_types=1);

namespace Framework\Routing;

use Throwable;

final class Router
{
    /**
     * @var array<Route>
     */
    private array $routes = [];

    /** @var array<int|string, callable> */
    private array $errorHandlers = [];

    private Route $current;

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

    public function dispatch(): mixed
    {
        $paths = $this->paths();

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = $_SERVER['REQUEST_URI'] ?? '/';

        $matching = $this->match($requestMethod, $requestPath);

        if ($matching) {
            try {
                $this->current = $matching;

                return $matching->dispatch();
            } catch (Throwable $e) {
                return $this->dispatchError();
            }
        }

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

    public function getCurrent(): ?Route
    {
        return $this->current;
    }

    /**
     * @return array<string>
     */
    private function paths(): array
    {
        $paths = [];

        foreach ($this->routes as $route) {
            $paths[] = $route->getPath();
        }

        return $paths;
    }

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
