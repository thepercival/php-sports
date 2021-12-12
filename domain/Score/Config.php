<?php

declare(strict_types=1);

namespace Sports\Score;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round;
use SportsHelpers\Identifiable;

class Config extends Identifiable
{
    protected Config|null $next = null;

    public const UPWARDS = 1;
    public const DOWNWARDS = 2;

    public function __construct(
        protected CompetitionSport $competitionSport,
        protected Round $round,
        protected int $direction,
        protected int $maximum,
        protected bool $enabled,
        protected Config|null $previous = null
    ) {
        $this->round->getScoreConfigs()->add($this);
        if ($this->previous !== null) {
            $this->previous->setNext($this);
        }
    }

    public function getPrevious(): Config|null
    {
        return $this->previous;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function isFirst(): bool
    {
        return !$this->hasPrevious();
    }

    public function getNext(): Config|null
    {
        return $this->next;
    }

    public function setNext(Config $scoreConfig = null): void
    {
        $this->next = $scoreConfig;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function getFirst(): Config
    {
        $parent = $this->getPrevious();
        if ($parent !== null) {
            return $parent->getFirst();
        }
        return $this;
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function setDirection(int $direction): void
    {
        if ($direction !== Config::UPWARDS and $direction !== Config::DOWNWARDS) {
            throw new \InvalidArgumentException("de richting heeft een onjuiste waarde", E_ERROR);
        }
        $this->direction = $direction;
    }

    public function getMaximum(): int
    {
        return $this->maximum;
    }

    public function setMaximum(int $maximum): void
    {
        $this->maximum = $maximum;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isLast(): bool
    {
        return !$this->hasNext();
    }

    public function getCalculate(): Config
    {
        $firstNext = $this->getFirst()->getNext();
        return $firstNext !== null && $firstNext->getEnabled() ? $firstNext : $this;
    }

    public function useSubScore(): bool
    {
        return ($this !== $this->getCalculate());
    }
}
