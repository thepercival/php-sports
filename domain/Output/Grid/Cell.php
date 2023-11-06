<?php

declare(strict_types=1);

namespace Sports\Output\Grid;

use SportsHelpers\Output\Color;

final class Cell implements \Stringable
{
    protected Color|null $color = null;

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

    public function getColor(): Color|null
    {
        return $this->color;
    }

    public function setColor(Color|null $color): void
    {
        $this->color = $color;
    }

    public function __toString(): string
    {
        return $this->color === null ? $this->value : Color::getColored($this->color, $this->value);
    }
}
