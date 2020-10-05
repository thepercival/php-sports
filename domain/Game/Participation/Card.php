<?php

namespace Sports\Game\Participation;

use Sports\Game\Participation as GameParticipation;

class Card
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
     * @var int
     */
    private $color;

    public function __construct(GameParticipation $gameParticipation, int $minute, int $color )
    {
        $this->setGameParticipation($gameParticipation);
        $this->minute = $minute;
        $this->color = $color;
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
        if ($this->gameParticipation === null and !$gameParticipation->getCards()->contains($this)) {
            $gameParticipation->getCards()->add($this) ;
        }
        $this->gameParticipation = $gameParticipation;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getColor(): int
    {
        return $this->color;
    }
}
