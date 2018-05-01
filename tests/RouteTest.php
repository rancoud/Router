<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Router\Route;
use Rancoud\Router\RouterException;

/**
 * Class RouterTest.
 */
class RouteTest extends TestCase
{
    public function testConstructArrayMethods()
    {
        $route = new Route(['GET', 'POST'], '/', function () {
        });
        static::assertSame('Rancoud\Router\Route', get_class($route));
    }

    public function testConstructStringMethods()
    {
        $route = new Route('POST', '/', function () {
        });
        static::assertSame('Rancoud\Router\Route', get_class($route));
    }

    public function testConstrucRouterException()
    {
        try {
            new Route('', '/', function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route(false, '/', function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('method', '/', function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('get', '/', function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('GET', '', function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }
    }
}
