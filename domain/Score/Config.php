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
    protected $sportDep;
    protected CompetitionSport $competitionSport;
    protected Round $round;
    protected $roundNumberDep;
    /**
     * @var Config|null
     */
    protected $previous;
    /**
     * @var Config
     */
    protected $next;
    /**
     * @var int
     */
    protected $direction;
    /**
     * @var int
     */
    protected $maximum;
    /**
     * @var bool
     */
    protected $enabled;

    const UPWARDS = 1;
    const DOWNWARDS = 2;

    public function __construct(CompetitionSport $competitionSport, Round $round, Config $previous = null)
    {
        $this->competitionSport = $competitionSport;
        $this->setRound($round);
        $this->setPrevious($previous);
    }

    /**
     * @return Config
     */
    public function getPrevious(): ?Config
    {
        return $this->previous;
    }

    /**
     * @param Config $scoreConfig
     */
    public function setPrevious(Config $scoreConfig = null)
    {
        $this->previous = $scoreConfig;
        if ($this->previous !== null) {
            $this->previous->setNext($this);
        }
    }

    /**
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return !$this->hasPrevious();
    }

    /**
     * @return Config
     */
    public function getNext(): ?Config
    {
        return $this->next;
    }

    /**
     * @param Config $scoreConfig
     */
    public function setNext(Config $scoreConfig = null)
    {
        $this->next = $scoreConfig;
    }

    /**
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    /**
     * @return Config
     */
    public function getFirst()
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

    protected function setRound(Round $round)
    {
        $this->round = $round;
        $this->round->getScoreConfigs()->add($this);
    }

    /**
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @param int $direction
     */
    public function setDirection(int $direction)
    {
        if ($direction !== Config::UPWARDS and $direction !== Config::DOWNWARDS) {
            throw new \InvalidArgumentException("de richting heeft een onjuiste waarde", E_ERROR);
        }
        $this->direction = $direction;
    }

    /**
     * @return int
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum(int $maximum)
    {
        $this->maximum = $maximum;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    public function isLast()
    {
        return !$this->hasNext();
    }

    public function getCalculate(): Config
    {
        $first = $this->getFirst();
        if ($first->hasNext() && $first->getNext()->getEnabled()) {
            return $first->getNext();
        }
        return $this;
    }

    public function useSubScore(): bool
    {
        return ($this !== $this->getCalculate());
    }
}
