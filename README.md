# React-ZMQ

ZeroMQ bindings for React.

[![Build Status](https://secure.travis-ci.org/react-php/zmq.png)](http://travis-ci.org/react-php/zmq)

## Install

The recommended way to install react is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "react/zmq": "dev-master"
    }
}
```

## Example

Here is an example of a push socket:

```php
<?php

$loop = React\EventLoop\Factory::create();

$context = new React\Zmq\Context($loop);

$push = $context->getSocket(ZMQ::SOCKET_PUSH);
$push->connect('tcp://127.0.0.1:5555');

$i = 0;
$loop->addPeriodicTimer(1, function () use (&$i, $push) {
    $i++;
    echo "sending $i\n";
    $push->send($i);
});

$loop->run();
```

And the pull socket that goes with it:

```php
<?php
$loop = React\EventLoop\Factory::create();

$context = new React\Zmq\Context($loop);

$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555');

$pull->on('error', function ($e) {
    var_dump($e->getMessage());
});

$pull->on('message', function ($msg) {
    echo "Received: $msg\n";
});

$loop->run();
```

## Todo

* Integration tests
* Buffer limiting
* Do not push messages if no listener
* Re-use react/event-loop buffer once it's migrated from socket

## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
