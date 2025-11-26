<?php

namespace Arrgh11\LazyDebug\Services\Smtp;

use Arrgh11\LazyDebug\Services\Smtp\Enums\Command;
use Arrgh11\LazyDebug\Services\Smtp\Enums\Reply;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\Socket\SocketServer;

class Server
{
    protected const HOST = '127.0.0.1';

    protected const PORT = 2525;

    protected LoopInterface $loop;

    protected ?ServerInterface $socket = null;

    /** @var callable(string):void */
    protected $onMessageReceivedCallback; // Needs to be configured by consuming class

    public function __construct()
    {
        $this->loop = new StreamSelectLoop();
    }

    public static function new(): self
    {
        return new self();
    }

    /**
         * Start the server
         */
    public function serve(): void
    {
        $this->socket = new SocketServer(self::HOST . ':' . self::PORT, [], $this->loop);

        dump("SMTP server | Started SMTP server on: {$this->socket->getAddress()}");

        $this->socket->on('connection', function (ConnectionInterface $connection) {

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
                        error_log('SMTP server | ' . Reply::Okay->value . ' - received RSET');

                        $content = '';
                        $transferring = false;
                        $connection->write(Reply::Okay->value . "SMTP transfer reset!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::QUIT->value)) {
                        error_log('SMTP server | ' . Reply::Goodbye->value . ' - received QUIT');

                        $transferring = false;
                        $connection->end(Reply::Goodbye->value . " Goodbye!\r\n");

                        continue;
                    }

                    // -------------------------------------------------------------------
                    // Message transfer
                    // -------------------------------------------------------------------
                    if ($transferring) {

                        if ($line === '.') {
                            error_log('SMTP server | ' . Reply::Okay->value . ' - message received!');

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
                    if (str_starts_with($line, Command::EHLO->value)) {
                        error_log('SMTP server | ' . Reply::Okay->value . ' - received ' . $line);
                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::HELO->value)) {
                        error_log('SMTP server | ' . Reply::Okay->value . ' - received ' . $line);
                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::FROM_HEADER->value)) {
                        error_log('SMTP server | ' . Reply::Okay->value . ' - received MAIL FROM');

                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if (str_starts_with($line, Command::RECIPIENT_HEADER->value)) {
                        error_log('SMTP server | ' . Reply::Okay->value . ' - received RCPT TO');

                        $connection->write(Reply::Okay->value . " Ok!\r\n");

                        continue;
                    }

                    if ($line === Command::DATA->value) {
                        error_log('SMTP server | ' . Reply::StartTransfer->value . ' - starting message transfer');
                        $connection->write(Reply::StartTransfer->value . " Start transfer\r\n");

                        $transferring = true;

                        continue;
                    }

                    // TODO: Refactor to match & handle default 500 something reply
                    error_log('SMTP server | Not implemented - ' . $line);
                    $connection->write(Reply::CommandNotImplemented->value . " Not implemented\r\n");
                    $connection->close();
                }

            });

            $connection->on('close', function () use ($connection) {
                dump('SMTP server | ' . "Closed SMTP connection on: {$connection->getLocalAddress()}");
            });

        });

        $this->loop->run();
    }

    /**
     * Configures a callback to be executed whenever a message is fully recceived
     */
    public function onMessageReceived(callable $callback): self
    {
        $this->onMessageReceivedCallback = $callback;

        return $this;
    }

    /**
     * Stops the currently running server
     * NOTE: Not used since introduction Native ChildProcesses
     */
    public function stop(): void
    {
        if (! $this->socket) {
            return;
        }

        $this->socket->close();
        $this->loop->stop();
    }

    /**
     * Tries to kill the process on the configured Port nr.
     * NOTE: Not used since introduction Native ChildProcesses
     */
    public function kill(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = Process::run("netstat -ano | findstr :{$this->port}")->output();

            // Extract the PID from the output
            $parts = explode(' ', $output[0]);
            $pid = trim($parts[count($parts) - 1]);

            if ($pid) {
                Process::run("taskkill /F /PID {$pid}");
            }
        } else {
            // Unix like
            $pid = Process::run("lsof -ti :{$this->port}")->output();

            if ($pid) {
                Process::run("kill {$pid}");
            }
        }

        $this->stop();
    }

    /**
     * Check if a process is alive on the configured port
     * NOTE: Not used since introduction Native ChildProcesses
     */
    public function ping(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = Process::run("netstat -ano | findstr :{$this->port}")->output();

            // Extract the PID from the output
            $parts = explode(' ', $output[0]);
            $pid = trim($parts[count($parts) - 1]);

            return (bool) $pid;
        }

        // Unix like
        $pid = Process::run("lsof -ti :{$this->port}")->output();

        return (bool) $pid;
    }
    // SMTP server implementation will go here
}
