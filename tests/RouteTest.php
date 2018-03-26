<?php

declare(strict_types=1);

namespace Rancoud\Router\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Router\Route;

/**
 * Class RouterTest.
 */
class RouteTest extends TestCase
{
    public function testConstructArrayMethods()
    {
        $route = new Route(['GET', 'POST'], '/', function () {
        });
        static::assertEquals('Rancoud\Router\Route', get_class($route));
    }

    public function testConstructStringMethods()
    {
        $route = new Route('POST', '/', function () {
        });
        static::assertEquals('Rancoud\Router\Route', get_class($route));
    }

    public function testConstructException()
    {
        try {
            new Route('', '/', function () {
            });
        } catch (\Exception $e) {
            static::assertEquals(\Exception::class, get_class($e));
        }

        try {
            new Route(false, '/', function () {
            });
        } catch (\Exception $e) {
            static::assertEquals(\Exception::class, get_class($e));
        }

        try {
            new Route('method', '/', function () {
            });
        } catch (\Exception $e) {
            static::assertEquals(\Exception::class, get_class($e));
        }

        try {
            new Route('get', '/', function () {
            });
        } catch (\Exception $e) {
            static::assertEquals(\Exception::class, get_class($e));
        }

        try {
            new Route('GET', '', function () {
            });
        } catch (\Exception $e) {
            static::assertEquals(\Exception::class, get_class($e));
        }
    }
}
