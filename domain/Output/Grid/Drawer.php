<?php
declare(strict_types=1);

namespace Sports\Output\Grid;

use SportsHelpers\Output\Color;
use Sports\Output\Coordinate;
use Sports\Output\Grid;

final class Drawer
{
    public const ALIGN_LEFT = 1;
    public const ALIGN_CENTER = 2;
    public const ALIGN_RIGHT = 3;

    use Color;

    public function __construct(protected Grid $grid)
    {
    }

    public function drawToRight(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setToRight($coordinate, $char);
        }
        return $coordinate->decrementX();
    }

    public function drawLineToRight(Coordinate $coordinate, int $length, string $value = '-', int $color = 0): Coordinate
    {
        return $this->drawToRight($coordinate, $this->initString($length, $value), $color);
    }

    public function drawToLeft(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setToLeft($coordinate, $char);
        }
        return $coordinate->incrementX();
    }

    public function drawLineToLeft(Coordinate $coordinate, int $length, string $value = '-', int $color = 0): Coordinate
    {
        return $this->drawToLeft($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertAwayFromOrigin(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setVertAwayFromOrigin($coordinate, $char);
        }
        return $coordinate->decrementY();
    }

    public function drawVertLineAwayFromOrigin(Coordinate $coordinate, int $length, string $value = '|', int $color = 0): Coordinate
    {
        return $this->drawVertAwayFromOrigin($coordinate, $this->initString($length, $value), $color);
    }
    
    public function drawVertToOrigin(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setVertToOrigin($coordinate, $char);
        }
        return $coordinate->decrementY();
    }

    public function drawVertLineToOrigin(Coordinate $coordinate, int $length, string $value = '|', int $color = 0): Coordinate
    {
        return $this->drawVertToOrigin($coordinate, $this->initString($length, $value), $color);
    }

    public function drawCellToRight(Coordinate $coordinate, string $text, int $width, int $align, int $color = 0): Coordinate
    {
        $char = ' ';
        if (strlen($text) > $width) {
            $text = substr($text, 0, $width);
        }
        if ($align === self::ALIGN_CENTER) {
            $align = self::ALIGN_LEFT;
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
                $align = $align === self::ALIGN_LEFT ? self::ALIGN_RIGHT : self::ALIGN_LEFT;
            }
        } else {
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
            }
        }
        return $this->drawToRight($coordinate, $text, $color);
    }

    public function drawRectangle(Coordinate $origin, Coordinate $size): void {
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

    public function addToString(string $text, string $char, int $side): string
    {
        if ($side === self::ALIGN_RIGHT) {
            return $text . $char;
        }
        return $char . $text;
    }
}
