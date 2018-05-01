<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rancoud\Http\Message\Factory\MessageFactory;
use Rancoud\Http\Message\Factory\ServerRequestFactory;
use Rancoud\Http\Message\Response;
use Rancoud\Router\Route;
use Rancoud\Router\Router;
use Rancoud\Router\RouterException;
use ReflectionClass;

/**
 * Class RouterTest.
 */
class RouterTest extends TestCase
{
    /** @var Router */
    protected $router;

    public function setUp()
    {
        $this->router = new Router();
    }

    public function testAddRoute()
    {
        $this->router->addRoute(new Route('GET', '/', function () {
        }));
        static::assertSame(1, count($this->router->getRoutes()));
    }

    public function testShortcutGet()
    {
        $this->router->get('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }

    public function testShortcutGetFluent()
    {
        $this->router->get('/', function () {
        })->setName('route a');
        
        $routes = $this->router->getRoutes();
        static::assertSame(1, count($routes));
        static::assertSame('route a', $routes[0]->getName());
    }

    public function testShortcutPost()
    {
        $this->router->post('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testShortcutPut()
    {
        $this->router->put('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testShortcutPatch()
    {
        $this->router->patch('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testShortcutDelete()
    {
        $this->router->delete('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testShortcutOptions()
    {
        $this->router->options('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testShortcutAny()
    {
        $this->router->any('/', function () {
        });
        static::assertSame(1, count($this->router->getRoutes()));
    }
    
    public function testFindRoute()
    {
        $this->router->get('/', function () {
        });
        $found = $this->router->findRoute('GET', '/');
        static::assertTrue($found);
    }
    
    public function testFindRouteWithQSA()
    {
        $this->router->get('/', function () {
        });
        $this->router->post('/', function () {
        });
        $found = $this->router->findRoute('POST', '/?qsa=asq');
        static::assertTrue($found);
    }

    public function testFindRouteUri()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/azerty?az=9');

        $this->router->get('/azerty', function () {
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
    }
    
    public function testNotFindRoute()
    {
        $this->router->get('/', function () {
        });
        $found = $this->router->findRoute('GET', '/aze');
        static::assertFalse($found);
    }

    public function testFindAllCrudRoute()
    {
        $this->router->crud('/posts', function () {
        });
        $found = $this->router->findRoute('GET', '/posts');
        static::assertTrue($found);
        $found = $this->router->findRoute('GET', '/posts/new');
        static::assertTrue($found);
        $found = $this->router->findRoute('POST', '/posts/new');
        static::assertTrue($found);
        $found = $this->router->findRoute('GET', '/posts/1');
        static::assertTrue($found);
        $found = $this->router->findRoute('POST', '/posts/1');
        static::assertTrue($found);
        $found = $this->router->findRoute('DELETE', '/posts/1');
        static::assertTrue($found);
    }
    
    public function testFindRouteWithParameters()
    {
        $this->router->get('/{id}', function () {
        });
        $found = $this->router->findRoute('GET', '/aze');
        static::assertTrue($found);
        $parameters = $this->router->getRouteParameters();
        static::assertTrue(array_key_exists('id', $parameters));
        static::assertSame('aze', $parameters['id']);
    }
    
    public function testFindRouteWithParametersAndSimpleRegexOnIt()
    {
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/fr/1990/myslug');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('fr', $parameters['locale']);
        static::assertSame('1990', $parameters['year']);
        static::assertSame('myslug', $parameters['slug']);
    }

    public function testFindRouteWithParametersAndSimpleRegexOnItNotFound()
    {
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/fra/1990/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/199/myslug');
        static::assertFalse($found);
    }
    
    public function testFindRouteWithParametersAndComplexRegexOnIt()
    {
        $ipRegex = '\b(?:(?:25[0-5]|2[0-4][0-9]|1?[1-9][0-9]?|10[0-9])(?:(?<!\.)\b|\.))';
        $ipRegex .= '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:(?<!\.)\b|\.)){3}';
        $route = new Route('GET', '/articles/{ip}/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints([
            'ip'     => $ipRegex,
            'locale' => 'fr|en',
            'year'   => '\d{4}'
        ]);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/192.168.1.1/en/2004/myotherslug?qsa=asq');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertTrue(array_key_exists('ip', $parameters));
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('192.168.1.1', $parameters['ip']);
        static::assertSame('en', $parameters['locale']);
        static::assertSame('2004', $parameters['year']);
        static::assertSame('myotherslug', $parameters['slug']);
    }
    
    public function testHandleWithClosureMatch()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme');
        $request = $request->withAttribute('attr', 'src');

        $this->router->get('/handleme', function ($req, $next) {
            static::assertEquals('src', $req->getAttribute('attr'));
            static::assertTrue($next[0] instanceof Router);
            static::assertEquals('handle', $next[1]);

            return (new MessageFactory())->createResponse(200, null, [], 'ok');
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);

        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testHandleWithClosureNext()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme');
        $this->router->get('/handleme', function ($request, $next) {
            return $next($request);
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('', $response->getBody());
    }

    public function testHandleWithMiddleware()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme');
        $middleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $response = (new MessageFactory())->createResponse(200, null, [], 'ok');
        $middleware->method('process')->willReturn($response);
        $this->router->get('/handleme', $middleware);
        $middleware->expects($this->once())->method('process');
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testHandleWithString()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme/src/8');
        $this->router->get('/handleme/{attr}/{id}', function ($request, $next) {
            static::assertEquals('src', $request->getAttribute('attr'));
            static::assertEquals('8', $request->getAttribute('id'));

            return (new MessageFactory())->createResponse(200, null, [], 'ok');
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testHandleWithClosureAndAttributeInRequestExtractedFromRoute()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme');
        $this->router->get('/handleme', ExampleMiddleware::class);
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testAddGlobalMiddleware()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme/src/8');
        $this->router->addGlobalMiddleware(function ($request, $next) {
            static::assertEquals('src', $request->getAttribute('attr'));
            $request = $request->withAttribute('global', 'middleware');
            
            return $next($request);
        });
        $this->router->addGlobalMiddleware(function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            $request = $request->withAttribute('global2', 'middleware2');

            return $next($request);
        });
        $this->router->get('/handleme/{attr}/{id}', function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));

            return (new MessageFactory())->createResponse(200, null, [], 'ok');
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testAddGlobalMiddlewareAndRouteAddMiddleware()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/handleme/src/8');
        
        $this->router->addGlobalMiddleware(function ($request, $next) {
            static::assertEquals('src', $request->getAttribute('attr'));
            $request = $request->withAttribute('global', 'middleware');

            return $next($request);
        });
        
        $this->router->addGlobalMiddleware(function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            $request = $request->withAttribute('global2', 'middleware2');

            return $next($request);
        });
        
        $route = new Route('GET', '/handleme/{attr}/{id}', function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));

            static::assertEquals('r_middleware', $request->getAttribute('route'));
            static::assertEquals('r_middleware2', $request->getAttribute('route2'));
            
            return (new MessageFactory())->createResponse(200, null, [], 'ok');
        });
        
        $route->addMiddleware(function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));
            $request = $request->withAttribute('route', 'r_middleware');

            return $next($request);
        });
        
        $route->addMiddleware(function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));
            static::assertEquals('r_middleware', $request->getAttribute('route'));

            $request = $request->withAttribute('route2', 'r_middleware2');
            
            return $next($request);
        });
        
        $this->router->addRoute($route);
        
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testSetupRouterAndRoutesWithConfigArray()
    {
        $config = [
            'router' => [
                'middlewares' => [
                    'callback1',
                    'callback2',
                    'callback3'
                ],
            ],
            'routes' => [
                [
                    'methods' => ['GET'],
                    'url' => '/{id}',
                    'callback' => 'callback',
                    'constraints' => ['id' => '\w+'],
                    'middlewares' => ['a', 'b'],
                    'name' => 'route1'
                ],
                [
                    'methods' => ['POST'],
                    'url' => '/aze',
                    'callback' => 'callback',
                ]
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        $routes = $this->router->getRoutes();
        static::assertTrue(count($routes) === 2);

        $router = new ReflectionClass($this->router);
        $property = $router->getProperty('globalMiddlewares');
        $property->setAccessible(true);
        
        static::assertEquals($config['router']['middlewares'], $property->getValue($this->router));

        static::assertEquals($config['routes'][0]['methods'], $routes[0]->getMethods());
        static::assertEquals($config['routes'][0]['middlewares'], $routes[0]->getMiddlewares());
        static::assertEquals($config['routes'][0]['callback'], $routes[0]->getCallback());
        static::assertEquals($config['routes'][0]['name'], $routes[0]->getName());
        static::assertEquals($config['routes'][0]['url'], $routes[0]->getUrl());
        static::assertEquals($config['routes'][0]['constraints'], $routes[0]->getParametersConstraints());

        static::assertEquals($config['routes'][1]['methods'], $routes[1]->getMethods());
        static::assertEquals([], $routes[1]->getMiddlewares());
        static::assertEquals($config['routes'][1]['callback'], $routes[1]->getCallback());
        static::assertEquals(null, $routes[1]->getName());
        static::assertEquals($config['routes'][1]['url'], $routes[1]->getUrl());
        static::assertEquals([], $routes[1]->getParametersConstraints());
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoRouterPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['GET'],
                    'url' => '/{id}',
                    'callback' => 'callback',
                    'constraints' => ['id' => '\w+'],
                    'middlewares' => ['a', 'b'],
                    'name' => 'route1'
                ],
                [
                    'methods' => ['POST'],
                    'url' => '/aze',
                    'callback' => 'callback',
                ]
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        $routes = $this->router->getRoutes();
        static::assertTrue(count($routes) === 2);

        $router = new ReflectionClass($this->router);
        $property = $router->getProperty('globalMiddlewares');
        $property->setAccessible(true);

        static::assertEquals([], $property->getValue($this->router));
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoMiddlewareInRouterPart()
    {
        $config = [
            'router' => null
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router has to be an array');
        
        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoMiddlewareValidInRouterPart()
    {
        $config = [
            'router' => ['middlewares' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/middlewares has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoValidRoutes()
    {
        $config = [
            'routes' => null
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoMethodsInRoutesPart()
    {
        $config = [
            'routes' => [
                []
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/methods is mandatory');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoUrlInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST']
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/url is mandatory');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoCallbackInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/'
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/callback is mandatory');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidMiddlewaresInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/',
                    'callback' => 'a',
                    'middlewares' => null
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/middlewares has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoRoutesPart()
    {
        $config = [];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        $routes = $this->router->getRoutes();
        static::assertTrue(count($routes) === 0);
    }
}
class ExampleMiddleware implements MiddlewareInterface{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return (new MessageFactory())->createResponse(200, null, [], 'ok');
    }
}