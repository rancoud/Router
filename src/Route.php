<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Closure;

/**
 * Class Route.
 */
class Route
{
    /** @var array */
    protected $methods = [];

    /** @var string */
    protected $url = '';

    /** @var string|null */
    protected $callback = null;

    /** @var array */
    protected $constraints = [];

    /** @var array */
    protected $middlewares = [];

    /** @var string */
    protected $name;

    /**
     * Route constructor.
     *
     * @param array|string   $methods
     * @param string         $url
     * @param Closure|string $callback
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
        $validMethods = ['CHECKOUT', 'CONNECT', 'COPY', 'DELETE', 'GET', 'HEAD', 'LINK', 'LOCK', 'M-SEARCH', 'MERGE',
            'MKACTIVITY', 'MKCALENDAR', 'MKCOL', 'MOVE', 'NOTIFY', 'OPTIONS', 'PATCH', 'POST', 'PROPFIND', 'PROPPATCH',
            'PURGE', 'PUT', 'REPORT', 'SEARCH', 'SUBSCRIBE', 'TRACE', 'UNLINK', 'UNLOCK', 'UNSUBSCRIBE', 'VIEW'];

        if (is_string($methods)) {
            $methods = [$methods];
        } elseif (!is_array($methods)) {
            throw new RouterException('Method invalid');
        }

        foreach ($methods as $method) {
            if (!in_array($method, $validMethods, true)) {
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
        if (mb_strlen($url) < 1) {
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
        $url = $this->extractInlineContraints();

        $regex = preg_replace('/\{(\w+?)\}/', '(?P<$1>[^/]++)', $url);

        $constraints = array_merge($globalConstraints, $this->constraints);
        foreach ($constraints as $id => $regexRule) {
            $regex = str_replace('<' . $id . '>[^/]++', '<' . $id . '>' . $regexRule, $regex);
        }

        return $regex;
    }

    /**
     * @return string
     */
    protected function extractInlineContraints(): string
    {
        $url = $this->url;

        preg_match('/\{(\w+?):(.+?)\}/', $url, $routeParameters);

        array_shift($routeParameters);
        if (count($routeParameters) > 0) {
            foreach ($routeParameters as $key => $value) {
                $this->constraints[$key] = $value;
            }

            $url = preg_replace('/\{(\w+?):(.+?)\}/', '{$1}', $url);
        }

        return $url;
    }

    /**
     * @param array $constraints
     */
    public function setParametersConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

    /**
     * @return Closure|null|string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param $middleware
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
}
