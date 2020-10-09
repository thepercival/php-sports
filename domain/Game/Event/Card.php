<?php

namespace Sports\Game\Event;

use Sports\Game\Participation as GameParticipation;

class Card
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
     * @var int
     */
    private $type;

    public function __construct(int $minute, GameParticipation $gameParticipation, int $type )
    {
        $this->setGameParticipation($gameParticipation);
        $this->minute = $minute;
        $this->type = $type;
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
        if ($this->gameParticipation === null and !$gameParticipation->getCards()->contains($this)) {
            $gameParticipation->getCards()->add($this) ;
        }
        $this->gameParticipation = $gameParticipation;
    }



    public function getType(): int
    {
        return $this->type;
    }
}
