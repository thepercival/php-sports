<?php

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;
use Sports\Team;
use Sports\Game\Event as GameEvent;

class Substitution implements GameEvent
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
    private $out;
    /**
     * @var GameParticipation
     */
    private $in;
    
    public function __construct( int $minute, GameParticipation $out, GameParticipation $in )
    {
        $this->minute = $minute;
        $this->out = $out;
        $this->out->setEndMinute( $minute );
        $this->in = $in;
        $this->in->setBeginMinute( $minute );
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

    public function getOut(): GameParticipation
    {
        return $this->out;
    }

    public function getIn(): GameParticipation
    {
        return $this->in;
    }

    public function getTeam(): Team {
        return $this->getOut()->getPlayer()->getTeam();
    }
}
