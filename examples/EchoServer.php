<?php

// require autoload file from composer
require __DIR__ . '/../vendor/autoload.php';

class Logger extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        echo sprintf("%s: %s %s", $level, $message, !empty($context) ? json_encode($context) : '') . PHP_EOL;
    }
}

// listen on address 127.0.0.1 and port 8000
$echoServer = new \Hbliang\SimpleTcpServer\SelectServer('127.0.0.1', 8000);
//$echoServer = new \Hbliang\SimpleTcpServer\BlockServer('127.0.0.1', 8000);

// trigger while receiving data from client
$echoServer->on('data', function (\Hbliang\SimpleTcpServer\Connection $connection, $data) {
    // send data to client
    $connection->write($data . PHP_EOL);
});

// trigger when new connection comes
$echoServer->on('connection', function (\Hbliang\SimpleTcpServer\Connection $connection) {
    $connection->write('welcome' .PHP_EOL);
});

// trigger when occur error
$echoServer->on('error', function (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$echoServer->setLogger(new Logger());

$echoServer->run();

