<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug;

use PhpTui\Term\Event;
use PhpTui\Tui\Widget\Widget;
use React\EventLoop\LoopInterface;

interface Component
{
    public function build(?LoopInterface $loop = null): Widget;

    public function handle(Event $event): void;
}
