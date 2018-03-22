<?php

declare(strict_types=1);

namespace Rancoud\Router;

use InvalidArgumentException;

//use RTools\HTTP\Request;
//use RTools\HTTP\Response;

/**
 * Class Router.
 */
class Router
{
    /** @var Route[] */
    protected static $routes = [];
    /** @var null */
    protected static $url = null;
    /** @var null */
    protected static $method = null;
    /** @var Route */
    protected static $currentRoute = null;

    /**
     * @param Route $route
     */
    public static function addRoute(Route $route)
    {
        self::$routes[] = $route;
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function get(string $url, $callback)
    {
        $route = new Route(['GET', 'HEAD'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function post(string $url, $callback)
    {
        $route = new Route(['POST'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function put(string $url, $callback)
    {
        $route = new Route(['PUT'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function patch(string $url, $callback)
    {
        $route = new Route(['PATCH'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function delete(string $url, $callback)
    {
        $route = new Route(['DELETE'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function options(string $url, $callback)
    {
        $route = new Route(['OPTIONS'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     */
    public static function any(string $url, $callback)
    {
        $route = new Route(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $url, $callback);
        self::addRoute($route);
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return bool
     */
    public static function findRoute($method, $url)
    {
        self::$method = $method;
        self::$url = self::removeQueryFromUri($url);

        foreach (self::$routes as $route) {
            $routeMethod = $route->getMethods();

            if (!in_array(self::$method, $routeMethod, true)) {
                continue;
            }

            $pattern = '#^' . $route->compileRegex() . '$#s';
            $matches = [];

            if (preg_match($pattern, self::$url, $matches)) {
                array_shift($matches);
                $route->setParameters($matches);
                self::$currentRoute = $route;

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected static function removeQueryFromUri($url)
    {
        $queryPathPosition = mb_strpos($url, '?');

        if ($queryPathPosition !== false) {
            return mb_substr($url, 0, $queryPathPosition);
        }

        return $url;
    }
    
    
    /** @var Route[] */
    //protected static $routes = [];
    /** @var Route */
    //protected static $currentRoute = null;
    /** @var null */
    //protected static $url = null;
    /** @var null */
    //protected static $method = null;
    /** @var array */
    //protected static $middlewares = [];
    /** @var array */
    //protected static $groupMiddlewares = [];
    //protected static $layers = [];

    /**
     * Router constructor.
     *
     * @param array      $layers
     * @param Route|null $currentRoute
     */
    /*public function __construct(array $layers = [], Route $currentRoute = null)
    {
        static::$layers = $layers;
        static::$currentRoute = $currentRoute;
    }*/

    /**
     * @param Route $route
     */
    /*public static function addRoute(Route $route)
    {
        self::$routes[] = $route;
    }*/

    /**
     * @param string $method
     * @param string $url
     *
     * @return bool
     */
    /*public static function findRoute($method, $url)
    {
        self::$method = $method;
        self::$url = self::removeQueryFromUri($url);

        foreach (self::$routes as $route) {
            $routeMethod = $route->getMethods();

            if (!in_array(self::$method, $routeMethod, true)) {
                continue;
            }

            $pattern = '#^' . $route->compileRegex() . '$#s';
            $matches = [];

            if (preg_match($pattern, self::$url, $matches)) {
                array_shift($matches);
                $route->setParameters($matches);
                self::$currentRoute = $route;

                return true;
            }
        }

        return false;
    }*/

    /**
     * @return null|Route
     */
    /*public static function currentRoute()
    {
        return self::$currentRoute;
    }*/

    /**
     * @return null|string
     */
    /*public static function currentRouteName()
    {
        return self::$currentRoute->getName();
    }*/

    /**
     * @return null|string
     */
    /*public static function currentRouteAction()
    {
        return self::$currentRoute->getAction();
    }*/

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed|null|string
     */
    /*public static function getUrl($name, $arguments = [])
    {
        $url = '';

        foreach (self::$routes as $route) {
            if ($route->getName() === $name) {
                $url = $route->getUri();
                foreach ($arguments as $key => $value) {
                    $url = str_replace('{' . $key . '}', $value, $url);
                }
                break;
            }
        }

        return $url;
    }*/

    /**
     * @param Middleware|string|array $middlewares
     *
     * @throws \InvalidArgumentException
     */
    /*public static function addMiddlewares($middlewares)
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

        self::$middlewares = array_merge(self::$middlewares, $middlewares);
    }*/

    /**
     * @param string                  $group
     * @param Middleware|string|array $middlewares
     *
     * @throws \InvalidArgumentException
     */
    /*public static function addMiddlewaresToGroup($group, $middlewares)
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

        if (!array_key_exists($group, self::$groupMiddlewares)) {
            self::$groupMiddlewares[$group] = [];
        }

        self::$groupMiddlewares[$group] = array_merge(self::$groupMiddlewares[$group], $middlewares);
    }*/

    /**
     * @param $req
     * @param $res
     *
     * @return mixed
     */
    /*public static function execute($req, $res)
    {
        self::completeMiddlewares();

        if (empty(self::$middlewares)) {
            self::$currentRoute->execute($req, $res);

            return $res;
        }
        $layers = new static(self::$middlewares, self::$currentRoute);

        return $layers::next($req, $res, function ($req, $res) {
            return $res;
        });
    }*/

    /**
     * @param $req
     * @param $res
     * @param $core
     *
     * @return mixed
     */
    /*public static function next($req, $res, $core)
    {
        $coreFunction = self::createCoreFunction($core);

        $layers = array_reverse(self::$layers);

        $completePipeline = array_reduce($layers, function ($nextLayer, $layer) {
            return Router::createLayer($nextLayer, $layer);
        }, $coreFunction);

        return $completePipeline($req, $res);
    }*/

    /**
     * @param $core
     *
     * @return mixed
     */
    /*private static function createCoreFunction($core)
    {
        $currentRoute = self::$currentRoute;

        return function ($req, $res) use ($core, $currentRoute) {
            $currentRoute->execute($req, $res);

            return $core($req, $res);
        };
    }*/

    /**
     * @param Middleware        $nextLayer
     * @param Middleware|string $layer
     *
     * @return mixed
     */
    /*public static function createLayer($nextLayer, $layer)
    {
        if (is_string($layer)) {
            $layer = new $layer();
        }

        return function ($req, $res) use ($nextLayer, $layer) {
            return $layer->next($req, $res, $nextLayer);
        };
    }*/

    /*protected static function completeMiddlewares()
    {
        if (self::$currentRoute->hasGroup()) {
            $group = self::$currentRoute->getGroup();
            if (array_key_exists($group, self::$groupMiddlewares)) {
                self::addMiddlewares(self::$groupMiddlewares[$group]);
            }
        }

        if (self::$currentRoute->hasMiddlewares()) {
            self::addMiddlewares(self::$currentRoute->getMiddlewares());
        }
    }*/
}
