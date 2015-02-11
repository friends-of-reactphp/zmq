<?php

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$context = new React\ZMQ\Context($loop);

$sub = $context->getSocket(\ZMQ::SOCKET_SUB);
$sub->connect('tcp://127.0.0.1:5555');
$sub->subscribe('sub');
$sub->on('messages', function ($msg) {
    echo "Received: ". $msg[1] ." on channel: ". $msg[0] ."\n";
});

$bus = $context->getSocket(\ZMQ::SOCKET_SUB);
$bus->connect('tcp://127.0.0.1:5555');
$bus->subscribe('bus');
$bus->on('messages', function ($msg) {
    echo $msg[0] ." :lennahc no ". $msg[1] ." :devieceR\n";
});

$pub = $context->getSocket(\ZMQ::SOCKET_PUB);
$pub->bind('tcp://127.0.0.1:5555');
$i = 0;
$loop->addPeriodicTimer(1, function () use (&$i, $pub) {
    $i++;
    echo "publishing $i\n";
    $pub->sendmulti(array('sub', $i)); // you get this one in the sub socket
    $pub->sendmulti(array('bus', $i)); // you don't get this one
});

$loop->run();
