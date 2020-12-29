<?php

namespace Sports\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport as SportBase;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class ScoreConfig extends Identifiable
{
    protected $sportDep;
    protected CompetitionSport $competitionSport;
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var ScoreConfig|null
     */
    protected $previous;
    /**
     * @var ScoreConfig
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

    public function __construct(CompetitionSport $competitionSport, RoundNumber $roundNumber, ScoreConfig $previous = null)
    {
        $this->competitionSport = $competitionSport;
        $this->setRoundNumber($roundNumber);
        $this->setPrevious($previous);
    }

    /**
     * @return ScoreConfig
     */
    public function getPrevious(): ?ScoreConfig
    {
        return $this->previous;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setPrevious(ScoreConfig $scoreConfig = null)
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
     * @return ScoreConfig
     */
    public function getNext(): ?ScoreConfig
    {
        return $this->next;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setNext(ScoreConfig $scoreConfig = null)
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
     * @return ScoreConfig
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

    public function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }

    protected function setRoundNumber(RoundNumber $roundNumber)
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->getSportScoreConfigs()->add($this);
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
        if ($direction !== ScoreConfig::UPWARDS and $direction !== ScoreConfig::DOWNWARDS) {
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

    public function getCalculate(): ScoreConfig
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
