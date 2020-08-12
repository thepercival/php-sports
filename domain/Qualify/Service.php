<?php

namespace Sports\Qualify;

use Sports\Ranking\Service as RankingService;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\State;
use Sports\Qualify\Rule\Single as QualifyRuleSingle;
use Sports\Qualify\Rule\Multiple as QualifyRuleMultiple;
use Sports\Qualify\Group as QualifyGroup;

class Service
{
    /**
     * @var Round
     */
    private $round;
    /**
     * @var RankingService
     */
    private $rankingService;
    /**
     * @var array
     */
    private $poulesFinished = [];
    /**
     * @var bool
     */
    private $roundFinished;
    /**
     * @var QualifyReservationService
     */
    private $reservationService;

    public function __construct(Round $round, int $ruleSet)
    {
        $this->round = $round;
        $this->rankingService = new RankingService($round, $ruleSet);
    }

    /**
     * @param Poule|null $filterPoule
     * @return array | Place[]
     */
    public function setQualifiers(Poule $filterPoule = null): array
    {
        $changedPlaces = [];

        $setQualifiersForHorizontalPoule = function (HorizontalPoule $horizontalPoule) use ($filterPoule, &$changedPlaces): void {
            $multipleRule = $horizontalPoule->getQualifyRuleMultiple();
            if ($multipleRule !== null) {
                $changedPlaces = array_merge($changedPlaces, $this->setQualifiersForMultipleRuleAndReserve($multipleRule));
            } else {
                foreach ($horizontalPoule->getPlaces() as $place) {
                    if ($filterPoule !== null && $place->getPoule() !== $filterPoule) {
                        continue;
                    }
                    $singleRule = $place->getToQualifyRule($horizontalPoule->getWinnersOrLosers());
                    $changedPlace = $this->setQualifierForSingleRuleAndReserve($singleRule);;
                    if ($changedPlace !== null) {
                        $changedPlaces[] = $changedPlace;
                    }
                }
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $this->reservationService = new QualifyReservationService($qualifyGroup->getChildRound());
            foreach ($qualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                $setQualifiersForHorizontalPoule($horizontalPoule);
            }
        }
        return $changedPlaces;
    }

    protected function setQualifierForSingleRuleAndReserve(QualifyRuleSingle $ruleSingle): ?Place
    {
        $fromPlace = $ruleSingle->getFromPlace();
        $poule = $fromPlace->getPoule();
        $rank = $fromPlace->getNumber();
        $this->reservationService->reserve($ruleSingle->getToPlace()->getPoule()->getNumber(), $poule);

        $qualifiedPlace = $this->getQualifiedPlace($poule, $rank);
        $toPlace = $ruleSingle->getToPlace();
        if ($toPlace->getQualifiedPlace() === $qualifiedPlace) {
            return null;
        }
        $toPlace->setQualifiedPlace($qualifiedPlace);
        return $toPlace;
    }

    /**
     * @param QualifyRuleMultiple $ruleMultiple
     * @return array | Place[]
     */
    protected function setQualifiersForMultipleRuleAndReserve(QualifyRuleMultiple $ruleMultiple): array
    {
        $changedPlaces = [];
        $toPlaces = $ruleMultiple->getToPlaces();
        if (!$this->isRoundFinished()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setQualifiedPlace(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $ruleMultiple->getFromRound();
        $rankedPlaceLocations = $this->rankingService->getPlaceLocationsForHorizontalPoule($ruleMultiple->getFromHorizontalPoule());

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $ruleMultiple->getWinnersOrLosers() === QualifyGroup::WINNERS ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
        }
        foreach ($toPlaces as $toPlace) {
            $toPouleNumber = $toPlace->getPoule()->getNumber();
            $rankedPlaceLocation = $this->reservationService->getFreeAndLeastAvailabe($toPouleNumber, $round, $rankedPlaceLocations);
            $toPlace->setQualifiedPlace($round->getPlace($rankedPlaceLocation));
            $changedPlaces[] = $toPlace;
            $index = array_search($rankedPlaceLocation, $rankedPlaceLocations, true);
            if ($index !== false) {
                unset($rankedPlaceLocations[$index]);
            }
        }
        return $changedPlaces;
    }

    protected function getQualifiedPlace(Poule $poule, int $rank): ?Place
    {
        if (!$this->isPouleFinished($poule)) {
            return null;
        }
        $pouleRankingItems = $this->rankingService->getItemsForPoule($poule);
        $rankingItem = $this->rankingService->getItemByRank($pouleRankingItems, $rank);
        return $poule->getPlace($rankingItem->getPlaceLocation()->getPlaceNr());
    }

    protected function isRoundFinished(): bool
    {
        if ($this->roundFinished === null) {
            $this->roundFinished = true;
            foreach ($this->round->getPoules() as $poule) {
                if (!$this->isPouleFinished($poule)) {
                    $this->roundFinished = false;
                    break;
                }
            }
        }
        return $this->roundFinished;
    }

    protected function isPouleFinished(Poule $poule): bool
    {
        if (!array_key_exists($poule->getNumber(), $this->poulesFinished)) {
            $this->poulesFinished[$poule->getNumber()] = ($poule->getState() === State::Finished);
        }
        return $this->poulesFinished[$poule->getNumber()];
    }
}
