<?php

namespace React\ZMQ;

use PHPUnit\Framework\TestCase;
use ZMQ;

class ContextTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldWrapARealZMQContext()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $innerContext = $this->getMockBuilder('ZMQContext')
            ->disableOriginalConstructor()
            ->getMock();

        $innerContext
            ->expects($this->once())
            ->method('getSocket')
            ->with(ZMQ::SOCKET_PULL, null);

        $context = new Context($loop, $innerContext);

        $context->getSocket(ZMQ::SOCKET_PULL, null);
    }

    /**
     * @test
     */
    public function getSocketShouldWrapSockets()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $context = new Context($loop);
        $socket = $context->getSocket(ZMQ::SOCKET_PULL);

        $this->assertInstanceOf('React\ZMQ\SocketWrapper', $socket);
    }

    /**
     * @test
     */
    public function getSocketShouldAddReadListener()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $loop
            ->expects($this->once())
            ->method('addReadStream');

        $context = new Context($loop);

        $context->getSocket(ZMQ::SOCKET_PULL);
    }

    /**
     * @test
     */
    public function getSocketShouldNotAddReadListenerForNonReadableSocketType()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $loop
            ->expects($this->never())
            ->method('addReadStream');

        $context = new Context($loop);

        $context->getSocket(ZMQ::SOCKET_PUSH);
    }
}
