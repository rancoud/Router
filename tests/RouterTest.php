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
        static::assertEquals(true, $found);
    }
}
