<?php

use React\Http\Server;
use React\Http\Response;
use React\Stream\ThroughStream;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . '/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$stream = new ThroughStream();
$server = new Server(function (ServerRequestInterface $request) use ($loop, $stream) {
    if ($request->getRequestTarget() === '/log') {

        $stream->on('error', function (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        });

        $loop->addPeriodicTimer(1, function () use ($stream) {
            $stream->write("data: " . exec("python " . __DIR__ . "/fetchHumidityInPercent.py"). "\n\n");
        });

        return new Response(
            200,
            array(
                'Content-Type' => 'text/event-stream',
            ),
            $stream
        );
    }

    return new Response(
        200,
        array('Content-Type' => 'text/html'),
        file_get_contents(__DIR__ . '/eventsource.html')
    );
});

$socket = new \React\Socket\Server('0.0.0.0:1080', $loop);
$server->listen($socket);

$loop->run();
