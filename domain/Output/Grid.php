<?php

declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Output\Grid\Cell;
use SportsHelpers\Output\Color;
use SportsHelpers\Output\OutputAbstract;

final class Grid extends OutputAbstract
{
    /**
     * @var array<int, array<int, Cell>>
     */
    private array $grid = [];
    public function __construct(protected int $height, protected int $width, LoggerInterface $logger)
    {
        parent::__construct($logger);
        for ($i = 0 ; $i < $this->height ; $i++) {
            $this->grid[$i] = [];
            for ($j = 0 ; $j < $this->width ; $j++) {
                $this->grid[$i][$j] = new Cell(' ');
            }
        }
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getCell(Coordinate $coordinate): Cell {
        if( !array_key_exists($coordinate->getY(), $this->grid) ) {
            throw new \Exception('no column found for coordinate ' . ((string)$coordinate));
        }
        $rows = $this->grid[$coordinate->getY()];
        if( !array_key_exists($coordinate->getX(), $rows) ) {
            throw new \Exception('no row found for coordinate ' . ((string)$coordinate));
        }
        return $rows[$coordinate->getX()];
    }

    public function setColor(Coordinate $coordinate, Color|null $color): void
    {
        $this->getCell($coordinate)->setColor($color);
    }

    public function setToRight(Coordinate $coordinate, string $char): Coordinate
    {
        $this->getCell($coordinate)->setValue($char);
        return $coordinate->incrementX();
    }

    public function setToLeft(Coordinate $coordinate, string $char): Coordinate
    {
        $this->getCell($coordinate)->setValue($char);
        return $coordinate->decrementX();
    }

    public function setVertAwayFromOrigin(Coordinate $coordinate, string $char): Coordinate
    {
        $this->getCell($coordinate)->setValue($char);
        return $coordinate->incrementY();
    }

    public function setVertToOrigin(Coordinate $coordinate, string $char): Coordinate
    {
        $this->getCell($coordinate)->setValue($char);
        return $coordinate->decrementY();
    }

    public function output(): void
    {
        foreach ($this->grid as $line) {
            $string = '';
            foreach ($line as $cell) {
                $string .= (string)$cell;
            }
            $this->logger->info($string);
        }
    }
}
