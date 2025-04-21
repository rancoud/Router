<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Router\Route;
use Rancoud\Router\RouterException;

/** @internal */
class RouteTest extends TestCase
{
    /** @throws RouterException */
    public function testConstructArrayMethods(): void
    {
        $route = new Route(['GET', 'POST'], '/', static function (): void {});
        static::assertInstanceOf(Route::class, $route);
    }

    /** @throws RouterException */
    public function testConstructStringMethods(): void
    {
        $route = new Route('POST', '/', static function (): void {});
        static::assertInstanceOf(Route::class, $route);
    }

    public function testConstructRouterException(): void
    {
        try {
            new Route('', '/', static function (): void {});
        } catch (RouterException $e) {
            static::assertInstanceOf(RouterException::class, $e);
        }

        try {
            new Route('method', '/', static function (): void {});
        } catch (RouterException $e) {
            static::assertInstanceOf(RouterException::class, $e);
        }

        try {
            new Route('get', '/', static function (): void {});
        } catch (RouterException $e) {
            static::assertInstanceOf(RouterException::class, $e);
        }

        try {
            new Route('GET', '', static function (): void {});
        } catch (RouterException $e) {
            static::assertInstanceOf(RouterException::class, $e);
        }
    }
}
