<?php

declare(strict_types=1);

namespace tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Stream;

class ExampleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Factory())->createResponse()->withBody(Stream::create('ok'));
    }
}
