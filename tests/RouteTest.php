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
    /**
     * @throws RouterException
     */
    public function testConstructArrayMethods(): void
    {
        $route = new Route(['GET', 'POST'], '/', function () {
        });
        static::assertInstanceOf(Route::class, $route);
    }

    /**
     * @throws RouterException
     */
    public function testConstructStringMethods(): void
    {
        $route = new Route('POST', '/', function () {
        });
        static::assertInstanceOf(Route::class, $route);
    }

    public function testConstructRouterException(): void
    {
        try {
            new Route('', '/', static function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route(false, '/', static function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('method', '/', static function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('get', '/', static function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }

        try {
            new Route('GET', '', static function () {
            });
        } catch (RouterException $e) {
            static::assertSame(RouterException::class, get_class($e));
        }
    }
}
