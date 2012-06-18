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
    private $fd;
    private $messages = array();

    public function __construct(\ZMQSocket $socket, $fd, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->fd = $fd;
        $this->loop = $loop;
    }

    public function send($message)
    {
        if ($this->closed) {
            return;
        }

        $this->messages[] = $message;

        if (!$this->listening) {
            $this->loop->addWriteStream($this->fd, array($this, 'handleWrite'));
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
        if (!$this->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_OUT) {
            return;
        }

        foreach ($this->messages as $i => $message) {
            try {
                $sent = (bool) $this->socket->send($message, \ZMQ::MODE_DONTWAIT);
                if ($sent) {
                    unset($this->messages[$i]);
                    if (0 === count($this->messages)) {
                        $this->loop->removeWriteStream($this->fd);
                        $this->listening = false;
                        $this->emit('end');
                    }
                }
            } catch (\ZMQSocketException $e) {
                $this->emit('error', array($e));
            }
        }
    }
}
