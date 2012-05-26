<?php

namespace React\Zmq;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Buffer extends EventEmitter
{
    public $socket;
    public $closed = false;
    public $listening = false;
    private $loop;
    private $messages = array();

    public function __construct(\ZMQSocket $socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;
    }

    public function send($message)
    {
        if ($this->closed) {
            return;
        }

        $this->messages[] = $message;

        if (!$this->listening) {
            $fd = $this->socket->getSockOpt(\ZMQ::SOCKOPT_FD);
            $that = $this;
            $listener = function () use ($that) {
                if ($that->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_OUT) {
                    $that->handleWrite();
                }
            };
            $this->loop->addWriteStream($fd, $listener);

            $this->listening = true;
        }
    }

    public function end()
    {
        $this->closed = true;

        if (!$this->listening) {
            $this->emit('end');
        }
    }

    public function handleWrite()
    {
        $fd = $this->socket->getSockOpt(\ZMQ::SOCKOPT_FD);
        foreach ($this->messages as $i => $message) {
            try {
                $sent = (bool) $this->socket->send($message, \ZMQ::MODE_DONTWAIT);
                if ($sent) {
                    unset($this->messages[$i]);
                    $this->loop->removeWriteStream($fd);
                    $that->listening = false;
                }
            } catch (\ZMQSocketException $e) {
                $that->emit('error', array($e));
            }
        }
    }
}
