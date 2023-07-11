<?php

declare(strict_types=1);

namespace Sports\Output;

final class Coordinate implements \Stringable
{
    public function __construct(protected int $x, protected int $y)
    {
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function addX(int $x): Coordinate
    {
        return new Coordinate($this->getX() + $x, $this->getY());
    }

    public function addY(int $y): Coordinate
    {
        return new Coordinate($this->getX(), $this->getY() + $y);
    }

    public function add(int $x, int $y): Coordinate
    {
        return new Coordinate($this->getX() + $x, $this->getY() + $y);
    }

    public function incrementX(): Coordinate
    {
        return new Coordinate($this->getX() + 1, $this->getY());
    }

    public function incrementY(): Coordinate
    {
        return new Coordinate($this->getX(), $this->getY() + 1);
    }

    public function decrementX(): Coordinate
    {
        return new Coordinate($this->getX() - 1, $this->getY());
    }

    public function decrementY(): Coordinate
    {
        return new Coordinate($this->getX(), $this->getY() - 1);
    }

    public function __toString(): string
    {
        return $this->getX() . ',' . $this->getY();
    }
}
