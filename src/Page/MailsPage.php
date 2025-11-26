<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug\Page;

use Arrgh11\LazyDebug\Component;
use PhpTui\Term\Event;
use PhpTui\Tui\Extension\Core\Widget\Block\Padding;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\List\ListItem;
use PhpTui\Tui\Extension\Core\Widget\ListWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Text\Text;
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
                Constraint::min(3),
            )
            ->widgets(
            )
        ;
    }

    public function handle(Event $event): void
    {
    }
}
