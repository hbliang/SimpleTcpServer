<?php


namespace Hbliang\SimpleTcpServer;


class Connection implements ConnectionInterface
{
    const MAX_READ = 2048;
    /**
     * @var ServerInterface
     */
    protected $server;

    protected $resource;

    public function __construct(ServerInterface $server, $resource)
    {
        $this->server = $server;
        $this->resource = $resource;
    }

    public function getRemoteAddress()
    {
        socket_getpeername($this->resource, $ip, $port);
        return $ip . ':' . $port;
    }

    public function getLocalAddress()
    {
        socket_getsockname($this->resource, $ip, $port);
        return $ip . ':' . $port;
    }

    public function close()
    {
        socket_close($this->resource);
        $this->server->removeConnection($this);
    }

    public function read()
    {
        return socket_read($this->resource, self::MAX_READ, PHP_BINARY_READ);
    }

    public function write($data)
    {
        return socket_write($this->resource, (string) $data);
    }

    public function getResource()
    {
        return $this->resource;
    }
}