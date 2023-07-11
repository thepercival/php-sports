<?php

declare(strict_types=1);

namespace Sports\Output\Grid;

use Sports\Output\Coordinate;
use Sports\Output\Grid;
use SportsHelpers\Output\Color;

final class Drawer
{
    public const HORIZONTAL_BORDER = '-';
    public const VERTICAL_BORDER = '|';

    public function __construct(protected Grid $grid)
    {
    }

    public function getGridWidth(): int
    {
        return $this->grid->getWidth();
    }

    public function drawToRight(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = mb_str_split($value);
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
        Color|null $color = null,
        string $value = self::HORIZONTAL_BORDER
    ): Coordinate {
        return $this->drawToRight($coordinate, $this->initString($length, $value), $color);
    }

    public function drawToLeft(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = mb_str_split($value);
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
        Color|null $color = null,
        string $value = self::HORIZONTAL_BORDER,
    ): Coordinate {
        return $this->drawToLeft($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertAwayFromOrigin(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = mb_str_split($value);
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
        Color|null $color = null,
        string $value = self::VERTICAL_BORDER
    ): Coordinate {
        return $this->drawVertAwayFromOrigin($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertToOrigin(Coordinate $coordinate, string $value, Color|null $color = null): Coordinate
    {
        $valueAsArray = mb_str_split($value);
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
        Color|null $color = null,
        string $value = self::VERTICAL_BORDER,
    ): Coordinate {
        return $this->drawVertToOrigin($coordinate, $this->initString($length, $value), $color);
    }

    public function drawCellToRight(
        Coordinate $coordinate,
        string $text,
        int $maxWidth,
        Align $align,
        Color|null $color = null
    ): Coordinate {
        $char = ' ';
        if (mb_strlen($text) > $maxWidth) {
            $text = mb_substr($text, 0, $maxWidth);
        }
        if ($align === Align::Center) {
            $align = Align::Left;
            while (mb_strlen($text) < $maxWidth) {
                $text = $this->addToString($text, $char, $align);
                $align = $align === Align::Left ? Align::Right : Align::Left;
            }
        } else {
            while (mb_strlen($text) < $maxWidth) {
                $text = $this->addToString($text, $char, $align);
            }
        }
        return $this->drawToRight($coordinate, $text, $color);
    }

    public function drawRectangle(Coordinate $origin, Coordinate $size, Color $color = null): void
    {
        $topRight = $this->drawLineToRight($origin, $size->getX(), $color);
        $bottomRight = $this->drawVertLineAwayFromOrigin($topRight, $size->getY(), $color);
        $bottomLeft = $this->drawLineToLeft($bottomRight->decrementX(), $size->getX() - 1, $color);
        $this->drawVertLineToOrigin($bottomLeft, $size->getY(), $color);
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
            return $char . $text;
        }
        return $text . $char;
    }
}
