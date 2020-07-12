# Router Package

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rancoud/router)
[![Packagist Version](https://img.shields.io/packagist/v/rancoud/router)](https://packagist.org/packages/rancoud/router)
[![Packagist Downloads](https://img.shields.io/packagist/dt/rancoud/router)](https://packagist.org/packages/rancoud/router)
[![Composer dependencies](https://img.shields.io/badge/dependencies-1-brightgreen)](https://github.com/rancoud/router/blob/master/composer.json)
[![Test workflow](https://img.shields.io/github/workflow/status/rancoud/router/test?label=test&logo=github)](https://github.com/rancoud/router/actions?workflow=test)
[![Codecov](https://img.shields.io/codecov/c/github/rancoud/router?logo=codecov)](https://codecov.io/gh/rancoud/router)
[![composer.lock](https://poser.pugx.org/rancoud/router/composerlock)](https://packagist.org/packages/rancoud/router)

Router PSR7 and PSR15.  

## Installation
```php
composer require rancoud/router
```

## Dependencies
[Http package](https://github.com/rancoud/Http)

## How to use it?
### General Case
```php
// Instantiation
$router = new Router();

// Add routes
$router->get('/posts', function ($request, $next) {
    return (new MessageFactory())->createResponse(200, null, [], 'ok');
});

// Find route
$found = $router->findRoute('GET', '/posts');

// You can use PSR-7 request for finding Route
$found = $router->findRouteRequest(new \Rancoud\Http\Message\ServerRequest('GET', '/posts'));

// Dispatch (response is a PSR7 object \Psr\Http\Message\Response)
$response = $router->dispatch($request);

// Display Response
$response->send();
```

### Routes shortcuts
```php
// Methods shortcuts
$router->get('/posts/{id}', function ($request, $next) {});
$router->post('/posts/{id}', function ($request, $next) {});
$router->put('/posts/{id}', function ($request, $next) {});
$router->patch('/posts/{id}', function ($request, $next) {});
$router->delete('/posts/{id}', function ($request, $next) {});
$router->options('/posts/{id}', function ($request, $next) {});

// Any methods
$router->any('/posts/{id}', function ($request, $next) {});

// CRUD method
$router->crud('/posts', function ($request, $next) {});
```
### Route Parameters
Use the pattern `{name}` for naming your parameters  
```php
$router->get('/posts/{id}', function ($request, $next) {});
```

### Constraints
Use regex syntax for your constraints
```php
// inline for simple case
$router->get('/{id:\d+}', function ($request, $next) {});

// complex
$route = new Route('GET', '/{id}', function ($request, $next) {});
$route->setParametersConstraints(['id' => '\d+']);
```

You can setup a global constraint when you use the same regex multiple times  
```php
$router->setGlobalParametersConstraints(['lang' => 'en|fr']);

// {lang} will use the global constraints
$router->get('/article/{lang}', function ($request, $next) {});

// {lang} will use the local constraints define by the route
$router->get('/news/{lang}', function ($request, $next) {})->setParametersConstraints(['lang' => 'jp']);
```

You can use on each route an optional parameters.  
The parameters `{page}` will be replace with the value `1` if it is not present  
```php
$route = new Route('GET', '/{id}/{page}', function ($request, $next) {});
$route->setOptionalsParameters(['page' => 1]);
```

### Middlewares
```php
// global middleware for router
$router->addGlobalMiddleware(function ($request, $next) {});

// middleware for only route
$route = new Route('GET', '/{id}', function ($request, $next) {});
$route->addMiddleware(function ($request, $next) {});

// for passing to next middleware
$router->addGlobalMiddleware(function ($request, $next) {
    $next($request);
});

// you can add an instance of Router as a middleware
$subRouter1 = new Router();
$subRouter1->any('/api/books/{id}', function ($req, $next){
    return (new MessageFactory())->createResponse(200, null, [], 'testRouterception books');
});

$subRouter2 = new Router();
$subRouter2->any('/api/peoples/{id}', function ($req, $next){
    return (new MessageFactory())->createResponse(200, null, [], 'testRouterception peoples');
});

$router->addGlobalMiddleware($subRouter1);
$router->addGlobalMiddleware($subRouter2);
```

#### Router in Route in Router
```php
// you can add an instance of Router in a Route callback
$subRouter1 = new Router();
$subRouter1->any('/api/books/{id}', function ($req, $next){
    return (new MessageFactory())->createResponse(200, null, [], 'testRouterception books');
});

$subRouter2 = new Router();
$subRouter2->any('/api/peoples/{id}', function ($req, $next){
    return (new MessageFactory())->createResponse(200, null, [], 'testRouterception peoples');
});

$router->any('/api/books/{id}', $subRouter1);
$router->any('/api/peoples/{id}', $subRouter2);
```

#### Default 404
```php
// you can set a default 404
$router->setDefault404( static function ($request, $next) {
    return (new \Rancoud\Http\Message\Factory\Factory())->createResponse(404, '')->withBody(Rancoud\Http\Message\Stream::create('404 content'));
});

// it will return false because no Route is matching
$found = $router->findRoute('GET', '/posts');

// response contains the default 404 callback
$response = $router->dispatch($request);

$response->send();
```
**WARNING with 404 and Router as Middleware**  
```php
// create new Router with default 404
$subRouter = new Router();
$subRouter->any('/posts/{id:\d+}', function ($req, $next){
   return (new MessageFactory())->createResponse(200, null, [], 'read 1 post');
});
$subRouter->setDefault404( static function ($request, $next) {
    return (new \Rancoud\Http\Message\Factory\Factory())->createResponse(404, '')->withBody(Rancoud\Http\Message\Stream::create('404 content from subRouter'));
});

// you can add a Router as middleware 
$router->any('/posts/{id}', $subRouter);

// it will return true because Router middleware is matching on /posts/{id}
$found = $router->findRoute('GET', '/posts/incorrect');

// response contains the default 404 callback from $subRouter
$response = $router->dispatch($request);

$response->send();
```
If you use Router as middleware **AND** set default 404 then `findRouteRequest` and `findRoute` will return `true`.  
When calling `dispatch` it will use the callback setted as default 404 by the Router middleware.    

## Router Methods
### General Commands  
#### Add route
* addRoute(route: \Rancoud\Router\Route): void  

#### Add route shortcuts
* get(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* post(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* put(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* patch(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* delete(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* options(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): \Rancoud\Router\Route  
* any(url: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): void  

#### Add route for a CRUD system
* crud(prefixPath: string, callback: string|\Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router): void  

It will create all this routes:  
GET  $prefixPath  
GET / POST  $prefixPath . '/new'  
GET / POST / DELETE $prefixPath . '/{id:\d+}'  

#### Setup Router and Routes with an array
* setupRouterAndRoutesWithConfigArray(config: array): void  

In this example you can setup router's middlewares and routes with an array  
```php
$config = [
    'router' => [
        'middlewares' => [
            'global_callback1',
            'global_callback2',
            'global_callback3'
        ],
        'constraints' => [
            'lang' => 'en|fr'
        ],
        'host' => '{service}.domain.{tld}',
        'host_constraint' => [
            'service' => 'api|backoffice|www|m',
            'tld' => 'en|jp'
        ],
        'default_404' => 'callable_404'
    ],
    'routes' => [
        [
            'methods' => ['GET'],
            'url' => '/articles/{id}',
            'callback' => 'route_callback',
            'constraints' => ['id' => '\w+'],
            'middlewares' => ['route_middleware1', 'route_middleware2'],
            'name' => 'route1'
        ],
        [
            'methods' => ['POST'],
            'url' => '/form',
            'callback' => 'callback',
        ],
        [
            'methods' => ['POST'],
            'url' => '/api/form',
            'callback' => 'callback',
            'host' => 'api.domain.{tld}',
            'host_constraint' => [
                'tld' => 'en|jp'
            ]
        ],
        [
            'methods' => ['GET'],
            'url' => '/blog/{page}',
            'callback' => 'callback',
            'optionals_parameters' => [
                'page' => '1'
            ]
        ]
    ]
];

$router = new Router();
$router->setupRouterAndRoutesWithConfigArray($config);

// you can add a Router as a callback
$subRoute = new Router();
$subRoute->setupRouterAndRoutesWithConfigArray($config);
$router->any('/(.*)', $subRoute);
```

#### Get Routes
* getRoutes(): \Rancoud\Router\Route[]  

#### Find route
* findRoute(method: string, url: string, [host: string = null]): bool  
* findRouteRequest(request: \Psr\Http\Message\ServerRequestInterface): bool  
* getRouteParameters(): array  

#### Run the found route 
* dispatch(request: \Psr\Http\Message\ServerRequestInterface): \Psr\Http\Message\Response  
* handle(request: \Psr\Http\Message\ServerRequestInterface): \Psr\Http\Message\Response  

The difference between dispatch and handle is dispatch is used in first place.  
Handle is from the PSR17 in Psr\Http\Message\ServerRequestInterface, it's useful for middleware.  

#### Middlewares
* addGlobalMiddleware(middleware: \Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router|string): void  
* setGlobalMiddlewares(middlewares: array): void  

#### Global constraints
* setGlobalParametersConstraints(constraints: array): void  
* setGlobalHostConstraints(constraints: array): void  

#### Generate url for a named route
* generateUrl(routeName: string, [routeParameters: array = []]): ?string  

#### Host constraints
* setGlobalHost(host: string): void  

#### Default 404
* setDefault404(callback: mixed): void  

## Route Constructor
### Settings
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| methods | string \| array | methods matching with the route |
| url | string | url to match |
| callback | string \| Closure \| \Psr\Http\Server\MiddlewareInterface \| \Rancoud\Router\Router | callback when route is calling by router |

## Route Methods
### General Commands  
#### Getters/Setters
* getMethods(): array  
* getUrl(): string  
* getName(): string  
* setName(name: string): void  

#### Constraints
* setParametersConstraints(constraints: array): void  
* getParametersConstraints(): array  
* compileRegex(globalConstraints: array): string  
* setOptionalsParameters(optionalsParameters: array): void  
* getOptionalsParameters(): array  

#### Callback
* getCallback(): mixed  

#### Middlewares
* addMiddleware(middleware: \Closure|\Psr\Http\Server\MiddlewareInterface|\Rancoud\Router\Router|string): array  
* getMiddlewares(): array  

#### Generate Url
* generateUrl([routeParameters: array = []]): string  

#### Host
* getHost(): ?string  
* setHost(host: string, [hostConstraints: array = []]): void  
* setHostConstraints(constraints: array): void  
* isSameHost(host: string, globalConstraints: array = []): bool  
* getHostParameters(): array  

## How to Dev
`composer ci` for php-cs-fixer and phpunit and coverage  
`composer lint` for php-cs-fixer  
`composer test` for phpunit and coverage  