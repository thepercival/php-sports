<?php
declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\State;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Group as QualifyGroup;

class Service
{
    private RoundRankingCalculator $rankingCalculator;
    /**
     * @var array<int|string,bool>
     */
    private array $finishedPouleMap = [];
    private bool|null $roundFinished = null;

    public function __construct(private Round $round)
    {
        $this->rankingCalculator = new RoundRankingCalculator();
    }

    /**
     * @param Poule|null $filterPoule
     * @return list<Place>
     */
    public function setQualifiers(Poule $filterPoule = null): array
    {
        /**
         * @param HorizontalPoule $horizontalPoule
         * @param ReservationService $reservationService
         * @param list<Place> $changedPlaces
         */
        $setQualifiersForHorizontalPoule = function (
            HorizontalPoule $horizontalPoule,
            QualifyReservationService $reservationService,
            array &$changedPlaces
        ) use ($filterPoule): void {
            $multipleRule = $horizontalPoule->getMultipleQualifyRule();
            if ($multipleRule !== null) {
                $ruleChangedPlaces = $this->setQualifiersForMultipleRuleAndReserve($multipleRule, $reservationService);
                $changedPlaces = array_values(array_merge($changedPlaces, $ruleChangedPlaces));
            } else {
                foreach ($horizontalPoule->getPlaces() as $place) {
                    if ($filterPoule !== null && $place->getPoule() !== $filterPoule) {
                        continue;
                    }
                    $singleRule = $place->getSingleToQualifyRule();
                    if ($singleRule === null) {
                        continue;
                    }
                    $changedPlace = $this->setQualifierForSingleRuleAndReserve($singleRule, $reservationService);
                    if ($changedPlace !== null) {
                        $changedPlaces[] = $changedPlace;
                    }
                }
            }
        };
        $changedPlaces = [];
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $reservationService = new QualifyReservationService($qualifyGroup->getChildRound());
            foreach ($qualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                $setQualifiersForHorizontalPoule($horizontalPoule, $reservationService, $changedPlaces);
            }
        }
        /** @var list<Place> $changedPlaces */
        return $changedPlaces;
    }

    protected function setQualifierForSingleRuleAndReserve(
        SingleQualifyRule $singleRule,
        QualifyReservationService $reservationService
    ): ?Place {
        $fromPlace = $singleRule->getFromPlace();
        $poule = $fromPlace->getPoule();
        $rank = $fromPlace->getNumber();
        $reservationService->reserve($singleRule->getToPlace()->getPoule()->getNumber(), $poule);

        $qualifiedPlace = $this->getQualifiedPlace($poule, $rank);
        $toPlace = $singleRule->getToPlace();
        if ($toPlace->getQualifiedPlace() === $qualifiedPlace) {
            return null;
        }
        $toPlace->setQualifiedPlace($qualifiedPlace);
        return $toPlace;
    }

    /**
     * @param MultipleQualifyRule $multipleRule
     * @param QualifyReservationService $reservationService
     * @return list<Place>
     */
    protected function setQualifiersForMultipleRuleAndReserve(
        MultipleQualifyRule $multipleRule,
        QualifyReservationService $reservationService
    ): array {
        $changedPlaces = [];
        $toPlaces = $multipleRule->getToPlaces();
        if (!$this->isRoundFinished()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setQualifiedPlace(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $multipleRule->getFromRound();
        $rankedPlaceLocations = $this->rankingCalculator->getPlaceLocationsForHorizontalPoule($multipleRule->getFromHorizontalPoule());

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $multipleRule->getWinnersOrLosers() === QualifyGroup::WINNERS ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
        }
        foreach ($toPlaces as $toPlace) {
            $toPouleNumber = $toPlace->getPoule()->getNumber();
            $rankedPlaceLocation = $reservationService->getFreeAndLeastAvailabe($toPouleNumber, $round, $rankedPlaceLocations);
            $toPlace->setQualifiedPlace($round->getPlace($rankedPlaceLocation));
            $changedPlaces[] = $toPlace;
            $index = array_search($rankedPlaceLocation, $rankedPlaceLocations, true);
            if ($index !== false) {
                array_splice($rankedPlaceLocations, $index, 1);
            }
        }
        return $changedPlaces;
    }

    protected function getQualifiedPlace(Poule $poule, int $rank): ?Place
    {
        if (!$this->isPouleFinished($poule)) {
            return null;
        }
        $pouleRankingItems = $this->rankingCalculator->getItemsForPoule($poule);
        $rankingItem = $this->rankingCalculator->getItemByRank($pouleRankingItems, $rank);
        if ($rankingItem === null) {
            return null;
        }
        return $poule->getPlace($rankingItem->getPlace()->getNumber());
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
        if (!array_key_exists($poule->getNumber(), $this->finishedPouleMap)) {
            $this->finishedPouleMap[$poule->getNumber()] = ($poule->getState() === State::Finished);
        }
        return $this->finishedPouleMap[$poule->getNumber()];
    }
}
