<?php
declare(strict_types=1);

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;
use Sports\Team;
use Sports\Game\Event as GameEvent;
use SportsHelpers\Identifiable;

class Substitution extends Identifiable implements GameEvent
{
    private int $minute;
    private GameParticipation $out;
    private GameParticipation $in;
    
    public function __construct(int $minute, GameParticipation $out, GameParticipation $in)
    {
        $this->minute = $minute;
        $this->out = $out;
        $this->out->setEndMinute($minute);
        $this->in = $in;
        $this->in->setBeginMinute($minute);
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

    public function getTeam(): Team
    {
        return $this->getOut()->getPlayer()->getTeam();
    }
}
