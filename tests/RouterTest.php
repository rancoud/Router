<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rancoud\Http\Message\Factory\MessageFactory;
use Rancoud\Router\Route;
use Rancoud\Router\Router;

/**
 * Class RouterTest.
 */
class RouterTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testAddRoute()
    {
        Router::addRoute(new Route('GET', '/', function () {
        }));
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutGet()
    {
        Router::get('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutPost()
    {
        Router::post('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutPut()
    {
        Router::put('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutPatch()
    {
        Router::patch('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutDelete()
    {
        Router::delete('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutOptions()
    {
        Router::options('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcutAny()
    {
        Router::any('/', function () {
        });
        static::assertSame(1, count(Router::getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFindRoute()
    {
        Router::get('/', function () {
        });
        $found = Router::findRoute('GET', '/');
        static::assertTrue($found);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFindRouteWithQSA()
    {
        Router::get('/', function () {
        });
        Router::post('/', function () {
        });
        $found = Router::findRoute('POST', '/?qsa=asq');
        static::assertTrue($found);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotFindRoute()
    {
        Router::get('/', function () {
        });
        $found = Router::findRoute('GET', '/aze');
        static::assertFalse($found);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFindRouteWithParameters()
    {
        Router::get('/{id}', function () {
        });
        $found = Router::findRoute('GET', '/aze');
        static::assertTrue($found);
        $parameters = Router::getRouteParameters();
        static::assertTrue(array_key_exists('id', $parameters));
        static::assertSame('aze', $parameters['id']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFindRouteWithParametersAndRegexOnIt()
    {
        $route = new Route('GET', '/articles/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['locale' => 'fr|en', 'year' => '\d{4}']);
        Router::addRoute($route);

        $found = Router::findRoute('GET', '/articles/fr/1990/myslug');
        static::assertTrue($found);
        $parameters = Router::getRouteParameters();
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('fr', $parameters['locale']);
        static::assertSame('1990', $parameters['year']);
        static::assertSame('myslug', $parameters['slug']);

        $found = Router::findRoute('GET', '/articles/fra/1990/myslug');
        static::assertFalse($found);

        $found = Router::findRoute('GET', '/articles/fr/199/myslug');
        static::assertFalse($found);

        $ipRegex = '\b(?:(?:25[0-5]|2[0-4][0-9]|1?[1-9][0-9]?|10[0-9])(?:(?<!\.)\b|\.))';
        $ipRegex .= '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:(?<!\.)\b|\.)){3}';
        $route = new Route('GET', '/articles/{ip}/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints([
            'ip'     => $ipRegex,
            'locale' => 'fr|en',
            'year'   => '\d{4}'
        ]);
        Router::addRoute($route);

        $found = Router::findRoute('GET', '/articles/192.168.1.1/en/2004/myotherslug?qsa=asq');
        static::assertTrue($found);
        $parameters = Router::getRouteParameters();
        static::assertTrue(array_key_exists('ip', $parameters));
        static::assertTrue(array_key_exists('locale', $parameters));
        static::assertTrue(array_key_exists('year', $parameters));
        static::assertTrue(array_key_exists('slug', $parameters));
        static::assertSame('192.168.1.1', $parameters['ip']);
        static::assertSame('en', $parameters['locale']);
        static::assertSame('2004', $parameters['year']);
        static::assertSame('myotherslug', $parameters['slug']);
    }

    /**
     * @runInSeparateProcess
     */
    /*public function testCallable()
    {
        Router::get('/', function () {
            static::assertTrue(true);

            return (new MessageFactory())->createResponse();
        });
        Router::findRoute('GET', '/');
        Router::dispatch();

        Router::get('/{id}', function (ServerRequestInterface $req) {
            static::assertTrue(true);
            static::assertSame('12', $req->getAttribute('id'));
            return (new MessageFactory())->createResponse();
        });
        Router::findRoute('GET', '/12');
        Router::dispatch();
    }*/

    /**
     * @runInSeparateProcess
     */
    /*public function testRequestHandler()
    {
        Router::get('/', new ControllerDummy());
        Router::findRoute('GET', '/');
        $response = Router::dispatch();
        static::assertNotNull($response);
    }*/
}
/*
require __DIR__ . '/../vendor/rancoud/http/src/Psr/RequestHandlerInterface.php';
class ControllerDummy implements \Psr\Http\Server\RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new MessageFactory())->createResponse();
    }
}
*/