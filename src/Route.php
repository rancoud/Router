<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Rancoud\Http\Message\Request;

class Route
{
    protected array $methods = [];

    protected string $url = '';

    protected \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string $callback;

    protected array $constraints = [];

    protected array $middlewares = [];

    protected ?string $name = null;

    protected ?string $host = null;

    protected array $hostConstraints = [];

    protected array $hostParameters = [];

    protected array $optionalsParameters = [];

    /** @throws RouterException */
    public function __construct(array|string $methods, string $url, \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string $callback)
    {
        $this->setMethods($methods);
        $this->setUrl($url);

        $this->callback = $callback;
    }

    /** @throws RouterException */
    protected function setMethods(array|string $methods): void
    {
        if (\is_string($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            if (!\in_array($method, Request::$methods, true)) {
                throw new RouterException('Method invalid: ' . $method);
            }
        }

        $this->methods = $methods;
    }

    /** @throws RouterException */
    protected function setUrl(string $url): void
    {
        if ($url === '') {
            throw new RouterException('Empty url');
        }

        $this->url = $url;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function compileRegex(array $globalConstraints): string
    {
        $url = $this->extractInlineContraints($this->url, 'constraints');

        $regex = \preg_replace('/{(\w+?)}/', '(?P<$1>[^/]++)', $url);

        $constraints = \array_merge($globalConstraints, $this->constraints);

        foreach ($this->optionalsParameters as $key => $defaultValue) {
            $regex = \str_replace('(?P<' . $key . '>[^/]++)', '?(?P<' . $key . '>[^/]++)?', $regex);
        }

        foreach ($constraints as $id => $regexRule) {
            $regex = \str_replace('<' . $id . '>[^/]++', '<' . $id . '>' . $regexRule, $regex);
        }

        return $regex;
    }

    protected function extractInlineContraints(string $string, string $arrayName): string
    {
        \preg_match('/{(\w+?):(.+?)}/', $string, $parameters);

        \array_shift($parameters);
        $max = \count($parameters);
        if ($max > 0) {
            for ($i = 0; $i < $max; $i += 2) {
                $this->{$arrayName}[$parameters[$i]] = $parameters[$i + 1];
            }

            $string = \preg_replace('/{(\w+?):(.+?)}/', '{$1}', $string);
        }

        return $string;
    }

    public function setParametersConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

    public function getCallback(): \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string
    {
        return $this->callback;
    }

    public function addMiddleware(\Closure|\Psr\Http\Server\MiddlewareInterface|Router|string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParametersConstraints(): array
    {
        return $this->constraints;
    }

    public function generateUrl(array $routeParameters = []): string
    {
        $url = $this->getUrl();
        $url = \preg_replace('/{(\w+?):(.+?)}/', '{$1}', $url);
        foreach ($routeParameters as $parameter => $value) {
            $url = \str_replace('{' . $parameter . '}', (string) $value, $url);
        }

        return $url;
    }

    public function setHost(string $host, array $hostConstraints = []): void
    {
        $this->host = $host;
        $this->hostConstraints = $hostConstraints;
    }

    public function setHostConstraints(array $hostConstraints): void
    {
        $this->hostConstraints = $hostConstraints;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function isSameHost(string $host, array $globalConstraints): bool
    {
        if ($this->host === null) {
            return true;
        }

        if (\mb_strpos($this->host, '{') === false) {
            return $this->host === $host;
        }

        $regex = $this->extractInlineContraints($this->host, 'hostConstraints');

        $regex = \preg_replace('/{(\w+?)}/', '(?P<$1>[^.]++)', $regex);

        $constraints = \array_merge($globalConstraints, $this->hostConstraints);
        foreach ($constraints as $id => $regexRule) {
            $regex = \str_replace('<' . $id . '>[^.]++', '<' . $id . '>' . $regexRule, $regex);
        }
        $pattern = '#^' . $regex . '$#s';
        $matches = [];

        if (\preg_match($pattern, $host, $matches)) {
            \array_shift($matches);
            $this->saveHostParameters($matches);

            return true;
        }

        return false;
    }

    protected function saveHostParameters(array $hostParameters): void
    {
        $this->hostParameters = [];

        foreach ($hostParameters as $key => $value) {
            if (!\is_int($key)) {
                $this->hostParameters[$key] = $value;
            }
        }
    }

    public function getHostParameters(): array
    {
        return $this->hostParameters;
    }

    public function setOptionalsParameters(array $optionalsParameters): void
    {
        $this->optionalsParameters = $optionalsParameters;
    }

    public function getOptionalsParameters(): array
    {
        return $this->optionalsParameters;
    }
}
