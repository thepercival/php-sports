<?php
declare(strict_types=1);

namespace Sports\Score;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round;
use Sports\Sport as SportBase;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class Config extends Identifiable
{
    protected Config|null $next = null;

    const UPWARDS = 1;
    const DOWNWARDS = 2;

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

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function isLast(): bool
    {
        return !$this->hasNext();
    }

    public function getCalculate(): Config|null
    {
        $first = $this->getFirst();
        $nextAfterFirst = $first->getNext();
        if ($nextAfterFirst !== null && $nextAfterFirst->getEnabled()) {
            return $nextAfterFirst;
        }
        return $this;
    }

    public function useSubScore(): bool
    {
        return ($this !== $this->getCalculate());
    }
}
