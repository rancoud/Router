<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
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

    public function testNotFindRoute()
    {
        Router::get('/', function () {
        });
        $found = Router::findRoute('GET', '/aze');
        static::assertFalse($found);
    }

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

        $route = new Route('GET', '/articles/{ip}/{locale}/{year}/{slug}', null);
        $route->setParametersConstraints(['ip' => '\b(?:(?:25[0-5]|2[0-4][0-9]|1?[1-9][0-9]?|10[0-9])(?:(?<!\.)\b|\.))(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:(?<!\.)\b|\.)){3}', 'locale' => 'fr|en', 'year' => '\d{4}']);
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

        $route = new Route('GET', '/{html_tag}', null);
        $route->setParametersConstraints(['html_tag' => '\s*?([\d\.]+(\,\d{1,2})?|\,\d{1,2})\s*']);
        Router::addRoute($route);

        $found = Router::findRoute('GET', '/000,00');
        static::assertTrue($found);
        $parameters = Router::getRouteParameters();
        static::assertTrue(array_key_exists('html_tag', $parameters));
        static::assertSame('000,00', $parameters['html_tag']);
    }
}
