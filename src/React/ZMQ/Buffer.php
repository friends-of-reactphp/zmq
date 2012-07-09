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
        if (!$this->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_OUT) {
            return;
        }

        foreach ($this->messages as $i => $message) {
            try {
                $flags = is_array($message) ? \ZMQ::MODE_SNDMORE : 0;
                $first = is_array($message) ? $message[0] : $message;
                $sent = (bool) $this->socket->send($first, \ZMQ::MODE_DONTWAIT | $flags);
                if ($sent) {
                    if (is_array($message) && count($message) > 1) {
                        array_shift($message);
                        foreach ($message as $msg) {
                            $flags = count($message) > 1 ? \ZMQ::MODE_SNDMORE : 0;
                            $this->socket->send($msg, \ZMQ::MODE_DONTWAIT | $flags);
                            array_shift($message);
                        }
                    }

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
