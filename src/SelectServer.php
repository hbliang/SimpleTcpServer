<?php


namespace Hbliang\SimpleTcpServer;


use Evenement\EventEmitter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class SelectServer extends EventEmitter implements LoggerAwareInterface, ServerInterface
{
    use LoggerAwareTrait;

    const SELECT_TIMEOUT = 0;

    protected $master;

    protected $resources = [];

    protected $running = false;

    protected $booted = false;

    public function __construct($domain = 'localhost', $port = 8000)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
        if ($socket === false) {
            $this->throwLastError();
        }
        if (socket_bind($socket, $domain, $port) === false) {
            $this->throwLastError();
        }

        socket_set_nonblock($socket);

        $this->master = $socket;
        $this->connections = new \SplObjectStorage();
        $this->logger = new \Psr\Log\NullLogger();
    }

    public function close()
    {
        if (!$this->running) {
            return;
        }

        socket_close($this->master);
    }

    public function pause()
    {
        $this->running = false;
    }

    public function resume()
    {
        if (!$this->booted) {
            $this->booted = true;

            if (socket_listen($this->master) === false) {
                $this->throwLastError();
            }
        }

        $this->running = true;

        if (!in_array($this->master, $this->resources)) {
            $this->resources[(int) $this->master] = $this->master;
        }
    }

    public function run()
    {
        $this->resume();

        while ($this->running) {
            $reads = $this->resources;
            $writes = [];
            $except = [];

            if (socket_select($reads, $writes, $except, self::SELECT_TIMEOUT) < 1) {
                continue;
            }

            if (in_array($this->master, $reads)) {
                $newSocket = socket_accept($this->master);

                if ($newSocket === false) {
                    $this->emit('error', [$this->lastError()]);
                } else {
                    $this->handleNewConnection($newSocket);
                }

                unset($reads[array_search($this->master, $reads)]);
            }

            foreach ($reads as $read) {
                $this->handleReadAction($read);
            }

            foreach ($writes as $write) {
                $this->handleWriteAction($write);
            }
        }
    }

    protected function handleReadAction($resource)
    {
        $connection = new Connection($this, $resource);
        if (false === ($data = $connection->read())) {
            $this->emit('error', [$this->lastError()]);
            $connection->close();
        }

        if (!$data = trim($data)) {
            return;
        }

        if ($data === 'quit') {
            $connection->close();
            $this->logger->info('client quit');
            return;
        }

        $this->emit('data', [$connection, $data]);
    }

    protected function handleWriteAction($resource)
    {

    }

    protected function handleNewConnection($socket)
    {
        $connection = new Connection($this, $socket);

        $this->logger->info('new client from ' . $connection->getRemoteAddress());

        $this->resources[(int) $socket] = $socket;

        $this->emit('connection', [$connection]);
    }

    public function removeConnection(ConnectionInterface $connection)
    {
        $resource = $connection->getResource();
        $resourceId = (int) $resource;

        if (isset($this->resources[$resourceId])) {
            unset($this->resources[$resourceId]);
        }
    }

    public function lastError()
    {
        return new \Exception(socket_strerror(socket_last_error()));
    }

    protected function throwLastError()
    {
        throw $this->lastError();
    }
}