<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use PhpTui\Tui\DisplayBuilder;
use PhpTui\Tui\Extension\Core\Widget\TabsWidget;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Text\Span;

$display = DisplayBuilder::default()
  ->fullscreen()
  ->build();

$display->clear();

$display->draw(
    TabsWidget::default()
        ->titles(
            Line::fromString('Tab 0'),
            Line::fromString('Tab 1'),
            Line::fromString('Tab 3'),
        )
        ->select(0)
        ->highlightStyle(Style::default()->white()->onRed())
        ->divider(Span::fromString('|'))
);
