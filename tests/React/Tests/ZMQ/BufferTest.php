<?php

namespace React\Tests\ZMQ;

use React\ZMQ\Buffer;

class BufferTest extends \PHPUnit_Framework_TestCase
{
    public function testSendShouldQueueMessages()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->once())
            ->method('addWriteStream');

        $socket = $this->getMockBuilder('ZMQSocket')->disableOriginalConstructor()->getMock();
        $socket
            ->expects($this->never())
            ->method('send');

        $buffer = new Buffer($socket, 42, $loop);
        $buffer->send('foo');
    }

    public function testLoopShouldSendQueuedMessages()
    {
        $writeListener = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->once())
            ->method('addWriteStream')
            ->will($this->returnCallback(function ($stream, $listener) use (&$writeListener) {
                $writeListener = function () use ($stream, $listener) {
                    return call_user_func($listener, $stream);
                };
            }));

        $socket = $this->getMockBuilder('ZMQSocket')->disableOriginalConstructor()->getMock();
        $socket
            ->expects($this->at(0))
            ->method('getSockOpt')
            ->with(\ZMQ::SOCKOPT_EVENTS)
            ->will($this->returnValue(\ZMQ::POLL_OUT));
        $socket
            ->expects($this->at(1))
            ->method('send')
            ->with('foo', \ZMQ::MODE_DONTWAIT)
            ->will($this->returnSelf());
        $socket
            ->expects($this->at(2))
            ->method('send')
            ->with('bar', \ZMQ::MODE_DONTWAIT)
            ->will($this->returnSelf());

        $buffer = new Buffer($socket, 42, $loop);
        $buffer->send('foo');
        $buffer->send('bar');

        $this->assertNotNull($writeListener);
        $writeListener();
    }
}
