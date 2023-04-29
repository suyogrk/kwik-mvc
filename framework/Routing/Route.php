<?php

declare(strict_types=1);

namespace Framework\Routing;

class Route
{
    private const         REGEX_SEARCH_PATTERN = '#{([^}]+)}/#';
    private const         OPTIONAL_PARAM_CHAR = '?';
    private const         REGEX_MATCH_OPTIONAL_PARAM = '([^/]*)(?:/?)'; //captures parameters with trailing slash optional
    private const         REGEX_MATCH_REQUIRED_PARAM = '([^/]+)/';
    private const         NORMALISE_PATH_REGEX = '/[\/]{2,}/';
    private string $method;
    private string $path;
    private ?string $name = null;

    /**
     * @var array<string>
     */
    private static array $parameterNames = [];

    /** @var callable $handler */
    private mixed $handler;

    /**
     * @var array<string, string|null>
     */
    private array $parameters = [];

    public function __construct(
        string $method,
        string $path,
        callable $handler
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /*Checks whether the provided method and path matches
     * the methods and paths for route
     *
     * @param string $method HTTP method to check for match
     * @param string $path   URL path to check for match
     */
    public function matches(string $method, string $path): bool
    {
        //if both method and path is matching return true
        if ($this->method === $method && $this->path === $path) {
            return true;
        }

        $pattern = $this->parsePathAndGetPattern();

        if ($this->parsedPatternDoesNotContainParamRegex($pattern)) {
            return false;
        }

        preg_match_all("#{$pattern}#", $this->normalisePath($path), $matches);

        $parameterValues = [];

        if ($matches && count($matches[1]) > 0) {
            foreach ($matches[1] as $value) {
                $parameterValues[] = $value;
            }

            $this->parameters = $this->parseParameterValues($parameterValues);

            return true;
        }

        return false;
    }

    public function dispatch(): mixed
    {
        return call_user_func($this->handler);
    }

    public function parsePathAndGetPattern(): string
    {
        self::$parameterNames = [];

        $pattern = $this->normalisePath($this->path);

        //get all parameter names and replace them with regular expression syntax.
        // -> /test/ remains /test/
        // -> /test/{id} becomes /test/([^/]+)/
        // -> /test/{id?} becomes /test/([^/]*)(?:/?)
        //

        return preg_replace_callback(
            self::REGEX_SEARCH_PATTERN,
            static function (array $found) {
                self::$parameterNames[] = rtrim($found[1], self::OPTIONAL_PARAM_CHAR);

                //check for optional param
                if (str_ends_with($found[1], self::OPTIONAL_PARAM_CHAR)) {
                    return self::REGEX_MATCH_OPTIONAL_PARAM;
                }

                //for required parameters
                return self::REGEX_MATCH_REQUIRED_PARAM;
            },
            $pattern
        ) ?? '';
    }

    public function parsedPatternDoesNotContainParamRegex(string $pattern): bool
    {
        return ! str_contains($pattern, '+') && ! str_contains($pattern, '*');
    }

    /**
     * @param array<string> $parameterValues
     *
     * @return array<string, string|null>
     */
    public function parseParameterValues(array $parameterValues): array
    {
        $emptyValues = array_fill(0, count(self::$parameterNames), null);

        $parameterValues += $emptyValues;

        return array_combine(self::$parameterNames, $parameterValues);
    }

    public function name(?string $name = null): self
    {
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Ensure there is a '/' character before and after the path.
     * Remove duplicate '/' characters
     */
    private function normalisePath(string $path): string
    {
        $path = trim($path, '/');
        $path = "/{$path}/";

        return preg_replace(self::NORMALISE_PATH_REGEX, '/', $path) ?? '';
    }
}
