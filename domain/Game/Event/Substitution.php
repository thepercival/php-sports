<?php

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;

class Substitution
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
        $out->setEndMinute( $minute );
        $in->setBeginMinute( $minute );
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
}
