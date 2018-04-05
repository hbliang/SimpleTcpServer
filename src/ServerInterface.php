<?php


namespace Hbliang\SimpleTcpServer;


use Evenement\EventEmitterInterface;

interface ServerInterface extends EventEmitterInterface
{
    public function removeConnection(ConnectionInterface $connection);

    public function run();
}