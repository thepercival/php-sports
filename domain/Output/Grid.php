<?php
declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputBase;
use Stringable;

final class Grid extends OutputBase
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $grid = [];
    public function __construct(protected int $height, protected int $width, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        for ($i = 0 ; $i < $this->height ; $i++) {
            $this->grid[$i] = [];
            for ($j = 0 ; $j < $this->width ; $j++) {
                $this->grid[$i][$j] = ' ';
            }
        }
    }

    public function setHorizontal(Coordinate $coordinate, string $char): Coordinate {
        $this->grid[$coordinate->getY()][$coordinate->getX()] = $char;
        return $coordinate->incrementX();
    }

    public function setVertical(Coordinate $coordinate, string $char): Coordinate {
        $this->grid[$coordinate->getY()][$coordinate->getX()] = $char;
        return $coordinate->incrementY();
    }

    public function output(): void
    {
        foreach ($this->grid as $line) {
            $string = '';
            foreach ($line as $value) {
                $string .= $value;
            }
            $this->logger->info($string);
        }
    }
}
