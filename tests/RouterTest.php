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
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\ServerRequest;
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

        $found = $this->router->findRoute('GET', '/articles/fr/190/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/mmmm/myslug');
        static::assertFalse($found);
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

        $found = $this->router->findRoute('GET', '/articles/192.1/en/2004/myotherslug?qsa=asq');
        static::assertFalse($found);
    }

    public function testFindRouteWithParametersAndSimpleInlineRegex()
    {
        $route = new Route('GET', '/articles/{locale:fr|jp}/{slug}', null);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/en/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/myslug');
        static::assertTrue($found);
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
                'constraints' => [
                    'lang' => 'fr|en'
                ]
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

        $property = $router->getProperty('globalConstraints');
        $property->setAccessible(true);
        static::assertEquals($config['router']['constraints'], $property->getValue($this->router));

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

    public function testSetupRouterAndRoutesWithConfigArrayNoConstraintValidInRouterPart()
    {
        $config = [
            'router' => ['constraints' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/constraints has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoHostConstraintValidInRouterPart()
    {
        $config = [
            'router' => ['host_constraints' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/host_constraints has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoHostValidInRouterPart()
    {
        $config = [
            'router' => ['host' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/host has to be a string');

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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidHostInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/',
                    'callback' => 'a',
                    'host' => null
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/host has to be a string');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidHostConstraintsInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/',
                    'callback' => 'a',
                    'host_constraints' => null
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/host_constraints has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidNameInRoutesPart()
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/',
                    'callback' => 'a',
                    'name' => null
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/name has to be a string');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }
    
    public function testSetupRouterAndRoutesWithConfigArrayNoRoutesPart()
    {
        $config = [];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        $routes = $this->router->getRoutes();
        static::assertTrue(count($routes) === 0);
    }

    public function testSetGlobalConstraints()
    {
        $request1Found = (new ServerRequestFactory())->createServerRequest('GET', '/article/fr');
        $request2Found = (new ServerRequestFactory())->createServerRequest('GET', '/article_bis/jp');

        $request1NotFound = (new ServerRequestFactory())->createServerRequest('GET', '/article/kx');
        $request2NotFound = (new ServerRequestFactory())->createServerRequest('GET', '/article_bis/m');

        $this->router->setGlobalParametersConstraints(['lang' => 'en|fr']);
        $this->router->get('/article/{lang}', 'a');
        $this->router->get('/article_bis/{lang}', 'b')->setParametersConstraints(['lang' => 'jp']);

        static::assertTrue($this->router->findRouteRequest($request1Found));
        static::assertFalse($this->router->findRouteRequest($request1NotFound));

        static::assertTrue($this->router->findRouteRequest($request2Found));
        static::assertFalse($this->router->findRouteRequest($request2NotFound));
    }

    public function testGenerateUrl()
    {
        $config = [
            'router' => [
                'constraints' => [
                    'lang' => 'fr|en'
                ]
            ],
            'routes' => [
                [
                    'methods' => ['GET'],
                    'url' => '/road',
                    'callback' => 'callback',
                    'constraints' => ['id' => '\w+'],
                    'name' => 'route0'
                ],
                [
                    'methods' => ['GET'],
                    'url' => '/{lang}-{id}',
                    'callback' => 'callback',
                    'constraints' => ['id' => '\w+'],
                    'name' => 'route1'
                ],
                [
                    'methods' => ['GET'],
                    'url' => '/{id:\w+}/pagename',
                    'callback' => 'callback',
                    'name' => 'route2'
                ],
                [
                    'methods' => ['GET'],
                    'url' => '/{lang}/postname',
                    'callback' => 'callback',
                    'constraints' => ['lang' => 'jp'],
                    'name' => 'route3'
                ],
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);

        $urls = [];
        $urls[] = $this->router->generateUrl('route0');
        $urls[] = $this->router->generateUrl('route1', ['lang' => 'fr', 'id' => '2']);
        $urls[] = $this->router->generateUrl('route2', ['id' => '12']);
        $urls[] = $this->router->generateUrl('route3', ['lang' => 'jp']);
        $urls[] = $this->router->generateUrl('route1');
        $urls[] = $this->router->generateUrl('no_route');

        static::assertEquals('/road', $urls[0]);
        static::assertEquals('/fr-2', $urls[1]);
        static::assertEquals('/12/pagename', $urls[2]);
        static::assertEquals('/jp/postname', $urls[3]);
        static::assertEquals('/{lang}-{id}', $urls[4]);
        static::assertNull($urls[5]);
    }

    public function testSetGlobalHostRouter()
    {
        $host = 'api.toto.com';
        $this->router->setGlobalHost($host);
        $route = new Route('GET', '/abc', null);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['SERVER_NAME' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'backoffice.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));
    }
    
    public function testSetHost()
    {
        $host = 'api.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['SERVER_NAME' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
        
        $serverHost = ['HTTP_HOST' => 'backoffice.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));
    }

    public function testSetHostPlaceholder()
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'backoffice.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'backoffice.tata.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'beta.backoffice.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));
    }

    public function testSetHostPlaceholderInlineConstraints()
    {
        $host = '{subdomain:api|backoffice}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'backoffice.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'beta.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));
    }

    public function testSetHostAndConstraints()
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host, ['subdomain' => '\d{4}']);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    public function testSetHostAndConstraints2()
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $route->setHostConstraints(['subdomain' => '\d{4}']);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    public function testSetGlobalHostAndConstraints()
    {
        $this->router->setGlobalHostConstraints(['subdomain' => '\d{4}']);

        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = new ServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    public function testSetGlobalHostAndConstraintsAndGetInfoInRequest()
    {
        $this->router->setGlobalHostConstraints(['subdomain' => '\d{4}']);

        $host = '{subdomain}.{domain}.{tld}';
        $route = new Route('GET', '/abc', function($req, $next){
            static::assertEquals('1990', $req->getAttribute('subdomain'));
            static::assertEquals('toto', $req->getAttribute('domain'));
            static::assertEquals('com', $req->getAttribute('tld'));
            return (new MessageFactory())->createResponse(200, null, [], 'ok');
        });
        $route->setHost($host);
        $this->router->addRoute($route);

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
        $this->router->dispatch($request);
    }

    public function testConfigWithHostAndConstraints()
    {
        $config = [
            'router' => [
                'host' => '{subdomain:api|backoffice}.{domain}.{tld}',
                'host_constraints' => [
                    'domain' => '\d{4}'
                ]
            ],
            'routes' => [
                [
                    'methods' => ['GET'],
                    'url' => '/common',
                    'callback' => function($req, $next){
                        static::assertEquals('api', $req->getAttribute('subdomain'));
                        static::assertEquals('2000', $req->getAttribute('domain'));
                        static::assertEquals('com', $req->getAttribute('tld'));
                        return (new MessageFactory())->createResponse(200, null, [], 'ok');
                    },
                    'name' => 'route0',
                ],
                [
                    'methods' => ['GET'],
                    'url' => '/special',
                    'callback' => function($req, $next){
                        static::assertEquals('api', $req->getAttribute('subdomain'));
                        static::assertEquals('2000', $req->getAttribute('domain'));
                        static::assertEquals('com', $req->getAttribute('tld'));
                        return (new MessageFactory())->createResponse(200, null, [], 'ok');
                    },
                    'name' => 'route1',
                    'host' => '{subdomain}.{domain}.{tld}',
                    'host_constraints' => [
                        'subdomain' => 'api',
                        'tld' => 'com'
                    ]
                ]
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);

        $router = new ReflectionClass($this->router);
        $property = $router->getProperty('currentRoute');
        $property->setAccessible(true);
        
        $serverHost = ['HTTP_HOST' => 'backoffice.2000.com'];
        $request = new ServerRequest('GET', '/common', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
        static::assertEquals('route0', $property->getValue($this->router)->getName());

        $serverHost = ['HTTP_HOST' => 'api.2000.com'];
        $request = new ServerRequest('GET', '/special', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
        static::assertEquals('route1', $property->getValue($this->router)->getName());
        $this->router->dispatch($request);

        $serverHost = ['HTTP_HOST' => 'api.2000.fr'];
        $request = new ServerRequest('GET', '/special', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'backoffice.2000.com'];
        $request = new ServerRequest('GET', '/special', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'www.2000.com'];
        $request = new ServerRequest('GET', '/special', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));
    }

    public function testDispatchNoRouteFound()
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('No route found to dispatch');
        
        $request = new ServerRequest('GET', '/');
        $this->router->dispatch($request);
    }

    public function testDispatch404()
    {
        $request = new ServerRequest('GET', '/');
        $this->router->setDefault404(function($req, $next){
            return (new MessageFactory())->createResponse(404, null, [], '404');
        });
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }
    
    public function testDispatch404Error()
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('The default404 is invalid');

        $this->router->setDefault404(4545);
        $request = new ServerRequest('GET', '/');
        $this->router->dispatch($request);
    }

    public function testHandleWithClosureNext()
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Middleware is invalid: NULL');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $this->router->get('/', null);
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);


        $this->router->dispatch($request);
    }

    public function testDispatch404AfterAllMiddlewarePassed()
    {
        $request = new ServerRequest('GET', '/');
        $this->router->setDefault404(function($req, $next){
            return (new MessageFactory())->createResponse(404, null, [], '404');
        });
        $this->router->get('/', function($req, $next){
            return $next($req);
        });
        $this->router->findRouteRequest($request);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }

    public function testDispatch404Middleware()
    {
        $middleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $response = (new MessageFactory())->createResponse(404, null, [], '404');
        $middleware->method('process')->willReturn($response);
        
        $request = new ServerRequest('GET', '/');
        $this->router->setDefault404($middleware);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }

    public function testDispatch404StringMiddleware()
    {
        $request = new ServerRequest('GET', '/');
        $this->router->setDefault404(ExampleMiddleware::class);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    public function testDispatch404StringMiddlewareWithConfig()
    {
        $config = [
            'router' => [
                'default_404' => ExampleMiddleware::class
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        
        $request = new ServerRequest('GET', '/');
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }
}
class ExampleMiddleware implements MiddlewareInterface{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return (new MessageFactory())->createResponse(200, null, [], 'ok');
    }
}