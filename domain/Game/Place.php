<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-1-19
 * Time: 11:32
 */

namespace Sports\Game;

use Sports\Game;
use Sports\Place as PlaceBase;

class Place
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var PlaceBase
     */
    private $place;
    /**
     * @var bool
     */
    private $homeaway;

    public function __construct(Game $game, PlaceBase $place, bool $homeaway)
    {
        $this->setGame($game);
        $this->setPlace($place);
        $this->setHomeaway($homeaway);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return PlaceBase
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param PlaceBase $place
     */
    public function setPlace(PlaceBase $place)
    {
        $this->place = $place;
    }

    /**
     * @return bool
     */
    public function getHomeaway()
    {
        return $this->homeaway;
    }

    /**
     * @param bool $homeaway
     */
    public function setHomeaway($homeaway)
    {
        $this->homeaway = $homeaway;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame(Game $game)
    {
        if ($this->game === null and !$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getPlaceNr(): int
    {
        return $this->getPlace()->getNumber();
    }
}
