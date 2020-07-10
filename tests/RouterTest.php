<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\ServerRequest;
use Rancoud\Http\Message\Stream;
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
    protected Router $router;

    public function setUp(): void
    {
        $this->router = new Router();
    }

    /**
     * @throws RouterException
     */
    public function testAddRoute(): void
    {
        $this->router->addRoute(new Route('GET', '/', static function () {
        }));
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutGet(): void
    {
        $this->router->get('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutGetFluent(): void
    {
        $this->router->get('/', static function () {
        })->setName('route a');
        
        $routes = $this->router->getRoutes();
        static::assertCount(1, $routes);
        static::assertSame('route a', $routes[0]->getName());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutPost(): void
    {
        $this->router->post('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutPut(): void
    {
        $this->router->put('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutPatch(): void
    {
        $this->router->patch('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutDelete(): void
    {
        $this->router->delete('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutOptions(): void
    {
        $this->router->options('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testShortcutAny(): void
    {
        $this->router->any('/', static function () {
        });
        static::assertCount(1, $this->router->getRoutes());
    }

    /**
     * @throws RouterException
     */
    public function testFindRoute(): void
    {
        $this->router->get('/', static function () {
        });
        $found = $this->router->findRoute('GET', '/');
        static::assertTrue($found);
    }

    /**
     * @throws RouterException
     */
    public function testFindRouteWithQSA(): void
    {
        $this->router->get('/', static function () {
        });
        $this->router->post('/', static function () {
        });
        $found = $this->router->findRoute('POST', '/?qsa=asq');
        static::assertTrue($found);
    }

    /**
     * @throws RouterException
     */
    public function testFindRouteUri(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/azerty?az=9');

        $this->router->get('/azerty', static function () {
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
    }

    /**
     * @throws RouterException
     */
    public function testNotFindRoute(): void
    {
        $this->router->get('/', static function () {
        });
        $found = $this->router->findRoute('GET', '/aze');
        static::assertFalse($found);
    }

    /**
     * @throws RouterException
     */
    public function testFindAllCrudRoute(): void
    {
        $this->router->crud('/posts', static function () {
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

    /**
     * @throws RouterException
     */
    public function testFindRouteWithParameters(): void
    {
        $this->router->get('/{id}', static function () {
        });
        $found = $this->router->findRoute('GET', '/aze');
        static::assertTrue($found);
        $parameters = $this->router->getRouteParameters();
        static::assertArrayHasKey('id', $parameters);
        static::assertSame('aze', $parameters['id']);
    }
    
    public function testFindRouteWithParametersAndSimpleRegexOnIt(): void
    {
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/fr/1990/myslug');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertArrayHasKey('locale', $parameters);
        static::assertArrayHasKey('year', $parameters);
        static::assertArrayHasKey('slug', $parameters);
        static::assertSame('fr', $parameters['locale']);
        static::assertSame('1990', $parameters['year']);
        static::assertSame('myslug', $parameters['slug']);

        $found = $this->router->findRoute('GET', '/articles/fr/190/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/mmmm/myslug');
        static::assertFalse($found);
    }

    public function testFindRouteWithParametersAndSimpleRegexOnItNotFound(): void
    {
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/fra/1990/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/199/myslug');
        static::assertFalse($found);
    }
    
    public function testFindRouteWithParametersAndComplexRegexOnIt(): void
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
        static::assertArrayHasKey('ip', $parameters);
        static::assertArrayHasKey('locale', $parameters);
        static::assertArrayHasKey('year', $parameters);
        static::assertArrayHasKey('slug', $parameters);
        static::assertSame('192.168.1.1', $parameters['ip']);
        static::assertSame('en', $parameters['locale']);
        static::assertSame('2004', $parameters['year']);
        static::assertSame('myotherslug', $parameters['slug']);

        $found = $this->router->findRoute('GET', '/articles/192.1/en/2004/myotherslug?qsa=asq');
        static::assertFalse($found);
    }

    public function testFindRouteWithParametersAndSimpleInlineRegex(): void
    {
        $route = new Route('GET', '/articles/{locale:fr|jp}/{slug}', null);
        $this->router->addRoute($route);

        $found = $this->router->findRoute('GET', '/articles/en/myslug');
        static::assertFalse($found);

        $found = $this->router->findRoute('GET', '/articles/fr/myslug');
        static::assertTrue($found);
    }

    /**
     * @throws RouterException
     */
    public function testHandleWithClosureMatch(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme');
        $request = $request->withAttribute('attr', 'src');

        $this->router->get('/handleme', static function ($req, $next) {
            static::assertEquals('src', $req->getAttribute('attr'));
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertEquals('handle', $next[1]);

            return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);

        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testHandleWithMiddleware(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme');
        $middleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $response = (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        $middleware->method('process')->willReturn($response);
        $this->router->get('/handleme', $middleware);
        $middleware->expects(static::once())->method('process');
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testHandleWithString(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme/src/8');
        $this->router->get('/handleme/{attr}/{id}', static function ($request, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertEquals('src', $request->getAttribute('attr'));
            static::assertEquals('8', $request->getAttribute('id'));

            return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testHandleWithClosureAndAttributeInRequestExtractedFromRoute(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme');
        $this->router->get('/handleme', ExampleMiddleware::class);
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testAddGlobalMiddleware(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme/src/8');
        $this->router->addGlobalMiddleware(static function ($request, $next) {
            static::assertEquals('src', $request->getAttribute('attr'));
            $request = $request->withAttribute('global', 'middleware');
            
            return $next($request);
        });
        $this->router->addGlobalMiddleware(static function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            $request = $request->withAttribute('global2', 'middleware2');

            return $next($request);
        });
        $this->router->get('/handleme/{attr}/{id}', static function ($request, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));

            return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        });
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testAddGlobalMiddlewareAndRouteAddMiddleware(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/handleme/src/8');
        
        $this->router->addGlobalMiddleware(static function ($request, $next) {
            static::assertEquals('src', $request->getAttribute('attr'));
            $request = $request->withAttribute('global', 'middleware');

            return $next($request);
        });
        
        $this->router->addGlobalMiddleware(static function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            $request = $request->withAttribute('global2', 'middleware2');

            return $next($request);
        });
        
        $route = new Route('GET', '/handleme/{attr}/{id}', static function ($request, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));

            static::assertEquals('r_middleware', $request->getAttribute('route'));
            static::assertEquals('r_middleware2', $request->getAttribute('route2'));
            
            return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        });
        
        $route->addMiddleware(static function ($request, $next) {
            static::assertEquals('middleware', $request->getAttribute('global'));
            static::assertEquals('middleware2', $request->getAttribute('global2'));
            $request = $request->withAttribute('route', 'r_middleware');

            return $next($request);
        });
        
        $route->addMiddleware(static function ($request, $next) {
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

    /**
     * @throws RouterException
     * @throws \ReflectionException
     */
    public function testSetupRouterAndRoutesWithConfigArray(): void
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
        static::assertSame(count($routes), 2);

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

    /**
     * @throws RouterException
     * @throws \ReflectionException
     */
    public function testSetupRouterAndRoutesWithConfigArrayNoRouterPart(): void
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
        static::assertSame(count($routes), 2);

        $router = new ReflectionClass($this->router);
        $property = $router->getProperty('globalMiddlewares');
        $property->setAccessible(true);

        static::assertEquals([], $property->getValue($this->router));
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoMiddlewareInRouterPart(): void
    {
        $config = [
            'router' => null
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router has to be an array');
        
        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoMiddlewareValidInRouterPart(): void
    {
        $config = [
            'router' => ['middlewares' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/middlewares has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoConstraintValidInRouterPart(): void
    {
        $config = [
            'router' => ['constraints' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/constraints has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoHostConstraintValidInRouterPart(): void
    {
        $config = [
            'router' => ['host_constraints' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/host_constraints has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoHostValidInRouterPart(): void
    {
        $config = [
            'router' => ['host' => null]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config router/host has to be a string');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayNoValidRoutes(): void
    {
        $config = [
            'routes' => null
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoMethodsInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoUrlInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoCallbackInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidMiddlewaresInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidHostInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidHostConstraintsInRoutesPart(): void
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

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidNameInRoutesPart(): void
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

    /**
     * @throws RouterException
     */
    public function testSetupRouterAndRoutesWithConfigArrayNoRoutesPart(): void
    {
        $config = [];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        $routes = $this->router->getRoutes();
        static::assertSame(count($routes), 0);
    }

    /**
     * @throws RouterException
     */
    public function testSetGlobalConstraints(): void
    {
        $request1Found = (new Factory())->createServerRequest('GET', '/article/fr');
        $request2Found = (new Factory())->createServerRequest('GET', '/article_bis/jp');

        $request1NotFound = (new Factory())->createServerRequest('GET', '/article/kx');
        $request2NotFound = (new Factory())->createServerRequest('GET', '/article_bis/m');

        $this->router->setGlobalParametersConstraints(['lang' => 'en|fr']);
        $this->router->get('/article/{lang}', 'a');
        $this->router->get('/article_bis/{lang}', 'b')->setParametersConstraints(['lang' => 'jp']);

        static::assertTrue($this->router->findRouteRequest($request1Found));
        static::assertFalse($this->router->findRouteRequest($request1NotFound));

        static::assertTrue($this->router->findRouteRequest($request2Found));
        static::assertFalse($this->router->findRouteRequest($request2NotFound));
    }

    /**
     * @throws RouterException
     */
    public function testGenerateUrl(): void
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

    /**
     * @throws RouterException
     */
    public function testGenerateUrlWithRouter(): void
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

        $subRoute = new Router();
        $subRoute->setupRouterAndRoutesWithConfigArray($config);
        $this->router->any('/(.*)', $subRoute);

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
    
    public function testSetGlobalHostRouter(): void
    {
        $host = 'api.toto.com';
        $this->router->setGlobalHost($host);
        $route = new Route('GET', '/abc', null);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
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
    
    public function testSetHost(): void
    {
        $host = 'api.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
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

    public function testSetHostPlaceholder(): void
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
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

    public function testSetHostPlaceholderInlineConstraints(): void
    {
        $host = '{subdomain:api|backoffice}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
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

    public function testSetHostAndConstraints(): void
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host, ['subdomain' => '\d{4}']);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    public function testSetHostAndConstraints2(): void
    {
        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $route->setHostConstraints(['subdomain' => '\d{4}']);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    public function testSetGlobalHostAndConstraints(): void
    {
        $this->router->setGlobalHostConstraints(['subdomain' => '\d{4}']);

        $host = '{subdomain}.toto.com';
        $route = new Route('GET', '/abc', null);
        $route->setHost($host);
        $this->router->addRoute($route);

        $request = (new Factory())->createServerRequest('GET', '/abc');
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => 'api.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertFalse($this->router->findRouteRequest($request));

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
    }

    /**
     * @throws RouterException
     */
    public function testSetGlobalHostAndConstraintsAndGetInfoInRequest(): void
    {
        $this->router->setGlobalHostConstraints(['subdomain' => '\d{4}']);

        $host = '{subdomain}.{domain}.{tld}';
        $route = new Route('GET', '/abc', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertEquals('1990', $req->getAttribute('subdomain'));
            static::assertEquals('toto', $req->getAttribute('domain'));
            static::assertEquals('com', $req->getAttribute('tld'));

            return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
        });
        $route->setHost($host);
        $this->router->addRoute($route);

        $serverHost = ['HTTP_HOST' => '1990.toto.com'];
        $request = new ServerRequest('GET', '/abc', [], null, '1.1', $serverHost);
        static::assertTrue($this->router->findRouteRequest($request));
        $this->router->dispatch($request);
    }

    /**
     * @throws RouterException
     * @throws \ReflectionException
     */
    public function testConfigWithHostAndConstraints(): void
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
                    'callback' => static function ($req, $next) {
                        static::assertInstanceOf(Router::class, $next[0]);
                        static::assertSame('handle', $next[1]);

                        static::assertEquals('api', $req->getAttribute('subdomain'));
                        static::assertEquals('2000', $req->getAttribute('domain'));
                        static::assertEquals('com', $req->getAttribute('tld'));

                        return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
                    },
                    'name' => 'route0',
                ],
                [
                    'methods' => ['GET'],
                    'url' => '/special',
                    'callback' => static function ($req, $next) {
                        static::assertInstanceOf(Router::class, $next[0]);
                        static::assertSame('handle', $next[1]);

                        static::assertEquals('api', $req->getAttribute('subdomain'));
                        static::assertEquals('2000', $req->getAttribute('domain'));
                        static::assertEquals('com', $req->getAttribute('tld'));

                        return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
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

    public function testDispatchNoRouteFound(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('No route found to dispatch');
        
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->dispatch($request);
    }

    /**
     * @throws RouterException
     */
    public function testDispatch404(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->setDefault404(static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(404, '')->withBody(Stream::create('404'));
        });
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }
    
    public function testDispatch404Error(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('The default404 is invalid');

        $this->router->setDefault404(4545);
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->dispatch($request);
    }

    public function testDispatch404ErrorStringNoProcessMethod(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('The default404 is invalid');

        $this->router->setDefault404(InvalidClass::class);
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->dispatch($request);
    }
    
    public function testHandleWithClosureNext(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('No route found to dispatch');

        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->get('/', null);
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $this->router->dispatch($request);
    }

    public function testHandleWithClosureNextStringNoMethodProcess(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Middleware is invalid: string');

        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->get('/', InvalidClass::class);
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);

        $this->router->dispatch($request);
    }

    /**
     * @throws RouterException
     */
    public function testDispatch404AfterAllMiddlewarePassed(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->setDefault404(static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(404, '')->withBody(Stream::create('404'));
        });
        $this->router->get('/', static function ($req, $next) {
            return $next($req);
        });
        $this->router->findRouteRequest($request);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testDispatch404Middleware(): void
    {
        $middleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $response = (new Factory())->createResponse(404, '')->withBody(Stream::create('404'));
        $middleware->method('process')->willReturn($response);
        
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->setDefault404($middleware);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('404', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testDispatch404StringMiddleware(): void
    {
        $request = (new Factory())->createServerRequest('GET', '/');
        $this->router->setDefault404(ExampleMiddleware::class);
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testDispatch404StringMiddlewareWithConfig(): void
    {
        $config = [
            'router' => [
                'default_404' => ExampleMiddleware::class
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);
        
        $request = (new Factory())->createServerRequest('GET', '/');
        $response = $this->router->dispatch($request);
        static::assertNotNull($response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('ok', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testRouterceptionRouterInRouteInRouter(): void
    {
        $subRouter1 = new Router();
        $subRouter1->any('/api/books/{id}', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(300, '')->withBody(Stream::create('testRouterception books'));
        });

        $subRouter2 = new Router();
        $subRouter2->any('/api/peoples/{id}', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(204, '')->withBody(Stream::create('testRouterception peoples'));
        });
        
        $this->router->any('/api/books/{id}', $subRouter1);
        $this->router->any('/api/peoples/{id}', $subRouter2);

        $request = (new Factory())->createServerRequest('GET', '/api/books/14');

        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
        $response = $this->router->dispatch($request);
        static::assertEquals(300, $response->getStatusCode());
        static::assertEquals('testRouterception books', $response->getBody());

        $request = (new Factory())->createServerRequest('GET', '/api/peoples/14');
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
        $response = $this->router->dispatch($request);
        static::assertEquals(204, $response->getStatusCode());
        static::assertEquals('testRouterception peoples', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testRouterception2RouterInMiddleware(): void
    {
        $subRouter1 = new Router();
        $subRouter1->any('/api/books/{id}', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(300, '')->withBody(Stream::create('testRouterception books'));
        });

        $subRouter2 = new Router();
        $subRouter2->any('/api/peoples/{id}', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(204, '')->withBody(Stream::create('testRouterception peoples'));
        });

        $this->router->addGlobalMiddleware($subRouter1);
        $this->router->addGlobalMiddleware($subRouter2);
        
        $this->router->any('/api/{items}/{id}', static function ($req, $next) {
            static::assertInstanceOf(Router::class, $next[0]);
            static::assertSame('handle', $next[1]);

            static::assertInstanceOf(ServerRequest::class, $req);

            return (new Factory())->createResponse(404, '')->withBody(Stream::create('no match'));
        });

        $request = (new Factory())->createServerRequest('GET', '/api/books/14');

        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
        $response = $this->router->dispatch($request);
        static::assertEquals(300, $response->getStatusCode());
        static::assertEquals('testRouterception books', $response->getBody());

        $request = (new Factory())->createServerRequest('GET', '/api/peoples/14');
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
        $response = $this->router->dispatch($request);
        static::assertEquals(204, $response->getStatusCode());
        static::assertEquals('testRouterception peoples', $response->getBody());

        $request = (new Factory())->createServerRequest('GET', '/api/bottles/14');
        $found = $this->router->findRouteRequest($request);
        static::assertTrue($found);
        $response = $this->router->dispatch($request);
        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('no match', $response->getBody());
    }

    /**
     * @throws RouterException
     */
    public function testFindRouteWithOneOptionalsParameters(): void
    {
        $this->router->get('/{params}', static function () {
        })->setOptionalsParameters(['params' => 1]);
        $found = $this->router->findRoute('GET', '/aze');
        static::assertTrue($found);
        $parameters = $this->router->getRouteParameters();
        static::assertArrayHasKey('params', $parameters);
        static::assertSame('aze', $parameters['params']);
    }

    /**
     * @throws RouterException
     */
    public function testFindRouteWithoutOneOptionalsParameters(): void
    {
        $this->router->get('/{params}', static function () {
        })->setOptionalsParameters(['params' => 1]);
        $found = $this->router->findRoute('GET', '/');
        static::assertTrue($found);
        $parameters = $this->router->getRouteParameters();
        static::assertArrayHasKey('params', $parameters);
        static::assertSame(1, $parameters['params']);
    }

    /**
     * @throws RouterException
     */
    public function testFindRouteWithMultiOptionalsParameters(): void
    {
        $this->router->get('/{items}/{category}/{offset}/{count}', static function () {
        })->setOptionalsParameters(
            ['items' => 1, 'category' => 2, 'offset' => 3, 'count' => 4]
        );
        $found = $this->router->findRoute('GET', '/aze/rty/5/8');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('aze', $parameters['items']);
        static::assertSame('rty', $parameters['category']);
        static::assertSame('5', $parameters['offset']);
        static::assertSame('8', $parameters['count']);
        
        $found = $this->router->findRoute('GET', '/aze/rty/5');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('aze', $parameters['items']);
        static::assertSame('rty', $parameters['category']);
        static::assertSame('5', $parameters['offset']);
        static::assertSame(4, $parameters['count']);
        
        $found = $this->router->findRoute('GET', '/aze/rty');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('aze', $parameters['items']);
        static::assertSame('rty', $parameters['category']);
        static::assertSame(3, $parameters['offset']);
        static::assertSame(4, $parameters['count']);
        
        $found = $this->router->findRoute('GET', '/aze');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('aze', $parameters['items']);
        static::assertSame(2, $parameters['category']);
        static::assertSame(3, $parameters['offset']);
        static::assertSame(4, $parameters['count']);

        $found = $this->router->findRoute('GET', '/');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame(1, $parameters['items']);
        static::assertSame(2, $parameters['category']);
        static::assertSame(3, $parameters['offset']);
        static::assertSame(4, $parameters['count']);
    }

    public function testSetupRouterAndRoutesWithConfigArrayWithNoValidOptionalParametersInRoutesPart(): void
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['POST'],
                    'url' => '/',
                    'callback' => 'a',
                    'optionals_parameters' => null
                ]
            ]
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Config routes/optionals_parameters has to be an array');

        $this->router->setupRouterAndRoutesWithConfigArray($config);
    }

    /**
     * @throws RouterException
     */
    public function testSetupRouterAndRoutesWithConfigArrayWithOptionalParametersInRoutesPart(): void
    {
        $config = [
            'routes' => [
                [
                    'methods' => ['GET'],
                    'url' => '/{items}',
                    'callback' => 'a',
                    'optionals_parameters' => ['items' => 'azerty']
                ]
            ]
        ];

        $this->router->setupRouterAndRoutesWithConfigArray($config);

        $found = $this->router->findRoute('GET', '/456');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('456', $parameters['items']);

        $found = $this->router->findRoute('GET', '/');
        static::assertTrue($found);

        $parameters = $this->router->getRouteParameters();
        static::assertSame('azerty', $parameters['items']);
    }
}

class ExampleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Factory())->createResponse(200, '')->withBody(Stream::create('ok'));
    }
}

class InvalidClass
{
}
