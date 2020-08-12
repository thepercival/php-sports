<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-6-19
 * Time: 7:56
 */

namespace Sports\Ranking\RoundItem;

use Sports\Place;
use Sports\Place\Location as PlaceLocation;

class Ranked
{
    /**
     * @var int
     */
    private $uniqueRank;
    /**
     * @var int
     */
    private $rank;
    /**
     * @var Unranked
     */
    private $unranked;

    /**
     * Ranked constructor.
     * @param Unranked $unranked
     * @param int $uniqueRank
     * @param int $rank
     */
    public function __construct(Unranked $unranked, int $uniqueRank, int $rank)
    {
        $this->unranked = $unranked;
        $this->uniqueRank = $uniqueRank;
        $this->rank = $rank;
    }


    public function getUniqueRank(): int
    {
        return $this->uniqueRank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getPlaceLocation(): PlaceLocation
    {
        return $this->unranked->getPlaceLocation();
    }

    public function getUnranked(): Unranked
    {
        return $this->unranked;
    }

    public function getPlace(): Place
    {
        return $this->unranked->getRound()->getPlace($this->unranked->getPlaceLocation());
    }
}
