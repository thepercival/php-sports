<?php

declare(strict_types=1);

namespace Sports\Output\Grid;

use Sports\Output\Coordinate;
use Sports\Output\Grid;
use SportsHelpers\Output\Color;

final class Drawer
{
    public function __construct(protected Grid $grid)
    {
    }

    public function getGridWidth(): int
    {
        return $this->grid->getWidth();
    }

    public function drawToRight(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = str_split($value);
        $char = array_shift($valueAsArray);
        while ($char !== null) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setToRight($coordinate, $char);
            $char = array_shift($valueAsArray);
        }
        return $coordinate->decrementX();
    }

    public function drawLineToRight(
        Coordinate $coordinate,
        int $length,
        string $value = '-',
        Color|null $color = null
    ): Coordinate {
        return $this->drawToRight($coordinate, $this->initString($length, $value), $color);
    }

    public function drawToLeft(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = str_split($value);
        $char = array_shift($valueAsArray);
        while ($char !== null) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setToLeft($coordinate, $char);
            $char = array_shift($valueAsArray);
        }
        return $coordinate->incrementX();
    }

    public function drawLineToLeft(
        Coordinate $coordinate,
        int $length,
        string $value = '-',
        Color|null $color = null
    ): Coordinate {
        return $this->drawToLeft($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertAwayFromOrigin(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = str_split($value);
        $char = array_shift($valueAsArray);
        while ($char !== null) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setVertAwayFromOrigin($coordinate, $char);
            $char = array_shift($valueAsArray);
        }
        return $coordinate->decrementY();
    }

    public function drawVertLineAwayFromOrigin(
        Coordinate $coordinate,
        int $length,
        string $value = '|',
        Color|null $color = null
    ): Coordinate {
        return $this->drawVertAwayFromOrigin($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertToOrigin(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = str_split($value);
        $char = array_shift($valueAsArray);
        while ($char !== null) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setVertToOrigin($coordinate, $char);
            $char = array_shift($valueAsArray);
        }
        return $coordinate->decrementY();
    }

    public function drawVertLineToOrigin(
        Coordinate $coordinate,
        int $length,
        string $value = '|',
        Color|null $color = null
    ): Coordinate {
        return $this->drawVertToOrigin($coordinate, $this->initString($length, $value), $color);
    }

    public function drawCellToRight(
        Coordinate $coordinate,
        string $text,
        int $width,
        Align $align,
        Color|null $color = null
    ): Coordinate {
        $char = ' ';
        if (strlen($text) > $width) {
            $text = substr($text, 0, $width);
        }
        if ($align === Align::Center) {
            $align = Align::Left;
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
                $align = $align === Align::Left ? Align::Right : Align::Left;
            }
        } else {
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
            }
        }
        return $this->drawToRight($coordinate, $text, $color);
    }

    public function drawRectangle(Coordinate $origin, Coordinate $size): void
    {
        $topRight = $this->drawLineToRight($origin, $size->getX());
        $bottomRight = $this->drawVertLineAwayFromOrigin($topRight, $size->getY());
        $bottomLeft = $this->drawLineToLeft($bottomRight->decrementX(), $size->getX() - 1);
        $this->drawVertLineToOrigin($bottomLeft, $size->getY());
    }

    public function initString(int $length, string $char = ' '): string
    {
        $retVal = '';
        while ($length--) {
            $retVal .= $char;
        }
        return $retVal;
    }

    public function addToString(string $text, string $char, Align $side): string
    {
        if ($side === Align::Right) {
            return $text . $char;
        }
        return $char . $text;
    }
}
