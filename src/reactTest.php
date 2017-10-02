<?php

use React\Http\Server;
use React\Http\ServerRequest;
use React\Http\Response;
use React\Stream\ThroughStream;
use React\Stream\ReadableStream;
use function RingCentral\Psr7\str;
use React\Stream\WritableStream;

require __DIR__ . '/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$stream = new ThroughStream();
$server = new Server(function (ServerRequest $request) use ($loop, $stream) {

    echo str($request);
    if ($request->getRequestTarget() === '/log') {

        echo "GOOO!";
        $stream->on('error', function (\Exception $exception) {
            echo "Error: ";
            echo $exception->getMessage() . PHP_EOL;
        });


        $stream->on('close', function () {
            echo "close\n";
        });

        $stream->on('end', function () {
            echo "end\n";
        });

        $loop->addPeriodicTimer(1, function () use ($stream) {
            $stream->write("data: " . exec("python fetchHumidityInPercent.py"). "\n\n");
        });

        return new Response(
            200,
            array(
                'Content-Type' => 'text/event-stream',
            ),
            $stream
        );
    }

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>EventSource example</title>
    <script>
      var es = new EventSource("log");
      es.addEventListener("message", function (event) {
    	  var div = document.createElement("div");
          div.appendChild(document.createTextNode(event.data));
          document.body.appendChild(div);
      });
    </script>
</head>
<body>
</body>
</html>
';
    return new Response(
        200,
        array('Content-Type' => 'text/html'),
        $body
    );
});


$socket = new \React\Socket\Server('0.0.0.0:1080', $loop);
$server->listen($socket);

$loop->run();
