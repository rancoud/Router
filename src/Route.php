<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Closure;
use Exception;

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
    protected $callable = null;

    /** @var array */
    protected $constraints = [];

    /** @var array */
    protected $middlewares = [];

    /**
     * Route constructor.
     *
     * @param array|string   $methods
     * @param string         $url
     * @param Closure|string $callable
     *
     * @throws Exception
     */
    public function __construct($methods, string $url, $callable)
    {
        $this->setMethods($methods);
        $this->setUrl($url);

        $this->callable = $callable;
    }

    /**
     * @param array|string $methods
     *
     * @throws Exception
     */
    protected function setMethods($methods): void
    {
        $validMethods = ['CHECKOUT', 'CONNECT', 'COPY', 'DELETE', 'GET', 'HEAD', 'LINK', 'LOCK', 'M-SEARCH', 'MERGE',
            'MKACTIVITY', 'MKCALENDAR', 'MKCOL', 'MOVE', 'NOTIFY', 'OPTIONS', 'PATCH', 'POST', 'PROPFIND', 'PROPPATCH',
            'PURGE', 'PUT', 'REPORT', 'SEARCH', 'SUBSCRIBE', 'TRACE', 'UNLINK', 'UNLOCK', 'UNSUBSCRIBE', 'VIEW'];

        if (is_string($methods)) {
            $methods = [$methods];
        } elseif (!is_array($methods)) {
            throw new Exception('Method invalid');
        }

        foreach ($methods as $method) {
            if (!in_array($method, $validMethods, true)) {
                throw new Exception('Method invalid: ' . $method);
            }
        }

        $this->methods = $methods;
    }

    /**
     * @param string $url
     *
     * @throws Exception
     */
    protected function setUrl(string $url): void
    {
        if (mb_strlen($url) < 1) {
            throw new Exception('Empty url');
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
     * @return string
     */
    public function compileRegex(): string
    {
        $regex = preg_replace('/\{(\w+?)\}/', '(?P<$1>[^/]++)', $this->url);

        foreach ($this->constraints as $id => $regexRule) {
            $regex = str_replace('<' . $id . '>[^/]++', '<' . $id . '>' . $regexRule, $regex);
        }

        return $regex;
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
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @param $middleware
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
