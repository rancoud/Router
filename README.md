# Router Package

[![Build Status](https://travis-ci.org/rancoud/Router.svg?branch=master)](https://travis-ci.org/rancoud/Router) [![Coverage Status](https://coveralls.io/repos/github/rancoud/Router/badge.svg?branch=master)](https://coveralls.io/github/rancoud/Router?branch=master)

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
```

## Router Methods
### General Commands  
#### Add route
* addRoute(route: \Rancoud\Router\Route):void  

#### Add route shortcuts
* get(url: string, callback: mixed):\Rancoud\Router\Route  
* post(url: string, callback: mixed):\Rancoud\Router\Route  
* put(url: string, callback: mixed):\Rancoud\Router\Route  
* patch(url: string, callback: mixed):\Rancoud\Router\Route  
* delete(url: string, callback: mixed):\Rancoud\Router\Route  
* options(url: string, callback: mixed):\Rancoud\Router\Route  
* any(url: string, callback: mixed):void  

#### Add route for a CRUD system
* crud(prefixPath: string, callback: mixed):void  

It will create all this routes:  
GET  $prefixPath  
GET / POST  $prefixPath . '/new'  
GET / POST / DELETE $prefixPath . '/{id:\d+}'  

#### Setup Router and Routes with an array
* setupRouterAndRoutesWithConfigArray(config: array):void  

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
        ]
    ],
    'routes' => [
        [
            'methods' => ['GET'],
            'url' => '/{id}',
            'callback' => 'route_callback',
            'constraints' => ['id' => '\w+'],
            'middlewares' => ['route_middleware1', 'route_middleware2'],
            'name' => 'route1'
        ],
        [
            'methods' => ['POST'],
            'url' => '/aze',
            'callback' => 'callback',
        ]
    ]
];

$router = new Router();
$router->setupRouterAndRoutesWithConfigArray($config);
```

#### Get Routes
* getRoutes():\Rancoud\Router\Route[]  

#### Find route
* findRoute(method: string, url: string, [host: string=null]):bool  
* findRouteRequest(request: \Psr\Http\Message\ServerRequestInterface):bool  
* getRouteParameters():array  

#### Run the found route 
* dispatch(request: \Psr\Http\Message\ServerRequestInterface):\Psr\Http\Message\Response  
* handle(request: \Psr\Http\Message\ServerRequestInterface):\Psr\Http\Message\Response  

The difference between dispatch and handle is dispatch is used in first place.  
Handle is from the PSR17 in Psr\Http\Message\ServerRequestInterface, it's useful for middleware.  

#### Middlewares
* addGlobalMiddleware(middleware: mixed):void  
* setGlobalMiddlewares(middlewares: array):void  

#### Global constraints
* setGlobalParametersConstraints(constraints: array): void  

#### Generate url for a named route
* generateUrl(route: string, [routeParameters: array = []])  

## Route Constructor
### Settings
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| methods | string OR array | methods matching with the route |
| url | string | url to match |
| callback | string OR closure OR MiddlewareInterface | callback when route is calling by router |

## Route Methods
### General Commands  
#### Getters
* getMethods():array  
* getUrl():string  
* getName():string  
#### Constraints
* setParametersConstraints(constraints: array):void  
* getParametersConstraints():array  
* compileRegex():string  
#### Callback
* getCallback():mixed  
#### Middlewares
* addMiddleware(middleware: mixed):array  
* getMiddlewares():array  
#### Generate Url
* generateUrl([routeParameters: array = []])  

## How to Dev
`./run_all_commands.sh` for php-cs-fixer and phpunit and coverage  
`./run_php_unit_coverage.sh` for phpunit and coverage  