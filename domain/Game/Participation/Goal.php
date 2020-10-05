<?php

namespace Sports\Game\Participation;

use Sports\Game\Participation as GameParticipation;

class Goal
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var GameParticipation
     */
    private $gameParticipation;
    /**
     * @var int
     */
    private $minute;
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
    
    public function __construct(GameParticipation $gameParticipation, int $minute )
    {
        $this->setGameParticipation($gameParticipation);
        $this->minute = $minute;
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

    public function getGameParticipation(): GameParticipation
    {
        return $this->gameParticipation;
    }

    protected function setGameParticipation(GameParticipation $gameParticipation)
    {
        if ($this->gameParticipation === null and !$gameParticipation->getGoals()->contains($this)) {
            $gameParticipation->getGoals()->add($this) ;
        }
        $this->gameParticipation = $gameParticipation;
    }

    public function getMinute(): int
    {
        return $this->minute;
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

    protected function setAssistGameParticipation(GameParticipation $assistGameParticipation)
    {
        if ($this->assistGameParticipation === null and !$assistGameParticipation->getAssists()->contains($this)) {
            $assistGameParticipation->getAssists()->add($this) ;
        }
        $this->assistGameParticipation = $assistGameParticipation;
    }
}
