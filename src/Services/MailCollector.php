<?php

namespace Arrgh11\LazyDebug\Services;

use Arrgh11\LazyDebug\Services\Smtp\Enums\Command;
use Arrgh11\LazyDebug\Services\Smtp\Enums\Reply;
use Exception;
use React\Http\HttpServer;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use React\Socket\TcpServer;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class MailCollector
{
    private array $_dumps = [];

    private int $_maxDumps = 50;

    private ?SocketServer $_serverSocket = null;
    protected $onMessageReceivedCallback; // Needs to be configured by consuming class
    private bool $debug = true; // Enable debugging

    public function __construct(
        private string $host = '127.0.0.1',
        private int $port = 2525
    ) {
        $this->_startServer();

    }

    private function _startServer(): void
    {
        $this->_serverSocket = new SocketServer($this->host . ':' . $this->port);

        dump("SMTP server | Started SMTP server on: {$this->_serverSocket->getAddress()}");

        $this->_serverSocket->on('connection', function (ConnectionInterface $connection) {

            $content = '';
            $transferring = false;

            $connection->write(Reply::Ready->value . " Ok!\r\n");

            $connection->on('data', function ($data) use ($connection, &$content, &$transferring) {
                $lines = explode(PHP_EOL, $data);

                foreach ($lines as $line) {
                    $line = trim($line);

                    // -------------------------------------------------------------------
                    // Abort signals
                    // -------------------------------------------------------------------
                    if (str_starts_with($line, Command::RESET->value)) {
                        dump('SMTP server | ' . Reply::Okay->value . ' - received RSET');

                        $content = '';
                        $transferring = false;
                        $connection->write(Reply::Okay->value . "SMTP transfer reset!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::QUIT->value)) {
                        dump('SMTP server | ' . Reply::Goodbye->value . ' - received QUIT');

                        $transferring = false;
                        $connection->end(Reply::Goodbye->value . " Goodbye!\r\n");

                        continue;
                    }

                    // -------------------------------------------------------------------
                    // Message transfer
                    // -------------------------------------------------------------------
                    if ($transferring) {

                        if ($line === '.') {
                            dump('SMTP server | ' . Reply::Okay->value . ' - message received!');

                            call_user_func($this->onMessageReceivedCallback, $content);

                            $connection->write(Reply::Okay->value . " Ok!\r\n");
                            $transferring = false;

                            continue;
                        }

                        // All ok. Append message content
                        $content .= $line . PHP_EOL;

                        continue;
                    }

                    // -------------------------------------------------------------------
                    // Handshake ($transferring = false)
                    // -------------------------------------------------------------------
                    if (str_starts_with($line, Command::EHLO->value) || str_starts_with($line, Command::HELO->value)) {
                        dump('SMTP server | ' . Reply::Okay->value . ' - received ' . $line);
                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::FROM_HEADER->value)) {
                        dump('SMTP server | ' . Reply::Okay->value . ' - received MAIL FROM');

                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::RECIPIENT_HEADER->value)) {
                        dump('SMTP server | ' . Reply::Okay->value . ' - received RCPT TO');

                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if ($line === Command::DATA->value) {
                        dump('SMTP server | ' . Reply::StartTransfer->value . ' - starting message transfer');
                        $connection->write(Reply::StartTransfer->value . " Start transfer\r\n");

                        $transferring = true;

                        continue;
                    }

                    // TODO: Refactor to match & handle default 500 something reply
                    error_log('SMTP server | Not implemented - ' . $line);
                    $connection->write(Reply::CommandNotImplemented->value . " Not implemented\r\n");
                    // $connection->close();
                }

            });

            $connection->on('close', function () use ($connection) {
                dump('SMTP server | ' . "Closed SMTP connection on: {$connection->getLocalAddress()}");
            });

        });
    }


    public function addDump(string $dump): void
    {
        $this->_dumps[] = $dump;

        // Keep only last N dumps
        if (count($this->_dumps) > $this->_maxDumps) {
            array_shift($this->_dumps);
        }
    }
    /**
     * Configures a callback to be executed whenever a message is fully recceived
     */
    public function onMessageReceived(callable $callback): self
    {
        $this->onMessageReceivedCallback = $callback;

        return $this;
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
