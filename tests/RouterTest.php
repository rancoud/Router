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
use Rancoud\Router\Route;
use Rancoud\Router\Router;

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
}
class ExampleMiddleware implements MiddlewareInterface{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return (new MessageFactory())->createResponse(200, null, [], 'ok');
    }
}