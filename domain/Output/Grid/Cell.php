<?php

declare(strict_types=1);

namespace Sports\Output\Grid;

use SportsHelpers\Output\Color;

final class Cell implements \Stringable
{
    use Color;
    protected int $color = 0;

    public function __construct(protected string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getColor(): int
    {
        return $this->color;
    }

    public function setColor(int $color): void
    {
        $this->color = $color;
    }

    public function __toString()
    {
        return $this->color === 0 ? $this->value : $this->getColored($this->color, $this->value);
    }
}
