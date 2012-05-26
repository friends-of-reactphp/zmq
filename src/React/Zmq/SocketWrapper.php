<?php

namespace React\Zmq;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class SocketWrapper extends EventEmitter
{
    private $socket;
    private $loop;
    private $buffer;

    public function __construct(\ZMQSocket $socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;

        $fd = $this->socket->getSockOpt(\ZMQ::SOCKOPT_FD);
        $this->buffer = new Buffer($socket, $this->loop);
    }

    public function getWrappedSocket()
    {
        return $this->socket;
    }

    public function subscribe($channel)
    {
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, $channel);
    }

    public function unsubscribe($channel)
    {
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_UNSUBSCRIBE, $channel);
    }

    public function send($message)
    {
        $this->buffer->send($message);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->socket, $method), $args);
    }
}
