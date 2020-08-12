<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 5-6-2019
 * Time: 12:21
 */

namespace Sports\Qualify\Rule;

use Sports\Poule;
use Sports\Place;
use Sports\Qualify\Rule as QualifyRule;
use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;

class Single extends QualifyRule
{

    /**
     * @var Place
     */
    private $fromPlace;
    /**
     * @var Place
     */
    private $toPlace;
    /**
     * @var int
     */
    private $winnersOrLosers;

    public function __construct(Place $fromPlace, QualifyGroup $toQualifyGroup)
    {
        $this->fromPlace = $fromPlace;
        $this->winnersOrLosers = $toQualifyGroup->getWinnersOrLosers();
        $this->fromPlace->setToQualifyRule($toQualifyGroup->getWinnersOrLosers(), $this);
    }

    public function getFromRound(): Round
    {
        return $this->fromPlace->getRound();
    }

    public function isMultiple(): bool
    {
        return false;
    }

    public function isSingle(): bool
    {
        return true;
    }

    public function getWinnersOrLosers(): int
    {
        return $this->winnersOrLosers;
    }

    public function getFromPlace(): Place
    {
        return $this->fromPlace;
    }

    public function getFromPoule(): Poule
    {
        return $this->fromPlace->getPoule();
    }

    public function getToPlace(): Place
    {
        return $this->toPlace;
    }

    public function setToPlace(Place $toPlace)
    {
        $this->toPlace = $toPlace;
        $toPlace->setFromQualifyRule($this);
    }

    public function getFromPlaceNumber(): int
    {
        if ($this->getWinnersOrLosers() === QualifyGroup::WINNERS) {
            return $this->getFromPlace()->getNumber();
        }
        return ($this->getFromPlace()->getPoule()->getPlaces()->count() - $this->getFromPlace()->getNumber()) + 1;
    }
}
