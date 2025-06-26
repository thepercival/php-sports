<?php

declare(strict_types=1);

namespace Sports\Score;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Round;
use SportsHelpers\Identifiable;

final class ScoreConfig extends Identifiable
{
    protected ScoreConfig|null $next = null;

    public const int UPWARDS = 1;
    public const int DOWNWARDS = 2;

    public function __construct(
        protected CompetitionSport $competitionSport,
        protected Round            $round,
        protected int              $direction,
        protected int $maximum,
        protected bool $enabled,
        protected ScoreConfig|null $previous = null
    ) {
        $this->round->getScoreConfigs()->add($this);
        if ($this->previous !== null) {
            $this->previous->setNext($this);
        }
    }

    public function getPrevious(): ScoreConfig|null
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

    public function getNext(): ScoreConfig|null
    {
        return $this->next;
    }

    public function setNext(ScoreConfig $scoreConfig = null): void
    {
        $this->next = $scoreConfig;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function getFirst(): ScoreConfig
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

    public function getCompetitionSportId(): string|int|null {
        return $this->competitionSport->id;
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
        if ($direction !== ScoreConfig::UPWARDS and $direction !== ScoreConfig::DOWNWARDS) {
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

    public function getCalculate(): ScoreConfig
    {
        $firstNext = $this->getFirst()->getNext();
        return $firstNext !== null && $firstNext->getEnabled() ? $firstNext : $this;
    }

    public function useSubScore(): bool
    {
        return ($this !== $this->getCalculate());
    }
}
