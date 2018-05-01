<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rancoud\Http\Message\Factory\MessageFactory;

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

    /** @var array */
    protected $middlewaresInPipe = [];

    /** @var int */
    protected $currentMiddlewareInPipeIndex = 0;

    /** @var array */
    protected $globalMiddlewares = [];

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
     * @param string $prefixPath
     * @param        $callback
     *
     * @throws \Exception
     */
    public function crud(string $prefixPath, $callback): void
    {
        $this->get($prefixPath, $callback);
        $this->get($prefixPath . '/new', $callback);
        $this->post($prefixPath . '/new', $callback);
        $this->get($prefixPath . '/{id:\d+}', $callback);
        $this->post($prefixPath . '/{id:\d+}', $callback);
        $this->delete($prefixPath . '/{id:\d+}', $callback);
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
        /* @var $request \Rancoud\Http\Message\ServerRequest */
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
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routeParameters as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        $this->pushMiddlewaresToApplyInPipe();

        return $this->handle($request);
    }

    protected function pushMiddlewaresToApplyInPipe(): void
    {
        $this->currentMiddlewareInPipeIndex = 0;
        $this->middlewaresInPipe = array_merge($this->middlewaresInPipe, $this->globalMiddlewares);
        $this->middlewaresInPipe = array_merge($this->middlewaresInPipe, $this->currentRoute->getMiddlewares());
        $this->middlewaresInPipe[] = $this->currentRoute->getCallback();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->getMiddlewareInPipe();
        if (is_callable($middleware)) {
            return call_user_func_array($middleware, [$request, [$this, 'handle']]);
        } elseif ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        } elseif (is_string($middleware)) {
            return (new $middleware())->process($request, $this);
        }

        return (new MessageFactory())->createResponse(404);
    }

    /**
     * @return mixed|null
     */
    protected function getMiddlewareInPipe()
    {
        $middleware = null;

        if (array_key_exists($this->currentMiddlewareInPipeIndex, $this->middlewaresInPipe)) {
            $middleware = $this->middlewaresInPipe[$this->currentMiddlewareInPipeIndex];
            ++$this->currentMiddlewareInPipeIndex;
        }

        return $middleware;
    }

    /**
     * @param $middleware
     */
    public function addGlobalMiddleware($middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    /**
     * @param array $config
     *
     * @throws RouterException
     */
    public function setupRouterAndRoutesWithConfigArray(array $config): void
    {
        $this->treatRouterConfig($config);
        $this->treatRoutesConfig($config);
    }

    /**
     * @param array $config
     *
     * @throws RouterException
     */
    protected function treatRouterConfig(array $config): void
    {
        if (array_key_exists('router', $config) === false) {
            return;
        }

        if (is_array($config['router']) === false) {
            throw new RouterException('Config router has to be an array');
        }

        if (array_key_exists('middlewares', $config['router'])) {
            if (is_array($config['router']['middlewares']) === false) {
                throw new RouterException('Config router/middlewares has to be an array');
            }

            foreach ($config['router']['middlewares'] as $middleware) {
                $this->addGlobalMiddleware($middleware);
            }
        }
    }

    /**
     * @param array $config
     *
     * @throws RouterException
     */
    protected function treatRoutesConfig(array $config): void
    {
        if (array_key_exists('routes', $config) === false) {
            return;
        }

        if (is_array($config['routes']) === false) {
            throw new RouterException('Config routes has to be an array');
        }

        foreach ($config['routes'] as $route) {
            if (array_key_exists('methods', $route) === false) {
                throw new RouterException('Config routes/methods is mandatory');
            }

            if (array_key_exists('url', $route) === false) {
                throw new RouterException('Config routes/url is mandatory');
            }

            if (array_key_exists('callback', $route) === false) {
                throw new RouterException('Config routes/callback is mandatory');
            }

            $newRoute = new Route($route['methods'], $route['url'], $route['callback']);

            if (array_key_exists('constraints', $route)) {
                $newRoute->setParametersConstraints($route['constraints']);
            }

            if (array_key_exists('middlewares', $route)) {
                if (is_array($route['middlewares']) === false) {
                    throw new RouterException('Config routes/middlewares has to be an array');
                }

                foreach ($route['middlewares'] as $middleware) {
                    $newRoute->addMiddleware($middleware);
                }
            }

            if (array_key_exists('name', $route)) {
                $newRoute->setName($route['name']);
            }

            $this->addRoute($newRoute);
        }
    }
}
