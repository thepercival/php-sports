<?php
declare(strict_types=1);

namespace Sports\Game\Event;

use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Participation as GameParticipation;
use Sports\Team;
use Sports\Game\Event as GameEvent;
use SportsHelpers\Identifiable;
use SuperElf\PersonStats as PersonStatsBase;

class Goal extends Identifiable implements GameEvent
{
    public const FIELD = 1;
    public const PENALTY = 2;
    public const OWN = 4;

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

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getGameParticipation(): GameParticipation
    {
        return $this->gameParticipation;
    }

    protected function setGameParticipation(GameParticipation $gameParticipation): void
    {
        if ($this->gameParticipation === null and !$gameParticipation->getGoalsAndAssists()->contains($this)) {
            $gameParticipation->getGoalsAndAssists()->add($this) ;
        }
        $this->gameParticipation = $gameParticipation;
    }

    public function isType( int $type ): bool
    {
        if( $type == self::FIELD ) {
            return !$this->getOwn() && !$this->getPenalty();
        } elseif( $type == self::PENALTY ) {
            return $this->getPenalty();
        } elseif( $type == self::OWN ) {
            return $this->getOwn();
        }
        return false;
    }

    public function getOwn(): bool
    {
        return $this->own;
    }

    public function setOwn( bool $own ): void
    {
        $this->own = $own;
    }

    public function getPenalty(): bool
    {
        return $this->penalty;
    }

    public function setPenalty( bool $penalty ): void
    {
        $this->penalty = $penalty;
    }

    public function getAssistGameParticipation(): ?GameParticipation
    {
        return $this->assistGameParticipation;
    }

    public function setAssistGameParticipation(GameParticipation $assistGameParticipation): void
    {
        if ($this->assistGameParticipation === null and !$assistGameParticipation->getGoalsAndAssists()->contains($this)) {
            $assistGameParticipation->getGoalsAndAssists()->add($this) ;
        }
        $this->assistGameParticipation = $assistGameParticipation;
    }

    public function getTeam(): Team {
        return $this->getGameParticipation()->getPlayer()->getTeam();
    }
}
