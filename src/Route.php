<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Closure;
use InvalidArgumentException;

//use RTools\HTTP\Request;
//use RTools\HTTP\Response;

/**
 * Class Route.
 */
class Route
{
    /** @var array|null */
    protected $methods = null;
    /** @var string|null */
    protected $uri = null;
    /** @var Closure|string|null */
    protected $action = null;
    /** @var string|null */
    protected $name = null;
    /** @var array */
    protected $regexes = [];
    /** @var array */
    protected $parameters = [];
    /** @var Middleware[]|string[] */
    protected $middlewares = [];
    /** @var string|null */
    protected $group = null;

    /**
     * Route constructor.
     *
     * @param array          $methods
     * @param string         $uri
     * @param Closure|string $action
     */
    public function __construct(array $methods, $uri, $action)
    {
        $this->methods = $methods;
        $this->uri = $uri;
        $this->action = $action;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function get($uri, $action)
    {
        $route = new self(['GET', 'HEAD'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function post($uri, $action)
    {
        $route = new self(['POST'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function put($uri, $action)
    {
        $route = new self(['PUT'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function patch($uri, $action)
    {
        $route = new self(['PATCH'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function delete($uri, $action)
    {
        $route = new self(['DELETE'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function options($uri, $action)
    {
        $route = new self(['OPTIONS'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param array          $methods
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function match(array $methods, $uri, $action)
    {
        $route = new self($methods, $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string         $uri
     * @param Closure|string $action
     *
     * @return Route
     */
    public static function any($uri, $action)
    {
        $route = new self(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);

        Router::addRoute($route);

        return $route;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array $regexes
     *
     * @return $this
     */
    public function setRegex(array $regexes)
    {
        $this->regexes = $regexes;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return null|string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return Closure|null|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getRegex()
    {
        return $this->regexes;
    }

    /**
     * @return string
     */
    public function compileRegex()
    {
        $regex = preg_replace('/\{(\w+?)\}/', '(?P<$1>[^/]++)', $this->uri);

        foreach ($this->regexes as $id => $pattern) {
            $regex = str_replace('<' . $id . '>[^/]++', '<' . $id . '>' . $pattern, $regex);
        }

        return $regex;
    }

    /**
     * @param $parameters
     */
    public function setParameters($parameters)
    {
        foreach ($parameters as $key => $value) {
            if (!is_int($key)) {
                unset($parameters[$key]);
            }
        }

        $this->parameters = $parameters;
    }

    /**
     * @param Middleware|string|array $middlewares
     *
     * @throws \InvalidArgumentException
     */
    public function addMiddlewares($middlewares)
    {
        if ($middlewares instanceof Middleware) {
            $middlewares = [$middlewares];
        } else {
            if (is_string($middlewares)) {
                $middlewares = [$middlewares];
            }
        }

        if (!is_array($middlewares)) {
            throw new InvalidArgumentException(get_class($middlewares) . ' is not a valid Middleware Layer.');
        }

        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }

    /**
     * @return Middleware[]|\string[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @return bool
     */
    public function hasMiddlewares()
    {
        return !empty($this->middlewares);
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return bool
     */
    public function hasGroup()
    {
        return !empty($this->group);
    }

    /**
     * @return null|string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param $req
     * @param $res
     */
    public function execute($req, $res)
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
    }
}
