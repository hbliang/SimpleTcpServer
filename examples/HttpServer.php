<?php
require __DIR__ . '/../vendor/autoload.php';

$tcpServer = new \Hbliang\SimpleTcpServer\SelectServer('localhost', 8000);

$httpServer = new \Hbliang\SimpleTcpServer\HttpServer($tcpServer);

$httpServer->get('/', function(\Symfony\Component\HttpFoundation\Request $request) {
    return 'hello world';
});

$httpServer->run();