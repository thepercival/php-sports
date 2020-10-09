<?php

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;

class Goal
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var int
     */
    private $minute;
    /**
     * @var GameParticipation
     */
    private $gameParticipation;
    /**
     * @var bool
     */
    private $own;
    /**
     * @var bool
     */
    private $penalty;
    /**
     * @var GameParticipation
     */
    private $assistGameParticipation;
    
    public function __construct(int $minute, GameParticipation $gameParticipation )
    {
        $this->minute = $minute;
        $this->setGameParticipation($gameParticipation);
        $this->own = false;
        $this->penalty = false;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getGameParticipation(): GameParticipation
    {
        return $this->gameParticipation;
    }

    protected function setGameParticipation(GameParticipation $gameParticipation)
    {
        if ($this->gameParticipation === null and !$gameParticipation->getGoalsAndAssists()->contains($this)) {
            $gameParticipation->getGoalsAndAssists()->add($this) ;
        }
        $this->gameParticipation = $gameParticipation;
    }

    public function getOwn(): bool
    {
        return $this->own;
    }

    public function setOwn( bool $own )
    {
        $this->own = $own;
    }

    public function getPenalty(): bool
    {
        return $this->penalty;
    }

    public function setPenalty( bool $penalty )
    {
        $this->penalty = $penalty;
    }

    public function getAssistGameParticipation(): GameParticipation
    {
        return $this->assistGameParticipation;
    }

    public function setAssistGameParticipation(GameParticipation $assistGameParticipation)
    {
        if ($this->assistGameParticipation === null and !$assistGameParticipation->getGoalsAndAssists()->contains($this)) {
            $assistGameParticipation->getGoalsAndAssists()->add($this) ;
        }
        $this->assistGameParticipation = $assistGameParticipation;
    }
}
