<?php

declare(strict_types=1);

namespace Unit;

Monoelf\Framework\container\ContainerInterface;
Monoelf\Framework\event_dispatcher\EventDispatcher;
Monoelf\Framework\event_dispatcher\Message;
Monoelf\Framework\event_dispatcher\ObserverInterface;
use Codeception\Test\Unit;
final class EventDispatcherTest extends Unit
{
    public function testSuccessAttachCallback(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $handler = fn () => null;

        $eventDispatcher->attach('event', $handler);

        $this->assertSame([[$handler, "__invoke"]], $eventDispatcher->getObservers('event'));
    }

    public function testSuccessAttachObserverClass(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $observerMock = $this->createMock(ObserverInterface::class);

        $eventDispatcher->attach('event', $observerMock);

        $this->assertSame([[$observerMock, "handle"]], $eventDispatcher->getObservers('event'));
    }

    public function testFailAttachNotObserverClass(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $notObserver = $this->getMockBuilder(\stdClass::class)->getMock();

        $this->expectException(\InvalidArgumentException::class);

        $eventDispatcher->attach('event', $notObserver);
    }

    public function testSuccessAttachObserverClassName(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $observerMock = $this->createMock(ObserverInterface::class);

        $eventDispatcher->attach('event', $observerMock::class);

        $this->assertSame([[$observerMock::class, "handle"]], $eventDispatcher->getObservers('event'));
    }

    public function testSuccessAttachObjectAndMethod(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $instance = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['methodName'])
            ->getMock();

        $eventDispatcher->attach('event', [$instance, 'methodName']);

        $this->assertSame([[$instance, "methodName"]], $eventDispatcher->getObservers('event'));
    }

    public function testFailAttachWithArraySizeOne(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $instance = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['methodName'])
            ->getMock();

        $this->expectException(\InvalidArgumentException::class);

        $eventDispatcher->attach('event', [$instance]);
    }

    public function testFailAttachWithArraySizeThree(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $instance = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['methodName'])
            ->getMock();

        $this->expectException(\InvalidArgumentException::class);

        $eventDispatcher->attach('event', [$instance, '1', '2']);
    }

    public function testSuccessAttachSameCallbackTwiceWithoutDuplicates(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $handler = fn () => null;

        for($i = 0; $i < 2; $i++) {
            $eventDispatcher->attach('event', $handler);
        }

        $this->assertSame([[$handler, "__invoke"]], $eventDispatcher->getObservers('event'));
    }

    public function testSuccessAttachDifferentCallbackInIncreaseOrder(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $handler1 = fn () => '1';
        $handler2 = fn () => '2';

        $eventDispatcher->attach('event', $handler1);
        $eventDispatcher->attach('event', $handler2);

        $this->assertSame(
            [[$handler1, "__invoke"], [$handler2, '__invoke']],
            $eventDispatcher->getObservers('event')
        );
    }

    public function testSuccessAttachDifferentCallbackInDecreaseOrder(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $handler1 = fn () => '1';
        $handler2 = fn () => '2';

        $eventDispatcher->attach('event', $handler2);
        $eventDispatcher->attach('event', $handler1);

        $this->assertSame(
            [[$handler2, "__invoke"], [$handler1, '__invoke']],
            $eventDispatcher->getObservers('event')
        );
    }

    public function testSuccessTriggerOnEmpty(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $message = new Message();

        $containerMock->expects($this->exactly(0))->method('call');

        $eventDispatcher->trigger('event', $message);
    }

    public function testSuccessTriggerObserverClass(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $observerMock = $this->createMock(ObserverInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $eventDispatcher->attach('event', $observerMock);
        $message = new Message();

        $containerMock->expects($this->once())->method('call')->with(
            $observerMock,
            'handle',
            ['message' => $message, 'eventName' => 'event']
        );

        $eventDispatcher->trigger('event', $message);
    }

    public function testSuccessTriggerObserverClassName(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $observerMock = $this->createMock(ObserverInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $eventDispatcher->attach('event', $observerMock::class);
        $message = new Message();

        $containerMock->expects($this->once())->method('call')->with(
            $observerMock::class,
            'handle',
            ['message' => $message, 'eventName' => 'event']
        );

        $eventDispatcher->trigger('event', $message);
    }

    public function testSuccessDetach(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $observerMock = $this->createMock(ObserverInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $eventDispatcher->attach('event', $observerMock);

        $eventDispatcher->detach('event', $observerMock);

        $this->assertEmpty($eventDispatcher->getObservers('event'));
    }

    public function testSuccessDetachOnEmpty(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $observerMock = $this->createMock(ObserverInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);

        $eventDispatcher->detach('event', $observerMock);

        $this->assertEmpty($eventDispatcher->getObservers('event'));
    }

    public function testSuccessDetachOneOfTwoEvent(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $observerMock = $this->createMock(ObserverInterface::class);
        $eventDispatcher = new EventDispatcher($containerMock);
        $eventDispatcher->attach('event1', $observerMock);
        $eventDispatcher->attach('event2', $observerMock);

        $eventDispatcher->detach('event1', $observerMock);

        $this->assertSame([[$observerMock, 'handle']], $eventDispatcher->getObservers('event2'));
    }
}
