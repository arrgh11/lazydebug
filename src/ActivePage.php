<?php

declare(strict_types=1);

namespace Arrgh11\LazyDebug;

use RuntimeException;

enum ActivePage
{
    case Logs;
    case Dumps;
    case Mails;

    public function navItem(): NavItem
    {
        return match ($this) {
            ActivePage::Logs => new NavItem('1', '<fg=red>[l]</>ogs'),
            ActivePage::Dumps => new NavItem('2', '<fg=red>[d]</>umps'),
            ActivePage::Mails => new NavItem('3', '<fg=red>[m]</>ails'),
        };
    }

    public function next(): self
    {
        foreach (self::cases() as $i => $case) {
            if ($case === $this) {
                return self::cases()[($i + 1) % count(self::cases())];
            }
        }

        throw new RuntimeException('should not happen!');
    }
    public function previous(): self
    {
        $cases = self::cases();
        foreach (self::cases() as $i => $case) {
            if ($case === $this) {
                return $cases[($i - 1) < 0 ? count($cases) - 1 : $i - 1];
            }
        }

        throw new RuntimeException('should not happen!');
    }

    public function index(): int
    {
        $search = array_search($this, self::cases(), true);

        return $search ? $search : 0;
    }
}
