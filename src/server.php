<?php

use React\Http\Server;
use React\Http\Response;
use React\Stream\ThroughStream;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . '/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$stream = new ThroughStream();
$server = new Server(function (ServerRequestInterface $request) use ($loop, $stream) {
    $header = array('Content-Type' => 'text/html');
    $body = file_get_contents(dirname(__FILE__) . '/eventsource.html');

    if ($request->getRequestTarget() === '/log') {
        $header = array('Content-Type' => 'text/event-stream');

        $stream->on('error', function (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        });

        $loop->addPeriodicTimer(1, function () use ($stream, $loop) {
            $process = new React\ChildProcess\Process('python ' . dirname(__FILE__) . '/fetchHumidityInPercent.py');
            $process->start($loop);

            $process->stdout->on('data', function ($data) use ($stream) {
                $stream->write("data: " . $data . "\n\n");
            });
        });

        $body = $stream;
    }

    return new Response(
        200,
        $header,
        $body
    );
});

$socket = new \React\Socket\Server('0.0.0.0:1080', $loop);
$server->listen($socket);

$loop->run();
