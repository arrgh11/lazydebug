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

final class LogsPage implements Component
{
    /** @var Event[] */
    private array $events = [];

    public function build(): Widget
    {
        return GridWidget::default()
            ->constraints(
                Constraint::min(3),
                Constraint::min(3),
            )
            ->widgets(
                BlockWidget::default()
                    ->padding(Padding::left(1)),
                // ->widget(
                //     ParagraphWidget::fromLines(
                //         Line::parse('Welcome to the <fg=white;options=bold>PHP-TUI 🐘</> demo application.'),
                //         Line::parse('Use the <fg=#ffa500>tab</> to go to the next page and <fg=#ffa500>shift-tab</> to go to the previous page.'),
                //         Line::parse('<fg=white>Below you can see a log of all the input events, try moving the mouse!</> 🐭'),
                //     ),
                // ),
                BlockWidget::default()
                    ->titles(Title::fromString('Logs'))
                    ->borders(Borders::ALL)
                    ->widget(
                        ListWidget::default()
                            ->items(...array_map(fn (Event $event) => ListItem::new(Text::fromString($event->__toString())), $this->events))
                    )
            )
        ;
    }

    public function handle(Event $event): void
    {
        array_unshift($this->events, $event);
    }
}
