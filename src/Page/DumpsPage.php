<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug\Page;

use Arrgh11\LazyDebug\Component;
use Arrgh11\LazyDebug\Services\DumpCollector;
use PhpTui\Term\Event;
use PhpTui\Term\KeyCode;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\CompositeWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\List\ListItem;
use PhpTui\Tui\Extension\Core\Widget\ListWidget;
use PhpTui\Tui\Extension\Core\Widget\Scrollbar\ScrollbarOrientation;
use PhpTui\Tui\Extension\Core\Widget\Scrollbar\ScrollbarState;
use PhpTui\Tui\Extension\Core\Widget\ScrollbarWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Widget;
use React\EventLoop\LoopInterface;

final class DumpsPage implements Component
{
    private DumpCollector $collector;
    private int $scrollState = 0;

    public function __construct()
    {
        $this->collector = new DumpCollector();
    }

    public function build(?LoopInterface $loop = null): Widget
    {
        // Check for new dump data on every build

        $dumps = $this->collector->getDumps();

        $items = empty($dumps)
                ? [ListItem::new(Text::fromString('Waiting for dumps on 127.0.0.1:9912...'))]
                : array_map(
                    fn (string $dump) => ListItem::new(Text::fromString($dump)),
                    $dumps
                );

        return GridWidget::default()
            ->constraints(
                Constraint::percentage(100),
            )
            ->widgets(
                // CompositeWidget::fromWidgets(
                //     BlockWidget::default()->borders(Borders::ALL)->titles(Title::fromString('Window 1')),
                //     ScrollbarWidget::default()->state(new ScrollbarState(20, 5, 5)),
                //     ScrollbarWidget::default()
                //   ->state(new ScrollbarState(20, 5, 5))
                //   ->orientation(ScrollbarOrientation::VerticalRight),
                // ),
                BlockWidget::default()
                    ->titles(Title::fromString('Dumps (c: clear, ↑/↓: scroll)'))
                    ->borders(Borders::ALL)
                    ->widget(
                        ListWidget::default()
                            ->items(...$items)
                            ->select($this->scrollState)
                    )
      );
    }

    public function handle(Event $event): void
    {
        if ($event instanceof Event\CharKeyEvent) {
            if ($event->char === 'c') {
                $this->collector->clearDumps();
                $this->scrollState = 0;
            }
        }

        if ($event instanceof Event\CodedKeyEvent) {
            $dumpCount = count($this->collector->getDumps());

            if ($event->code === KeyCode::Down) {
                $this->scrollState = min($this->scrollState + 1, max(0, $dumpCount - 1));
            }

            if ($event->code === KeyCode::Up) {
                $this->scrollState = max(0, $this->scrollState - 1);
            }

            if ($event->code === KeyCode::Enter) {
                //do something with the selected dump
                $dumps = $this->collector->getDumps();
                $selectedDump = $dumps[$this->scrollState] ?? null;
                dump($selectedDump);
            }

        }
    }
}
