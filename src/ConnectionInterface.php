<?php


namespace Hbliang\SimpleTcpServer;


interface ConnectionInterface
{
    public function getResource();

    public function close();

    public function read();

    public function write($data);

    public function getRemoteAddress();

    public function getLocalAddress();
}