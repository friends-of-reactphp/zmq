<?php

namespace React\ZMQ;

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
            $this->listening = true;
            $this->loop->addWriteStream($this->fd, array($this, 'handleWrite'));
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
        $events = $this->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS);

        if (!$events & \ZMQ::POLL_OUT) {
            if ($events & \ZMQ::POLL_IN) {
                $this->emit('written');
            }
            return;
        }

        foreach ($this->messages as $i => $message) {
            try {
                $message = !is_array($message) ? array($message) : $message;
                $sent = (bool) $this->socket->sendmulti($message, \ZMQ::MODE_NOBLOCK);
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

        $this->emit('written');
    }
}
