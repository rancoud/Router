<?php

declare(strict_types=1);

namespace Rancoud\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements RequestHandlerInterface
{
    /** @var Route[] */
    protected array $routes = [];

    protected ?string $url = null;

    protected ?string $method = null;

    protected ?Route $currentRoute = null;

    protected array $routeParameters = [];

    protected array $middlewaresInPipe = [];

    protected int $currentMiddlewareInPipeIndex = 0;

    protected array $globalMiddlewares = [];

    protected array $globalConstraints = [];

    protected ?string $host = null;

    protected ?string $hostRouter = null;

    protected array $hostConstraints = [];

    protected array $hostParameters = [];

    protected \Closure|MiddlewareInterface|string|null $default404 = null;

    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /** @throws RouterException */
    public function get(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['GET', 'HEAD'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function post(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['POST'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function put(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['PUT'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function patch(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['PATCH'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function delete(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['DELETE'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function options(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['OPTIONS'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function any(string $url, \Closure|MiddlewareInterface|self|string $callback): Route
    {
        $route = new Route(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $url, $callback);
        $this->addRoute($route);

        return $route;
    }

    /** @throws RouterException */
    public function crud(string $prefixPath, \Closure|MiddlewareInterface|self|string $callback): void
    {
        $this->get($prefixPath, $callback);
        $this->get($prefixPath . '/new', $callback);
        $this->post($prefixPath . '/new', $callback);
        $this->get($prefixPath . '/{id:\d+}', $callback);
        $this->post($prefixPath . '/{id:\d+}', $callback);
        $this->delete($prefixPath . '/{id:\d+}', $callback);
    }

    /** @return Route[] */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    public function findRouteRequest(ServerRequestInterface $request): bool
    {
        $this->method = $request->getMethod();
        $this->url = $request->getUri()->getPath();
        $this->host = null;

        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_HOST'])) {
            $this->host = $serverParams['HTTP_HOST'];
        } elseif (isset($serverParams['SERVER_NAME'])) {
            $this->host = $serverParams['SERVER_NAME'];
        }

        return $this->find();
    }

    public function findRoute(string $method, string $url, ?string $host = null): bool
    {
        $this->method = $method;
        $this->url = $this->removeQueryFromUrl($url);
        $this->host = $host;

        return $this->find();
    }

    protected function find(): bool
    {
        $this->currentRoute = null;
        $this->routeParameters = [];

        if ($this->isNotSameRouterHost()) {
            return false;
        }

        foreach ($this->routes as $route) {
            if ($this->isNotSameRouteMethod($route)) {
                continue;
            }

            if ($this->isNotSameRouteHost($route)) {
                continue;
            }

            $pattern = '#^' . $route->compileRegex($this->globalConstraints) . '$#s';
            $matches = [];

            if (\preg_match($pattern, $this->url, $matches)) {
                \array_shift($matches);
                $this->saveRouteParameters($matches, $route->getOptionalsParameters());

                $this->currentRoute = $route;

                return true;
            }
        }

        return false;
    }

    protected function removeQueryFromUrl(string $url): string
    {
        $queryPathPosition = \mb_strpos($url, '?');

        if ($queryPathPosition !== false) {
            return \mb_substr($url, 0, $queryPathPosition);
        }

        return $url;
    }

    protected function isNotSameRouterHost(): bool
    {
        if ($this->hostRouter === null) {
            return false;
        }

        if ($this->host === null) {
            return true;
        }

        if (\mb_strpos($this->hostRouter, '{') === false) {
            return !($this->hostRouter === $this->host);
        }

        $regex = $this->extractInlineContraints($this->hostRouter, 'hostConstraints');

        $regex = \preg_replace('/{(\w+?)}/', '(?P<$1>[^.]++)', $regex);

        $constraints = \array_merge($this->globalConstraints, $this->hostConstraints);
        foreach ($constraints as $id => $regexRule) {
            $regex = \str_replace('<' . $id . '>[^.]++', '<' . $id . '>' . $regexRule, $regex);
        }
        $pattern = '#^' . $regex . '$#s';
        $matches = [];

        if (\preg_match($pattern, $this->host, $matches)) {
            \array_shift($matches);
            $this->saveHostParameters($matches);

            return false;
        }

        return true;
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

    protected function isNotSameRouteMethod(Route $route): bool
    {
        return !\in_array($this->method, $route->getMethods(), true);
    }

    protected function isNotSameRouteHost(Route $route): bool
    {
        if ($this->host === null && $route->getHost() === null) {
            return false;
        }

        if ($this->host === null && $route->getHost() !== null) {
            return true;
        }

        return !$route->isSameHost($this->host, $this->hostConstraints);
    }

    protected function saveRouteParameters(array $routeParameters, array $optionalsParameters): void
    {
        $this->routeParameters = [];

        foreach ($routeParameters as $key => $value) {
            if (!\is_int($key)) {
                $this->routeParameters[$key] = $value;
            }
        }

        foreach ($optionalsParameters as $key => $value) {
            if (!isset($this->routeParameters[$key])) {
                $this->routeParameters[$key] = $value;
            }
        }
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /** @throws RouterException */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->currentRoute === null) {
            return $this->generate404($request);
        }

        foreach ($this->hostParameters as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        foreach ($this->currentRoute->getHostParameters() as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        foreach ($this->routeParameters as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        $this->pushMiddlewaresToApplyInPipe();

        return $this->handle($request);
    }

    /** @throws RouterException */
    protected function generate404(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->default404 !== null) {
            if (\is_callable($this->default404)) {
                return \call_user_func($this->default404, $request, [$this, 'handle']);
            }

            if ($this->default404 instanceof MiddlewareInterface) {
                return $this->default404->process($request, $this);
            }

            if (\is_string($this->default404)) {
                $default404Instance = (new $this->default404());
                if (\method_exists($default404Instance, 'process')) {
                    return $default404Instance->process($request, $this);
                }
            }

            throw new RouterException('The default404 is invalid');
        }

        throw new RouterException('No route found to dispatch');
    }

    protected function pushMiddlewaresToApplyInPipe(): void
    {
        $this->currentMiddlewareInPipeIndex = 0;
        $this->middlewaresInPipe = \array_merge($this->middlewaresInPipe, $this->globalMiddlewares);
        $this->middlewaresInPipe = \array_merge($this->middlewaresInPipe, $this->currentRoute->getMiddlewares());
        $this->middlewaresInPipe[] = $this->currentRoute->getCallback();
    }

    /** @throws RouterException */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middlewaresInPipe[$this->currentMiddlewareInPipeIndex])) {
            return $this->generate404($request);
        }

        $middleware = $this->getMiddlewareInPipe();
        if (\is_callable($middleware)) {
            return $middleware($request, [$this, 'handle']);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        }

        if (\is_string($middleware)) {
            $middlewareInstance = (new $middleware());
            if (\method_exists($middlewareInstance, 'process')) {
                return $middlewareInstance->process($request, $this);
            }
        } elseif ($middleware instanceof self) {
            if ($middleware->findRouteRequest($request)) {
                return $middleware->dispatch($request);
            }

            if ($middleware->default404 !== null) {
                return $middleware->dispatch($request);
            }

            return $this->handle($request);
        }

        throw new RouterException(\sprintf('Middleware is invalid: %s', \gettype($middleware)));
    }

    protected function getMiddlewareInPipe(): \Closure|MiddlewareInterface|self|string|null
    {
        $middleware = null;

        if (\array_key_exists($this->currentMiddlewareInPipeIndex, $this->middlewaresInPipe)) {
            $middleware = $this->middlewaresInPipe[$this->currentMiddlewareInPipeIndex];
            ++$this->currentMiddlewareInPipeIndex;
        }

        return $middleware;
    }

    public function addGlobalMiddleware(\Closure|MiddlewareInterface|self|string $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    public function setGlobalMiddlewares(array $middlewares): void
    {
        $this->globalMiddlewares = $middlewares;
    }

    /** @throws RouterException */
    public function setupRouterAndRoutesWithConfigArray(array $config): void
    {
        $this->treatRouterConfig($config);
        $this->treatRoutesConfig($config);
    }

    /** @throws RouterException */
    protected function treatRouterConfig(array $config): void
    {
        if (!\array_key_exists('router', $config)) {
            return;
        }

        if (!\is_array($config['router'])) {
            throw new RouterException('Config router has to be an array');
        }

        if (\array_key_exists('middlewares', $config['router'])) {
            if (!\is_array($config['router']['middlewares'])) {
                throw new RouterException('Config router/middlewares has to be an array');
            }

            $this->setGlobalMiddlewares($config['router']['middlewares']);
        }

        if (\array_key_exists('constraints', $config['router'])) {
            if (!\is_array($config['router']['constraints'])) {
                throw new RouterException('Config router/constraints has to be an array');
            }

            $this->setGlobalParametersConstraints($config['router']['constraints']);
        }

        if (\array_key_exists('host', $config['router'])) {
            if (!\is_string($config['router']['host'])) {
                throw new RouterException('Config router/host has to be a string');
            }

            $this->setGlobalHost($config['router']['host']);
        }

        if (\array_key_exists('host_constraints', $config['router'])) {
            if (!\is_array($config['router']['host_constraints'])) {
                throw new RouterException('Config router/host_constraints has to be an array');
            }

            $this->setGlobalHostConstraints($config['router']['host_constraints']);
        }

        if (\array_key_exists('default_404', $config['router'])) {
            $this->setDefault404($config['router']['default_404']);
        }
    }

    /** @throws RouterException */
    protected function treatRoutesConfig(array $config): void
    {
        if (!\array_key_exists('routes', $config)) {
            return;
        }

        if (!\is_array($config['routes'])) {
            throw new RouterException('Config routes has to be an array');
        }

        foreach ($config['routes'] as $route) {
            if (!\array_key_exists('methods', $route)) {
                throw new RouterException('Config routes/methods is mandatory');
            }

            if (!\array_key_exists('url', $route)) {
                throw new RouterException('Config routes/url is mandatory');
            }

            if (!\array_key_exists('callback', $route)) {
                throw new RouterException('Config routes/callback is mandatory');
            }

            $newRoute = new Route($route['methods'], $route['url'], $route['callback']);

            if (\array_key_exists('constraints', $route)) {
                $newRoute->setParametersConstraints($route['constraints']);
            }

            if (\array_key_exists('middlewares', $route)) {
                if (!\is_array($route['middlewares'])) {
                    throw new RouterException('Config routes/middlewares has to be an array');
                }

                foreach ($route['middlewares'] as $middleware) {
                    $newRoute->addMiddleware($middleware);
                }
            }

            if (\array_key_exists('name', $route)) {
                if (!\is_string($route['name'])) {
                    throw new RouterException('Config routes/name has to be a string');
                }

                $newRoute->setName($route['name']);
            }

            if (\array_key_exists('host', $route)) {
                if (!\is_string($route['host'])) {
                    throw new RouterException('Config routes/host has to be a string');
                }

                $newRoute->setHost($route['host']);
            }

            if (\array_key_exists('host_constraints', $route)) {
                if (!\is_array($route['host_constraints'])) {
                    throw new RouterException('Config routes/host_constraints has to be an array');
                }

                $newRoute->setHostConstraints($route['host_constraints']);
            }

            if (\array_key_exists('optionals_parameters', $route)) {
                if (!\is_array($route['optionals_parameters'])) {
                    throw new RouterException('Config routes/optionals_parameters has to be an array');
                }

                $newRoute->setOptionalsParameters($route['optionals_parameters']);
            }

            $this->addRoute($newRoute);
        }
    }

    public function setGlobalParametersConstraints(array $constraints): void
    {
        $this->globalConstraints = $constraints;
    }

    public function generateUrl(string $routeName, array $routeParameters = []): ?string
    {
        foreach ($this->routes as $route) {
            if ($route->getCallback() instanceof self) {
                $url = $route->getCallback()->generateUrl($routeName, $routeParameters);
                if ($url !== null) {
                    return $url;
                }
            } elseif ($route->getName() === $routeName) {
                return $route->generateUrl($routeParameters);
            }
        }

        return null;
    }

    public function setGlobalHostConstraints(array $constraints): void
    {
        $this->hostConstraints = $constraints;
    }

    public function setGlobalHost(string $host): void
    {
        $this->hostRouter = $host;
    }

    public function setDefault404(\Closure|MiddlewareInterface|string $callback): void
    {
        $this->default404 = $callback;
    }
}
