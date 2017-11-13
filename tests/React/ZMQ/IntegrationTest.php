<?php

namespace React\ZMQ;

use PHPUnit\Framework\TestCase;
use React\EventLoop\StreamSelectLoop;
use ZMQ;
use ZMQContext;

class IntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function pushPull()
    {
        $loop = new StreamSelectLoop();
        $context = new Context($loop);

        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind('ipc://test.ipc');

        $push = $context->getSocket(ZMQ::SOCKET_PUSH);
        $push->connect('ipc://test.ipc');

        $messages = array();

        $pull->on('message', function ($message) use (&$messages) {
            $messages[] = $message;
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

        $this->assertSame(array('foo', 'bar', 'baz'), $messages);
    }

    /**
     * @test
     */
    public function dealerRep()
    {
        $pids[] = $this->forkRepWorker();
        $pids[] = $this->forkRepWorker();

        $loop = new StreamSelectLoop();
        $context = new Context($loop);

        $dealer = $context->getSocket(ZMQ::SOCKET_DEALER);
        $dealer->bind('ipc://test2.ipc');

        sleep(1);

        $messages = array();

        $dealer->on('messages', function ($message) use (&$messages) {
            $messages[] = $message;
        });

        $dealer->send(array('A', '', 'foo'));
        $dealer->send(array('B', '', 'bar'));

        $loop->addTimer(1, function () use ($loop) {
            $loop->stop();
        });

        $loop->run();

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status, WUNTRACED);
        }

        $this->assertCount(2, $messages);
        $this->assertContains(array('A', '', 'foobar'), $messages);
        $this->assertContains(array('B', '', 'barbar'), $messages);
    }

    protected function forkRepWorker()
    {
        $pid = pcntl_fork();

        if ($pid != 0) {
            return $pid;
        }

        $context = new ZMQContext();

        $rep = $context->getSocket(ZMQ::SOCKET_REP);
        $rep->connect('ipc://test2.ipc');

        $message = $rep->recv();

        $rep->send($message . 'bar');

        exit;
    }
}
