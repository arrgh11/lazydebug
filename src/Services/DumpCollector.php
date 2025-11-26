<?php

namespace Arrgh11\LazyDebug\Services;

use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use React\Socket\TcpServer;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DumpCollector
{
    private array $_dumps = [];

    private int $_maxDumps = 50;

    private ?TcpServer $_serverSocket = null;

    private bool $debug = true; // Enable debugging

    public function __construct(
        private string $host = '127.0.0.1',
        private int $port = 9912
    ) {
        $this->_startServer();
    }

    private function _startServer(): void
    {
        $address = "tcp://{$this->host}:{$this->port}";


        // Create socket server
        $this->_serverSocket = new TcpServer($this->port);

        $this->_serverSocket->on('connection', function (ConnectionInterface $connection) {

            $dumper = new CliDumper();

            $connection->on('data', function ($rawData) use ($dumper) {
                // Decode the base64-encoded serialized data
                $decoded = base64_decode($rawData, true);

                if ($decoded === false) {
                    echo "Failed to decode base64 data\n";
                    return;
                }

                // Unserialize the data
                $payload = @unserialize($decoded);

                if ($payload === false) {
                    echo "Failed to unserialize data\n";
                    return;
                }

                // The payload is an array: [Data $data, array $context]
                [$data, $context] = $payload;

                if ($data instanceof Data) {
                    // Dump the data

                    // Optionally show context (file, line, timestamp)
                    if (isset($context['source'])) {

                        $contextString = sprintf(
                            "Source: %s:%d\n",
                            $context['source']['file'] ?? 'unknown',
                            $context['source']['line'] ?? 0
                        );

                        $this->addDump($contextString);
                    }
                }
            });

        });


        if (!$this->_serverSocket) {
            $this->addDump("Failed to start server on {$address}: {$errstr}");
            return;
        }

        // Make it non-blocking
        $this->addDump("VarDumper server started on {$address}");
    }


    public function addDump(string $dump): void
    {
        $this->_dumps[] = $dump;

        // Keep only last N dumps
        if (count($this->_dumps) > $this->_maxDumps) {
            array_shift($this->_dumps);
        }
    }

    public function getDumps(): array
    {
        return $this->_dumps;
    }

    public function clearDumps(): void
    {
        $this->_dumps = [];
    }

    public function __destruct()
    {
        if ($this->_serverSocket) {
            $this->_serverSocket->close();
        }
    }
}
