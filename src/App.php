<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug;

use Arrgh11\LazyDebug\Page;
use Arrgh11\LazyDebug\Services\DumpCollector;
use PhpTui\Term\Actions;
use PhpTui\Term\ClearType;
use PhpTui\Term\Event\CharKeyEvent;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;
use PhpTui\Term\KeyModifiers;
use PhpTui\Term\Terminal;
use PhpTui\Tui\Bridge\PhpTerm\PhpTermBackend as PhpTuiPhpTermBackend;
use PhpTui\Tui\Display\Backend;
use PhpTui\Tui\Display\Display;
use PhpTui\Tui\DisplayBuilder;
use PhpTui\Tui\Extension\Bdf\BdfExtension;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\TabsWidget;
use PhpTui\Tui\Extension\ImageMagick\ImageMagickExtension;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Command\ServerDumpCommand;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ContextProvider\CliContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\ServerDumper;
use Symfony\Component\VarDumper\Server\DumpServer;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

/**
 * A simple, synchronous, application which aims to demo
 * all of the supported functionality.
 *
 * The demo app introduces Component interface to create UI elements/pages
 * which are responsible for:
 *
 * - Building the widget that will be displayed
 * - Handling events
 * - Maintaining their own state
 *
 * Taking this further it would also make sense to introduce a event bus to allow
 * components to propagate state and communicate with eachother.
 */
final class App
{
    /**
     * @param array<string,Component> $pages
     */
    private function __construct(
        private Terminal $terminal,
        private Display $display,
        private ActivePage $activePage,
        private array $pages,
        private LoopInterface $loop,
        private ?SocketServer $socket = null,
    ) {

    }

    public static function new(?Terminal $terminal = null, ?Backend $backend = null): self
    {
        $terminal = $terminal ?? Terminal::new();
        $pages = [];

        // build up an exhaustive set of pages
        foreach (ActivePage::cases() as $case) {
            $pages[$case->name] = match ($case) {
                ActivePage::Logs => new Page\LogsPage(),
                ActivePage::Dumps => new Page\DumpsPage(),
                ActivePage::Mails => new Page\MailsPage(),
            };
        }

        $display = DisplayBuilder::default($backend ?? PhpTuiPhpTermBackend::new($terminal))
            ->addExtension(new ImageMagickExtension())
            ->addExtension(new BdfExtension())
            ->build();

        $loop = Loop::get();

        return new self(
            $terminal,
            $display,
            ActivePage::Logs,
            $pages,
            $loop
        );
    }

    public function run(): int
    {
        try {
            // enable "raw" mode to remove default terminal behavior (e.g.
            // echoing key presses)
            // hide the cursor
            $this->terminal->execute(Actions::cursorHide());
            // switch to the "alternate" screen so that we can return the user where they left off
            $this->terminal->execute(Actions::alternateScreenEnable());
            $this->terminal->execute(Actions::enableMouseCapture());
            $this->terminal->enableRawMode();

            return $this->doRun();
        } catch (Throwable $err) {
            $this->terminal->disableRawMode();
            $this->terminal->execute(Actions::disableMouseCapture());
            $this->terminal->execute(Actions::alternateScreenDisable());
            $this->terminal->execute(Actions::cursorShow());
            $this->terminal->execute(Actions::clear(ClearType::All));

            throw $err;
        }
    }

    private function doRun(): int
    {

        $this->loop->addPeriodicTimer(0.05, function () {
            // handle events sent to the terminal
            while (null !== $event = $this->terminal->events()->next()) {
                if ($event instanceof CharKeyEvent) {
                    if ($event->modifiers === KeyModifiers::NONE) {
                        if ($event->char === 'q') {
                            // $this->socket?->close();
                            $this->loop->stop();
                        }
                        if ($event->char === '1' || $event->char === 'l') {
                            $this->activePage = ActivePage::Logs;
                        }
                        if ($event->char === '2' || $event->char === 'd') {
                            $this->activePage = ActivePage::Dumps;
                        }
                        if ($event->char === '3' || $event->char === 'm') {
                            $this->activePage = ActivePage::Mails;
                        }
                    }
                }
                if ($event instanceof CodedKeyEvent) {
                    if ($event->code === KeyCode::Tab) {
                        $this->activePage = $this->activePage->next();
                    }
                    if ($event->code === KeyCode::BackTab) {
                        $this->activePage = $this->activePage->previous();
                    }
                }
                $this->activePage()->handle($event);
            }

            $this->display->draw($this->layout());
        });

        $this->loop->run();

        $this->terminal->disableRawMode();
        $this->terminal->execute(Actions::cursorShow());
        $this->terminal->execute(Actions::alternateScreenDisable());
        $this->terminal->execute(Actions::disableMouseCapture());

        return 0;
    }

    private function layout(): Widget
    {
        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::min(3),
                Constraint::min(1),
            )
            ->widgets(
                $this->header(),
                $this->activePage()->build($this->loop)
            );
    }

    private function activePage(): Component
    {
        return $this->pages[$this->activePage->name];
    }

    private function header(): Widget
    {
        return BlockWidget::default()
                ->borders(Borders::ALL)->style(Style::default()->blue())
                ->widget(
                    TabsWidget::fromTitles(
                        Line::parse('<fg=red>[q]</>uit'),
                        ...array_reduce(ActivePage::cases(), function (array $lines, ActivePage $page) {
                            $lines[] = Line::parse(sprintf('%s', $page->navItem()->label));

                            return $lines;
                        }, []),
                    )
                    ->select($this->activePage->index() + 1)
                    ->highlightStyle(Style::default()->white()->onDarkGray())
                );
    }
}
