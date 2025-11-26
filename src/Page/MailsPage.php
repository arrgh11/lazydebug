<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug\Page;

use Arrgh11\LazyDebug\Component;
use PhpTui\Term\Event;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Widget;
use React\EventLoop\LoopInterface;

final class MailsPage implements Component
{
    public function build(?LoopInterface $loop = null): Widget
    {
        return GridWidget::default()
            ->constraints(
                Constraint::min(3),
            )
            ->widgets(
                BlockWidget::default()
                    ->titles(Title::fromString('Mails (c: clear, ↑/↓: scroll)'))
                    ->borders(Borders::ALL)
                // ->widget(
                //     ListWidget::default()
                //         ->items(...$items)
                //         ->select($this->scrollState)
                // )
            )
        ;
    }

    public function handle(Event $event): void
    {
    }

}
