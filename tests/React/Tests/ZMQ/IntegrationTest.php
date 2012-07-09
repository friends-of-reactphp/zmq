<?php

namespace React\Tests\ZMQ;

use React\EventLoop\StreamSelectLoop;
use React\ZMQ\Context;

class IntergrationTest extends \PHPUnit_Framework_TestCase
{
    public function testPushPull()
    {
        $loop = new StreamSelectLoop();
        $context = new Context($loop);

        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('ipc://test.ipc');

        $push = $context->getSocket(\ZMQ::SOCKET_PUSH);
        $push->connect('ipc://test.ipc');

        $msgs = array();

        $pull->on('message', function ($msg) use (&$msgs) {
            $msgs[] = $msg;
        });

        $loop->addTimer(0.001, function () use ($push) {
            $push->send('foo');
            $push->send('bar');
            $push->send('baz');
        });

        $loop->addTimer(0.005, function () use ($loop) {
            $loop->stop();
        });

        $loop->run();

        $this->assertSame(array('foo', 'bar', 'baz'), $msgs);
    }

    public function testDealerRep()
    {
        $this->forkRepWorker();
        $this->forkRepWorker();

        $loop = new StreamSelectLoop();
        $context = new Context($loop);

        $dealer = $context->getSocket(\ZMQ::SOCKET_DEALER);
        $dealer->bind('ipc://test2.ipc');

        $msgs = array();

        $dealer->on('message', function ($msg) use (&$msgs) {
            $msgs[] = $msg;
        });

        $dealer->send(array('A', '', 'foo'));
        $loop->addTimer(0.5, function () use ($dealer) {
            $dealer->send(array('B', '', 'bar'));
        });

        $loop->addTimer(1, function () use ($loop) {
            $loop->stop();
        });

        $loop->run();

        $expected = array(
            array('A', '', 'foobar'),
            array('B', '', 'barbar'),
        );
        $this->assertSame($expected, $msgs);
    }

    private function forkRepWorker()
    {
        $pid = pcntl_fork();
        if ($pid != 0) {
            return;
        }

        $context = new \ZMQContext();
        $rep = $context->getSocket(\ZMQ::SOCKET_REP);
        $rep->connect('ipc://test2.ipc');

        $msg = $rep->recv();
        $rep->send($msg.'bar');

        exit;
    }
}
