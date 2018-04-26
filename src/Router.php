<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rancoud\Http\Message\Factory\MessageFactory;
use Rancoud\Http\Message\Factory\ServerRequestFactory;

/**
 * Class Router.
 */
class Router implements RequestHandlerInterface
{
    /** @var Route[] */
    protected $routes = [];

    /** @var null */
    protected $url = null;

    /** @var null */
    protected $method = null;

    /** @var Route */
    protected $currentRoute = null;

    /** @var array */
    protected $routeParameters = [];

    /**
     * @param Route $route
     */
    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function get(string $url, $callback): void
    {
        $route = new Route(['GET', 'HEAD'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function post(string $url, $callback): void
    {
        $route = new Route(['POST'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function put(string $url, $callback): void
    {
        $route = new Route(['PUT'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function patch(string $url, $callback): void
    {
        $route = new Route(['PATCH'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function delete(string $url, $callback): void
    {
        $route = new Route(['DELETE'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function options(string $url, $callback): void
    {
        $route = new Route(['OPTIONS'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @param string $url
     * @param        $callback
     *
     * @throws \Exception
     */
    public function any(string $url, $callback): void
    {
        $route = new Route(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $url, $callback);
        $this->addRoute($route);
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function findRouteRequest(ServerRequestInterface $request): bool
    {
        $this->method = $request->getMethod();
        $this->url = $request->getUri()->getPath();

        return $this->find();
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return bool
     */
    public function findRoute(string $method, string $url): bool
    {
        $this->method = $method;
        $this->url = $this->removeQueryFromUrl($url);

        return $this->find();
    }

    /**
     * @return bool
     */
    protected function find(): bool
    {
        $this->currentRoute = null;
        $this->routeParameters = [];

        foreach ($this->routes as $route) {
            if ($this->isNotSameRouteMethod($route)) {
                continue;
            }

            $pattern = '#^' . $route->compileRegex() . '$#s';
            $matches = [];

            if (preg_match($pattern, $this->url, $matches)) {
                array_shift($matches);
                $this->saveRouteParameters($matches);

                $this->currentRoute = $route;

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
    protected function removeQueryFromUrl(string $url): string
    {
        $queryPathPosition = mb_strpos($url, '?');

        if ($queryPathPosition !== false) {
            return mb_substr($url, 0, $queryPathPosition);
        }

        return $url;
    }

    /**
     * @param Route $route
     *
     * @return bool
     */
    protected function isNotSameRouteMethod(Route $route): bool
    {
        return !in_array($this->method, $route->getMethods(), true);
    }

    /**
     * @param array $routeParameters
     */
    protected function saveRouteParameters(array $routeParameters): void
    {
        $this->routeParameters = [];

        foreach ($routeParameters as $key => $value) {
            if (!is_int($key)) {
                $this->routeParameters[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * @param ServerRequestInterface|null $request
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request = null): ResponseInterface
    {
        if ($this->currentRoute === null) {
            //TODO custom 404
            return null;
        }

        if ($request === null) {
            $request = (new ServerRequestFactory())->createServerRequestFromGlobals();
        }

        foreach ($this->routeParameters as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        //compilMiddlewares();

        return $this->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        //get current middleware
        //si pas de middleware tu cries
        // if is_callable($middleware)
        //    call_user_func_array($this->middleware[0], $request, [$this, 'handle']);
        // if instance of middelware
        //    $middleware->process($request, $this)

        if (is_callable($this->currentRoute)) {
            return call_user_func_array($this->currentRoute, [$request, [$this, 'handle']]);
        } elseif ($this->currentRoute instanceof MiddlewareInterface) {
            return $this->currentRoute->process($request, $this);
        }

        return (new MessageFactory())->createResponse();
    }

    /* @var Route[] */
    //protected $routes = [];
    /* @var Route */
    //protected $currentRoute = null;
    /* @var null */
    //protected $url = null;
    /* @var null */
    //protected $method = null;
    /* @var array */
    //protected $middlewares = [];
    /* @var array */
    //protected $groupMiddlewares = [];
    //protected $layers = [];

    /*
     * Router constructor.
     *
     * @param array      $layers
     * @param Route|null $currentRoute
     */
    /*public function __construct(array $layers = [], Route $currentRoute = null)
    {
        :$layers = $layers;
        :$currentRoute = $currentRoute;
    }*/

    /*
     * @param Route $route
     */
    /*public function addRoute(Route $route)
    {
        $this->routes[] = $route;
    }*/

    /*
     * @param string $method
     * @param string $url
     *
     * @return bool
     */
    /*public function findRoute($method, $url)
    {
        $this->method = $method;
        $this->url = $this->removeQueryFromUrl($url);

        foreach ($this->routes as $route) {
            $routeMethod = $route->getMethods();

            if (!in_array($this->method, $routeMethod, true)) {
                continue;
            }

            $pattern = '#^' . $route->compileRegex() . '$#s';
            $matches = [];

            if (preg_match($pattern, $this->url, $matches)) {
                array_shift($matches);
                $route->setParameters($matches);
                $this->currentRoute = $route;

                return true;
            }
        }

        return false;
    }*/

    /*
     * @return null|Route
     */
    /*public function currentRoute()
    {
        return $this->currentRoute;
    }*/

    /*
     * @return null|string
     */
    /*public function currentRouteName()
    {
        return $this->currentRoute->getName();
    }*/

    /*
     * @return null|string
     */
    /*public function currentRouteAction()
    {
        return $this->currentRoute->getAction();
    }*/

    /*
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed|null|string
     */
    /*public function getUrl($name, $arguments = [])
    {
        $url = '';

        foreach ($this->routes as $route) {
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

    /*
     * @param Middleware|string|array $middlewares
     *
     * @throws \InvalidArgumentException
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
            throw new InvalidArgumentException(get_class($middlewares) . ' is not a valid Middleware Layer.');
        }

        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }*/

    /*
     * @param string                  $group
     * @param Middleware|string|array $middlewares
     *
     * @throws \InvalidArgumentException
     */
    /*public function addMiddlewaresToGroup($group, $middlewares)
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

        if (!array_key_exists($group, $this->groupMiddlewares)) {
            $this->groupMiddlewares[$group] = [];
        }

        $this->groupMiddlewares[$group] = array_merge($this->groupMiddlewares[$group], $middlewares);
    }*/

    /*
     * @param $req
     * @param $res
     *
     * @return mixed
     */
    /*public function execute($req, $res)
    {
        $this->completeMiddlewares();

        if (empty($this->middlewares)) {
            $this->currentRoute->execute($req, $res);

            return $res;
        }
        $layers = new $this->middlewares, $this->currentRoute);

        return $layers::next($req, $res, function ($req, $res) {
            return $res;
        });
    }*/

    /*
     * @param $req
     * @param $res
     * @param $core
     *
     * @return mixed
     */
    /*public function next($req, $res, $core)
    {
        $coreFunction = $this->createCoreFunction($core);

        $layers = array_reverse($this->layers);

        $completePipeline = array_reduce($layers, function ($nextLayer, $layer) {
            return Router::createLayer($nextLayer, $layer);
        }, $coreFunction);

        return $completePipeline($req, $res);
    }*/

    /*
     * @param $core
     *
     * @return mixed
     */
    /*private function createCoreFunction($core)
    {
        $currentRoute = $this->currentRoute;

        return function ($req, $res) use ($core, $currentRoute) {
            $currentRoute->execute($req, $res);

            return $core($req, $res);
        };
    }*/

    /*
     * @param Middleware        $nextLayer
     * @param Middleware|string $layer
     *
     * @return mixed
     */
    /*public function createLayer($nextLayer, $layer)
    {
        if (is_string($layer)) {
            $layer = new $layer();
        }

        return function ($req, $res) use ($nextLayer, $layer) {
            return $layer->next($req, $res, $nextLayer);
        };
    }*/

    /*protected function completeMiddlewares()
    {
        if ($this->currentRoute->hasGroup()) {
            $group = $this->currentRoute->getGroup();
            if (array_key_exists($group, $this->groupMiddlewares)) {
                $this->addMiddlewares($this->groupMiddlewares[$group]);
            }
        }

        if ($this->currentRoute->hasMiddlewares()) {
            $this->addMiddlewares($this->currentRoute->getMiddlewares());
        }
    }*/
}
