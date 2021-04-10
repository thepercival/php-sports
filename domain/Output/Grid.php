<?php
declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Output\Grid\Cell;
use SportsHelpers\Output as OutputBase;

final class Grid extends OutputBase
{
    /**
     * @var array<int, array<int, Cell>>
     */
    private array $grid = [];
    public function __construct(protected int $height, protected int $width, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        for ($i = 0 ; $i < $this->height ; $i++) {
            $this->grid[$i] = [];
            for ($j = 0 ; $j < $this->width ; $j++) {
                $this->grid[$i][$j] = new Cell(' ');
            }
        }
    }

    public function setColor(Coordinate $coordinate, int $color): void
    {
        $this->grid[$coordinate->getY()][$coordinate->getX()]->setColor($color);
    }

    public function setToRight(Coordinate $coordinate, string $char): Coordinate
    {
        $this->grid[$coordinate->getY()][$coordinate->getX()]->setValue($char);
        return $coordinate->incrementX();
    }

    public function setToLeft(Coordinate $coordinate, string $char): Coordinate
    {
        $this->grid[$coordinate->getY()][$coordinate->getX()]->setValue($char);
        return $coordinate->decrementX();
    }

    public function setVertAwayFromOrigin(Coordinate $coordinate, string $char): Coordinate
    {
        $this->grid[$coordinate->getY()][$coordinate->getX()]->setValue($char);
        return $coordinate->incrementY();
    }

    public function setVertToOrigin(Coordinate $coordinate, string $char): Coordinate
    {
        $this->grid[$coordinate->getY()][$coordinate->getX()]->setValue($char);
        return $coordinate->decrementY();
    }

    public function output(): void
    {
        foreach ($this->grid as $line) {
            $string = '';
            foreach ($line as $cell) {
                $string .= $cell;
            }
            $this->logger->info($string);
        }
    }
}
