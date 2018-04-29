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
// Instanciation
$router = new Router();

// Add routes
$router->get('/posts', function ($request, $next) {
    return (new MessageFactory())->createResponse(200, null, [], 'ok');
});

// Find route
$found = $router->findRoute('GET', '/posts');

// Dispatch (response is a PSR7 object \Psr\Http\Message\Response)
$response = $router->dispatch($request);
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
* get(url: string, callback: mixed):void  
* post(url: string, callback: mixed):void  
* put(url: string, callback: mixed):void  
* patch(url: string, callback: mixed):void  
* delete(url: string, callback: mixed):void  
* options(url: string, callback: mixed):void  
* any(url: string, callback: mixed):void  

#### Add route for a CRUD system
* crud(prefixPath: string, callback: mixed):void  

It will create all this routes:  
GET  $prefixPath  
GET / POST  $prefixPath . '/new'  
GET / POST / DELETE $prefixPath . '/{id:\d+}'  

#### Get Routes
* getRoutes():\Rancoud\Router\Route[]  

#### Find route
* findRoute(method: string, url: string):bool  
* findRouteRequest(request: \Psr\Http\Message\ServerRequestInterface):bool  
* getRouteParameters():array  

#### Run the found route 
* dispatch(request: \Psr\Http\Message\ServerRequestInterface):\Psr\Http\Message\Response  
* handle(request: \Psr\Http\Message\ServerRequestInterface):\Psr\Http\Message\Response  

The difference between dispatch and handle is dispatch is used in first place.  
Handle is from the PSR17 in Psr\Http\Message\ServerRequestInterface, it's useful for middleware.  

#### Middlewares
* addGlobalMiddleware(middleware: mixed):void  

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
#### Constraints
* setParametersConstraints(constraints: array):void  
* compileRegex():string  
#### Callback
* getCallback():mixed  
#### Middlewares
* addMiddleware(middleware: mixed):array  
* getMiddlewares():array  

## How to Dev
`./run_all_commands.sh` for php-cs-fixer and phpunit and coverage  
`./run_php_unit_coverage.sh` for phpunit and coverage  