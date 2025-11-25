<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug;

final class NavItem
{
    public function __construct(public string $shortcut, public string $label)
    {
    }
}
