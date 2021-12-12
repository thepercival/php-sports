<?php

declare(strict_types=1);

namespace Sports\Priority;

interface Prioritizable
{
    public function getPriority(): int;
    public function setPriority(int $priority): void;
}
