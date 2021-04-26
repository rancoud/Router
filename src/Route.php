<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Rancoud\Http\Message\Request;

/**
 * Class Route.
 */
class Route
{
    /** @var array */
    protected array $methods = [];

    /** @var string */
    protected string $url = '';

    /** @var \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string|null */
    protected $callback;

    /** @var array */
    protected array $constraints = [];

    /** @var array */
    protected array $middlewares = [];

    /** @var string|null */
    protected ?string $name = null;

    /** @var string|null */
    protected ?string $host = null;

    /** @var array */
    protected array $hostConstraints = [];

    /** @var array */
    protected array $hostParameters = [];

    /** @var array */
    protected array $optionalsParameters = [];

    /**
     * Route constructor.
     *
     * @param array|string                                                $methods
     * @param string                                                      $url
     * @param \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string $callback
     *
     * @throws RouterException
     */
    public function __construct($methods, string $url, $callback)
    {
        $this->setMethods($methods);
        $this->setUrl($url);

        $this->callback = $callback;
    }

    /**
     * @param array|string $methods
     *
     * @throws RouterException
     */
    protected function setMethods($methods): void
    {
        if (\is_string($methods)) {
            $methods = [$methods];
        } elseif (!\is_array($methods)) {
            throw new RouterException('Method invalid');
        }

        foreach ($methods as $method) {
            if (!\in_array($method, Request::$methods, true)) {
                throw new RouterException('Method invalid: ' . $method);
            }
        }

        $this->methods = $methods;
    }

    /**
     * @param string $url
     *
     * @throws RouterException
     */
    protected function setUrl(string $url): void
    {
        if ($url === '') {
            throw new RouterException('Empty url');
        }

        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array $globalConstraints
     *
     * @return string
     */
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

    /**
     * @param string $string
     * @param string $arrayName
     *
     * @return string
     */
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

    /**
     * @param array $constraints
     */
    public function setParametersConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

    /**
     * @return \Closure|string|Router|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param \Closure|\Psr\Http\Server\MiddlewareInterface|Router|string $middleware
     */
    public function addMiddleware($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getParametersConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param array $routeParameters
     *
     * @return string
     */
    public function generateUrl(array $routeParameters = []): string
    {
        $url = $this->getUrl();
        $url = \preg_replace('/{(\w+?):(.+?)}/', '{$1}', $url);
        foreach ($routeParameters as $parameter => $value) {
            $url = \str_replace('{' . $parameter . '}', (string) $value, $url);
        }

        return $url;
    }

    /**
     * @param string $host
     * @param array  $hostConstraints
     */
    public function setHost(string $host, array $hostConstraints = []): void
    {
        $this->host = $host;
        $this->hostConstraints = $hostConstraints;
    }

    /**
     * @param array $hostConstraints
     */
    public function setHostConstraints(array $hostConstraints): void
    {
        $this->hostConstraints = $hostConstraints;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @param array  $globalConstraints
     *
     * @return bool
     */
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

    /**
     * @param array $hostParameters
     */
    protected function saveHostParameters(array $hostParameters): void
    {
        $this->hostParameters = [];

        foreach ($hostParameters as $key => $value) {
            if (!\is_int($key)) {
                $this->hostParameters[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getHostParameters(): array
    {
        return $this->hostParameters;
    }

    /**
     * @param array $optionalsParameters
     */
    public function setOptionalsParameters(array $optionalsParameters): void
    {
        $this->optionalsParameters = $optionalsParameters;
    }

    /**
     * @return array
     */
    public function getOptionalsParameters(): array
    {
        return $this->optionalsParameters;
    }
}
