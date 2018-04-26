<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Closure;
use Exception;
use Psr\Http\Server\MiddlewareInterface;

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
    //protected $parameters = [];
    /** @var MiddlewareInterface[]|string[] */
    //protected $middlewares = [];
    /** @var string|null */
    //protected $group = null;

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

    /*
     * @param array $regexes
     *
     * @return $this
     */
    /*public function setRegex(array $regexes)
    {
        $this->regexes = $regexes;

        return $this;
    }*/

    /*
     * @return array|null
     */
    /*public function getMethods()
    {
        return $this->methods;
    }*/

    /*
     * @return null|string
     */
    /*public function getUri()
    {
        return $this->url;
    }*/

    /*
     * @return Closure|null|string
     */
    /*public function getAction()
    {
        return $this->action;
    }*/

    /*
     * @return array
     */
    /*public function getRegex()
    {
        return $this->regexes;
    }*/

    /*
     * @return string
     */
    /*public function compileRegex()
    {
        $regex = preg_replace('/\{(\w+?)\}/', '(?P<$1>[^/]++)', $this->url);

        foreach ($this->regexes as $id => $pattern) {
            $regex = str_replace('<' . $id . '>[^/]++', '<' . $id . '>' . $pattern, $regex);
        }

        return $regex;
    }*/

    /*
     * @param $parameters
     */
    /*public function setParameters($parameters)
    {
        foreach ($parameters as $key => $value) {
            if (!is_int($key)) {
                unset($parameters[$key]);
            }
        }

        $this->parameters = $parameters;
    }*/

    /*
     * @param Middleware|string|array $middlewares
     *
     * @throws Exception
     */
    /*public function addMiddlewares($middlewares)
    {
        if ($middlewares instanceof Middleware) {
            $middlewares = [$middlewares];
        } else {
            if (is_string($middlewares)) {
                $middlewares = [$middlewares];
            }
        }

        if (!is_array($middlewares)) {
            throw new Exception(get_class($middlewares) . ' is not a valid Middleware Layer.');
        }

        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }*/

    /*
     * @return Middleware[]|\string[]
     */
    /*public function getMiddlewares()
    {
        return $this->middlewares;
    }*/

    /*
     * @return bool
     */
    /*public function hasMiddlewares()
    {
        return !empty($this->middlewares);
    }*/

    /*
     * @param string $group
     */
    /*public function setGroup($group)
    {
        $this->group = $group;
    }*/

    /*
     * @return bool
     */
    /*public function hasGroup()
    {
        return !empty($this->group);
    }*/

    /*
     * @return null|string
     */
    /*public function getGroup()
    {
        return $this->group;
    }*/

    /*
     * @param $req
     * @param $res
     */
    /*public function execute($req, $res)
    {
        $parameters = array_merge([$req, $res], $this->parameters);

        if ($this->action instanceof Closure) {
            $html = call_user_func_array($this->action, $parameters);
        } else {
            $parts = explode('::', $this->action);
            $instance = new $parts[0]();
            $html = call_user_func_array([$instance, $parts[1]], $parameters);
        }

        //$res->addBodyContent($html);
    }*/
}
