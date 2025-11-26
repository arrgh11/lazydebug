<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug\Widgets;

use PhpTui\Tui\Display\Area;
use PhpTui\Tui\Display\Buffer;
use PhpTui\Tui\Position\Position;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Tui\Widget\WidgetRenderer;

final class DumpServer implements Widget, WidgetRenderer
{
    public function render(WidgetRenderer $renderer, Widget $widget, Buffer $buffer, Area $area): void
    {

        // if (empty($this->socket)) {
        //     $this->socket = stream_socket_server(
        //         "tcp://127.0.0.1:9912",
        //         $errno,
        //         $errstr
        //     );
        //
        //     if (!$this->socket) {
        //         die("Failed to create server: {$errstr} ({$errno})\n");
        //     }
        // }

        // $logger = new ConsoleLogger($output->getErrorOutput());
        // $command = new ServerDumpCommand(new DumpServer('127.0.0.1:9912', $logger));

        // while ($conn = stream_socket_accept($this->socket, -1)) {
        //     $data = stream_get_contents($conn);
        //     echo $data;
        //     fclose($conn);
        // }

        // fclose($socket);
    }
}
