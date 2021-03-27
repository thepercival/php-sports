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

    public function drawHorizontal(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setHorizontal($coordinate, $char);
        }
        return $coordinate->decrementX();
    }

    public function drawHorizontalLine(Coordinate $coordinate, int $length, string $value = '-', int $color = 0): Coordinate
    {
        return $this->drawHorizontal($coordinate, $this->initString($length, $value), $color);
    }

    public function drawVertical(Coordinate $coordinate, string $value, int $color = 0): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $this->grid->setColor($coordinate, $color);
            $coordinate = $this->grid->setVertical($coordinate, $char);
        }
        return $coordinate->decrementY();
    }

    public function drawVerticalLine(Coordinate $coordinate, int $length, string $value = '|', int $color = 0): Coordinate
    {
        return $this->drawVertical($coordinate, $this->initString($length, $value), $color);
    }

    public function drawHorizontalCell(Coordinate $coordinate, string $text, int $width, int $align, int $color = 0): Coordinate
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
        return $this->drawHorizontal($coordinate, $text, $color);
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
