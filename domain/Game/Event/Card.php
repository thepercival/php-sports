<?php
declare(strict_types=1);

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;

use Sports\Game\Event as GameEvent;
use Sports\Team;
use SportsHelpers\Identifiable;

class Card extends Identifiable implements GameEvent
{
    public function __construct(private int $minute, private GameParticipation $gameParticipation, private int $type)
    {
        if (!$gameParticipation->getCards()->contains($this)) {
            $gameParticipation->getCards()->add($this) ;
        }
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getGameParticipation(): GameParticipation
    {
        return $this->gameParticipation;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTeam(): Team
    {
        return $this->getGameParticipation()->getPlayer()->getTeam();
    }
}
