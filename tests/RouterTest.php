<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rancoud\Http\Message\Factory\MessageFactory;
use Rancoud\Http\Message\Factory\ServerRequestFactory;
use Rancoud\Router\Route;
use Rancoud\Router\Router;

/**
 * Class RouterTest.
 */
class RouterTest extends TestCase
{
    public function testAddRoute()
    {
        $router = new Router();
        $router->addRoute(new Route('GET', '/', function () {
        }));
        static::assertSame(1, count($router->getRoutes()));
    }

    public function testShortcutGet()
    {
        $router = new Router();
        $router->get('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }

    public function testShortcutPost()
    {
        $router = new Router();
        $router->post('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testShortcutPut()
    {
        $router = new Router();
        $router->put('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testShortcutPatch()
    {
        $router = new Router();
        $router->patch('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testShortcutDelete()
    {
        $router = new Router();
        $router->delete('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testShortcutOptions()
    {
        $router = new Router();
        $router->options('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testShortcutAny()
    {
        $router = new Router();
        $router->any('/', function () {
        });
        static::assertSame(1, count($router->getRoutes()));
    }
    
    public function testFindRoute()
    {
        $router = new Router();
        $router->get('/', function () {
        });
        $found = $router->findRoute('GET', '/');
        static::assertTrue($found);
    }
    
    public function testFindRouteWithQSA()
    {
        $router = new Router();
        $router->get('/', function () {
        });
        $router->post('/', function () {
        });
        $found = $router->findRoute('POST', '/?qsa=asq');
        static::assertTrue($found);
    }

    public function testFindRouteUri()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/azerty');
        $router = new Router();
        $router->get('/azerty', function () {
        });
        $found = $router->findRouteRequest($request);
        static::assertTrue($found);
    }
    
    public function testNotFindRoute()
    {
        $router = new Router();
        $router->get('/', function () {
        });
        $found = $router->findRoute('GET', '/aze');
        static::assertFalse($found);
    }
    
    public function testFindRouteWithParameters()
    {
        $router = new Router();
        $router->get('/{id}', function () {
        });
        $found = $router->findRoute('GET', '/aze');
        static::assertTrue($found);
        $parameters = $router->getRouteParameters();
        static::assertTrue(array_key_exists('id', $parameters));
        static::assertSame('aze', $parameters['id']);
    }
    
    public function testFindRouteWithParametersAndRegexOnIt()
    {
        $router = new Router();
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        $router->addRoute($route);

        $found = $router->findRoute('GET', '/articles/fr/1990/myslug');
        static::assertTrue($found);
        $parameters = $router->getRouteParameters();
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('fr', $parameters['locale']);
        static::assertSame('1990', $parameters['year']);
        static::assertSame('myslug', $parameters['slug']);

        $found = $router->findRoute('GET', '/articles/fra/1990/myslug');
        static::assertFalse($found);

        $found = $router->findRoute('GET', '/articles/fr/199/myslug');
        static::assertFalse($found);

        $ipRegex = '\b(?:(?:25[0-5]|2[0-4][0-9]|1?[1-9][0-9]?|10[0-9])(?:(?<!\.)\b|\.))';
        $ipRegex .= '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:(?<!\.)\b|\.)){3}';
        $route = new Route('GET', '/articles/{ip}/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints([
            'ip'     => $ipRegex,
            'locale' => 'fr|en',
            'year'   => '\d{4}'
        ]);
        $router->addRoute($route);

        $found = $router->findRoute('GET', '/articles/192.168.1.1/en/2004/myotherslug?qsa=asq');
        static::assertTrue($found);
        $parameters = $router->getRouteParameters();
        static::assertTrue(array_key_exists('ip', $parameters));
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('192.168.1.1', $parameters['ip']);
        static::assertSame('en', $parameters['locale']);
        static::assertSame('2004', $parameters['year']);
        static::assertSame('myotherslug', $parameters['slug']);
    }
    
    public function testCallable()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/azerty');
        $router = new Router();
        $router->get('/', function () {
            static::assertTrue(true);

            return (new MessageFactory())->createResponse();
        });
        $router->findRoute('GET', '/');
        $response = $router->dispatch($request);
        static::assertNotNull($response);

        $router->get('/{id}', function (ServerRequestInterface $req) {
            static::assertTrue(true);
            static::assertSame('12', $req->getAttribute('id'));
            return (new MessageFactory())->createResponse();
        });
        $router->findRoute('GET', '/12');
        $response = $router->dispatch($request);
        static::assertNotNull($response);
    }
}