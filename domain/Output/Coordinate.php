<?php
declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competition\Field;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\NameService;
use Sports\Place;
use SportsHelpers\Output as OutputBase;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competition\Referee;
use Sports\Score\Config\Service as ScoreConfigService;

final class Coordinate
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
}

