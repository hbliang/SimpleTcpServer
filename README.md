# Simple TCP Server

There are two tcp server. The one is blocking tcp server, then the other is non-blocking tcp server.

`BlockServer` is limited and accept only one client. It means that the server can handle only one connection at a time. Only when the client leaved and close the connection can next client be processed by server.

`SelectServer` built upon `system Select()` is better than `BlockServer` due to non-blocking feature. So the server can accept multiple client simultaneously.


## Examples

### Echo Server
`php examples/EchoServer.php`
```PHP
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

```


### Http Server

`php examples/EchoServer.php`
```PHP
<?php
require __DIR__ . '/../vendor/autoload.php';

$tcpServer = new \Hbliang\SimpleTcpServer\SelectServer('localhost', 8000);

$httpServer = new \Hbliang\SimpleTcpServer\HttpServer($tcpServer);

$httpServer->get('/', function(\Symfony\Component\HttpFoundation\Request $request) {
    return 'hello world';
});

$httpServer->run();
```


## Reference
* [ReactPHP](https://github.com/reactphp/react)
* [Workerman](https://github.com/walkor/Workerman)
