<?php

namespace React\Zmq;

use React\EventLoop\LoopInterface;

class Context
{
    private $loop;
    private $context;

    public function __construct(LoopInterface $loop, \ZMQContext $context = null)
    {
        $this->loop = $loop;
        $this->context = $context ?: new \ZMQContext();
    }

    public function __call($method, $args)
    {
        $res = call_user_func_array(array($this->context, $method), $args);
        if ($res instanceof \ZMQSocket) {
            $res = $this->wrapSocket($res);
        }
        return $res;
    }

    private function wrapSocket(\ZMQSocket $socket)
    {
        $wrapped = new SocketWrapper($socket, $this->loop);

        $fd = $socket->getSockOpt(\ZMQ::SOCKOPT_FD);
        $this->loop->addReadStream($fd, function ($fd) use ($wrapped, $socket) {
            while ($socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_IN) {
                $message = $socket->recv(\ZMQ::MODE_DONTWAIT);
                if (false !== $message) {
                    $wrapped->emit('message', array($message));
                }
            }
        });

        return $wrapped;
    }
}
