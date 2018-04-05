<?php


namespace Hbliang\SimpleTcpServer;


use Evenement\EventEmitter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class BlockServer extends EventEmitter implements LoggerAwareInterface, ServerInterface
{
    use LoggerAwareTrait;

    protected $master;

    /**
     * @var \SplObjectStorage
     */
    protected $connections;

    protected $running = false;

    public function __construct($domain = 'localhost', $port = 8000)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
        if ($socket === false) {
            $this->throwLastError();
        }
        if (socket_bind($socket, $domain, $port) === false) {
            $this->throwLastError();
        }

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

    public function run()
    {
        if (socket_listen($this->master) === false) {
            $this->throwLastError();
        }

        $this->running = true;

        $this->logger->info('start');

        while ($this->running) {
            $this->logger->info('waiting connection...');
            $socket = socket_accept($this->master);
            if ($socket === false) {
                $this->emit('error', [$this->lastError()]);
                continue;
            }

            $this->handleNewConnection($socket);
        };
    }


    protected function handleNewConnection($socket)
    {
        $connection = new Connection($this, $socket);

        $this->logger->info('new client from ' . $connection->getRemoteAddress());

        $this->connections->attach($connection);

        $this->emit('connection', [$connection]);

        do {
            if (false === ($data = $connection->read())) {
                $this->emit('error', [$this->lastError()]);
                $connection->close();
                break;
            }

            if (!$data = trim($data)) {
                continue;
            }

            if ($data === 'quit') {
                $connection->close();
                $this->logger->info('client quit');
                break;
            }

            $this->emit('data', [$connection, $data]);

        } while (true);
    }

    public function removeConnection(ConnectionInterface $connection)
    {

        $this->connections->detach($connection);
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