<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
Monoelf\Framework\event_dispatcher\Message;
Monoelf\Framework\http\router\MiddlewareInterface;
Monoelf\Framework\http\ServerResponseInterface;
Monoelf\Framework\logger\LogContextEvent;
Monoelf\Framework\logger\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $this->eventDispatcher->trigger(LogContextEvent::ATTACH_CATEGORY, new Message(self::class));
        $this->logger->debug("Выполнено обращение методом {$request->getMethod()} к энпдоинту {$request->getUri()}");
        $this->eventDispatcher->trigger(LogContextEvent::FLUSH_CATEGORY);

        $next();
    }
}
